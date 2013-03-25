<?php
namespace omShares;
/**
 * @author Roman Ožana <ozana@omdesign.cz>
 */
interface IShares {
	/**
	 * @param string $url
	 * @return int|bool
	 */
	public static function getShares($url);
}