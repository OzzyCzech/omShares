<?php
namespace omShares;
/**
 * @author Roman Ozana <ozana@omdesign.cz>
 * @see http://papermashup.com/display-facebook-likes-shares-php-function/
 */
class Facebook implements IShares {
	/**
	 * Vraci pocet share URL
	 *
	 * @param string $url
	 * @return bool|int
	 */
	public static function getShares($url) {

		$json_string = wp_remote_get(
			'https://graph.facebook.com/' . $url,
			array(
				'sslverify' => false
			)
		);

		$json_string = wp_remote_retrieve_body($json_string);

		$json = json_decode($json_string, true);

		if (isset($json['shares'])) {
			return intval($json['shares']);
		} elseif (isset($json['id'])) {
			return 0;
		} else {
			return false;
		}
	}
}