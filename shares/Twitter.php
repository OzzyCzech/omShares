<?php
namespace omShares;
/**
 * @author Roman Ozana <ozana@omdesign.cz>
 */
class Twitter implements IShares {

	/**
	 * @param string $url
	 * @return bool|int
	 */
	public static function getShares($url) {
		$json_string = wp_remote_get(
			'https://urls.api.twitter.com/1/urls/count.json?url=' . $url,
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