<?php
namespace omShares;
/**
 * Plugin Name: omShares
 * Plugin URI: http://www.omdesign.cz
 * Description: Getting total page shares count auto-update on background with cron
 * Version: 1.0
 * Author: Roman Ožana
 * Author URI: http://www.omdesign.cz/kontakt
 */

require_once __DIR__ . '/Cache.php';
require_once __DIR__ . '/shares/IShares.php';
require_once __DIR__ . '/shares/LinkedIn.php';
require_once __DIR__ . '/shares/Twitter.php';
require_once __DIR__ . '/shares/Facebook.php';
require_once __DIR__ . '/shares/GooglePlus.php';

/**
 * @see http://codex.wordpress.org/Function_Reference/wp_schedule_event
 * @author Roman Ožana <ozana@omdesign.cz>
 */
class omShares {

	/** @var $wpdb */
	private $wpdb;

	/** @var string */
	private $table;

	/** @var array */
	private $cache = [];

	const CRON_HOOK = 'om_shares_update_hook'; // cron hook name

	/**
	 * @param \wpdb $wpdb
	 */
	public function __construct(\wpdb $wpdb = null) {
		$this->wpdb = $wpdb;
		$this->table = $this->wpdb->prefix . 'om_shares'; // setup table name

		// activate and deactivate plugin hook
		register_activation_hook(__FILE__, [$this, 'activation']);
		register_deactivation_hook(__FILE__, [$this, 'deactivation']);

		add_action('delete_post', [$this, 'deletePost']);
		add_action('cron_schedules', [$this, 'cronSchedules']);

		add_action(self::CRON_HOOK, [$this, 'updateSharesCounts']); // setup cron
	}

	/**
	 * Getting total shares count
	 *
	 * @param null $post_id
	 * @return int
	 */
	public function getTotalSharesCount($post_id = null) {
		$post_id = (int)($post_id == null) ? get_the_ID() : $post_id; // post_id can be null

		$this->hit($post_id); // +1 hit

		if (!$total = Cache::getTotal($post_id)) {
			$total = (int)$this->wpdb->get_var("SELECT `total` FROM $this->table WHERE `post_id` = '$post_id' LIMIT 1");
			Cache::setTotal($post_id, $total);
		}

		return $total;
	}

	/**
	 * Return shares
	 *
	 *
	 * @param null $network
	 * @param null $post_id
	 * @return array|int
	 */
	public function getNetworkSharesCount($network, $post_id = null) {
		$post_id = (int)($post_id == null) ? get_the_ID() : $post_id; // post_id can be null

		$this->hit($post_id); // +1 hit

		// getting shares count from
		if (!$shares = Cache::getShares($post_id)) {
			$shares = json_decode(
				(string)$this->wpdb->get_var("SELECT `shares` FROM $this->table WHERE `post_id` = '$post_id' LIMIT 1")
			);
			Cache::setShares($post_id, $shares);
		}

		return array_key_exists($network, $shares) ? $shares[$network] : 0;
	}

	/**
	 * Cron task for update shares counts
	 * - select 10 from start some most hits articles
	 * - select 10 from end some not hits articles or old one
	 */
	public function updateSharesCounts($size = 10) {
		// get some hits posts
		$sql = "SELECT `post_id` FROM `$this->table` WHERE `hits` IS NOT NULL ORDER BY `hits` DESC, `stamp` ASC LIMIT " . $size;
		$hits = (array)$this->wpdb->get_col($sql);

		var_dump($hits);

		// get some oldies :)
		$sql = "SELECT `post_id` FROM `$this->table` ORDER BY `stamp` ASC LIMIT " . $size;
		$oldies = (array)$this->wpdb->get_col($sql);

		foreach (array_merge($hits, $oldies) as $id) {
			$shares = $this->getNewSharesCount($id);
			$this->saveShareData($id, $shares, $total = (int)array_sum($shares));
			Cache::setHits($id, 0); // reset hits
		}
	}

	/**
	 * Download shares for one post by ID
	 *
	 * @param int $post_id
	 * @return array
	 */
	public function getNewSharesCount($post_id) {
		$url = apply_filters('share_url', get_permalink($post_id));

		return [
			'twitter' => Twitter::getShares($url),
			'facebook' => Facebook::getShares($url),
			'linkedin' => LinkedIn::getShares($url),
			'gplus' => GooglePlus::getShares($url),
		];
	}


	/**
	 * Hit counter
	 *
	 * @param $post_id
	 */
	public function hit($post_id) {
		$hits = Cache::getHits($post_id);
		$inc = (is_home() || is_single()) ? 3 : 1; // hompage and single is 3 x more important

		if (!$hits || $hits > 50) {
			$this->updateHits($post_id, $hits + $inc);
			Cache::setHits($post_id, $hits = 1); // try set cache for next time
		} else {
			Cache::setHits($post_id, $hits + $inc);
		}
	}

	/**
	 * Return post hits
	 *
	 * @param $post_id
	 * @return int
	 */
	public function getHits($post_id) {
		$post_id = (int)$post_id;

		if (!$hits = Cache::getHits($post_id)) {
			$hits = (int)$this->wpdb->get_var("SELECT `hits` FROM $this->table WHERE `post_id` = '$post_id' LIMIT 1");
			Cache::setHits($post_id, $hits);
		}

		return $hits;
	}

	/**
	 * Return last update date
	 *
	 * @param $post_id
	 * @return null|string
	 */
	public function getLastUpdateDate($post_id) {
		return $this->wpdb->get_var("SELECT `stamp` FROM $this->table WHERE `post_id` = '$post_id' LIMIT 1");
	}

	/**
	 * Delete record from databas
	 *
	 * @param $post_id
	 */
	public function deletePost($post_id) {
		Cache::delete($post_id);
		$this->wpdb->delete($this->table, ['post_id' => $post_id]);
	}

	// -------------------------------------------------------------------------------------------------------------------

	/**
	 * Update hits counter
	 *
	 * @param $post_id
	 * @param int $hits
	 * @return \false|int
	 */
	private function updateHits($post_id, $hits = 1) {
		if (!get_post($post_id)) return false;

		$sql =
			"
				INSERT INTO $this->table
					(`post_id`,`shares`,`total`, `hits`) VALUES ('$post_id', '[]', 0, 999)
  			ON DUPLICATE KEY UPDATE
  				`hits` = IFNULL(`hits`, 0) + $hits
  		";

		return $this->wpdb->query($sql);
	}

	/**
	 * Insert shares record
	 *
	 * @param int $post_id
	 * @param array $shares
	 * @param string $total
	 * @return bool
	 */
	private function saveShareData($post_id, $shares, $total) {
		if (!get_post($post_id)) return false;

		// 1. save result to cache first
		Cache::setShares($post_id, $shares);
		Cache::setTotal($post_id, $total);

		// 2. backup result to database
		$shares = json_encode($shares);

		$sql = "
			INSERT INTO {$this->table}
				(`post_id`,`shares`,`total`) VALUES ('$post_id', '$shares', '$total')
  		ON DUPLICATE KEY UPDATE
  			`shares` = '$shares', `total` = '$total', `hits` = null";

		return $this->wpdb->query($sql);
	}

	/**
	 * Plugin activation
	 */
	public function activation() {
		if ($this->wpdb->get_var("SHOW TABLES LIKE '$this->table';") !== $this->table) $this->createTable(); // if missing

		if (!wp_next_scheduled(self::CRON_HOOK)) {
			wp_schedule_event(time(), 'superoften', self::CRON_HOOK);
		}
	}


	/**
	 * Plugin deactivation
	 */
	public function deactivation() {
		wp_clear_scheduled_hook(self::CRON_HOOK);
		wp_cache_flush(); // delete cache
	}

	public function createTable() {
		$this->wpdb->query(
			"
					CREATE TABLE `$this->table` (
						`post_id` bigint(20) unsigned NOT NULL DEFAULT '0',
						`shares` longtext COLLATE utf8_czech_ci NOT NULL,
						`total` int(10) unsigned NOT NULL DEFAULT '0',
						`hits` bigint(20) unsigned DEFAULT NULL,
						`stamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
						PRIMARY KEY (`post_id`)
					) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;
			"
		);
	}

	/**
	 * Add new cron shedule period
	 *
	 * @param $schedules
	 * @return mixed
	 */
	public function cronSchedules($schedules) {
		$schedules['superoften'] = [
			'interval' => MINUTE_IN_SECONDS * 3,
			'display' => __('Super often')
		];
		return $schedules;
	}


	// -------------------------------------------------------------------------------------------------------------------

	/**
	 * Plugin uninstall hook
	 */
	public static function uninstall() {
		wp_clear_scheduled_hook(self::CRON_HOOK);
	}

}

register_uninstall_hook(__FILE__, ['omShares\omShares', 'uninstall']);

global $wpdb;
$GLOBALS['omShares'] = $omShares = new omShares($wpdb);

// $omShares->updateSharesCounts(); // FIXME for debug only