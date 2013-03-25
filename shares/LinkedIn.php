<?php
namespace omShares;

/**
 * @author Roman Ozana <ozana@omdesign.cz>
 */
class LinkedIn implements IShares {

	/**
	 * @param string $url
	 * @return int|bool
	 */
	public static function getShares($url) {
		$json_string = wp_remote_get(
			'http://www.linkedin.com/countserv/count/share?format=json&url=' . $url,
			array(
				'sslverify' => false
			)
		);
		if (is_wp_error($json_string)) return false;
		$json_string = wp_remote_retrieve_body($json_string);
		$json = json_decode($json_string, true);
		return intval($json['count']);
	}
}