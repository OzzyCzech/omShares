<?php
namespace omShares;

/**
 * @author Roman Ožana <roman@ozana.cz>
 */
class Cache {

	const GROUP = 'omShares';

	/**
	 * @param $post_id
	 * @param $total
	 * @return bool
	 */
	public static function setTotal($post_id, $total) {
		return wp_cache_set($post_id . ':total', (int)$total, self::GROUP);
	}

	/**
	 * @param $post_id
	 * @return int
	 */
	public static function getTotal($post_id) {
		return (int)wp_cache_get($post_id . ':total', self::GROUP);
	}

	/**
	 * @param $post_id
	 * @return int
	 */
	public static function getHits($post_id) {
		return (int)wp_cache_get($post_id . ':hits', self::GROUP);
	}

	/**
	 * @param $post_id
	 * @param $hits
	 * @return int
	 */
	public static function setHits($post_id, $hits) {
		return wp_cache_set($post_id . ':hits', (int)$hits, self::GROUP);
	}


	/**
	 * @param $post_id
	 * @return bool|mixed
	 */
	public static function getShares($post_id) {
		return wp_cache_get($post_id . ':shares', self::GROUP);
	}

	/**
	 * @param $post_id
	 * @param $shares
	 * @return bool
	 */
	public static function setShares($post_id, $shares) {
		return wp_cache_set($post_id . ':shares', (array)$shares, self::GROUP);
	}

	/**
	 * @param $post_id
	 */
	public static function delete($post_id) {
		wp_cache_delete($post_id . ':hits', self::GROUP);
		wp_cache_delete($post_id . ':shares', self::GROUP);
		wp_cache_delete($post_id . ':total', self::GROUP);
	}

}
