<?php
namespace omShares;
/**
 * @author Roman Ozana <ozana@omdesign.cz>
 */
class GooglePlus implements IShares {

	/**
	 * @param string $url
	 * @return bool|int
	 */
	public static function getShares($url) {
		$args = array(
			'method' => 'POST',
			'headers' => array(
				'Content-Type' => 'application/json'
			),
			'body' => json_encode(
				array(
					'method' => 'pos.plusones.get',
					'id' => 'p',
					'method' => 'pos.plusones.get',
					'jsonrpc' => '2.0',
					'key' => 'p',
					'apiVersion' => 'v1',
					'params' => array(
						'nolog' => true,
						'id' => $url,
						'source' => 'widget',
						'userId' => '@viewer',
						'groupId' => '@self'
					)
				)
			),
			'sslverify' => false
		);

		$json_string = wp_remote_post("https://clients6.google.com/rpc", $args);

		if (is_wp_error($json_string)) {
			return false;
		} else {
			$json = json_decode($json_string['body'], true);
			return intval($json['result']['metadata']['globalCounts']['count']);
		}
	}
}