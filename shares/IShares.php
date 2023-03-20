<?php
namespace omShares;
/**
 * @author Roman Ožana <roman@ozana.cz>
 */
interface IShares {
	/**
	 * @param string $url
	 * @return int|bool
	 */
	public static function getShares($url);
}