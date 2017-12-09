<?php
class Utils {
	/**
	 * Truncate a string
	 * @param string $string
	 * @param integer $max
	 * @param string $suffix
	 * @return Ambigous <unknown, mixed>
	 */
	public static function truncate ($string, $max = 50, $suffix = '') {
		$leave = $max - strlen ($suffix);
		return strlen($string) <= $max ? $string : substr_replace($string, $suffix, $leave);
	}

	/**
	 * Jump to a given url
	 * @param String_type $url
	 * @param Array $params The url parameters
	 */
	public static function jump($url, $params = array(), $anchor = null) {
		header('Location: ' . Utils::getUrl($url, $params, false, $anchor));
		die();
	}

	/**
	 * Retrieve a html link element
	 * @param string $url
	 * @param string $title
	 * @return string
	 */
	public static function getLink($url, $title) {
		return '<a href="' . $url . '">' . $title . '</a>';
	}

	/**
	 * Retrieve an url
	 * @param string $url
	 * @param Array $params
	 * @param boolean $requireOnline
	 * @return string
	 */
	public static function getUrl($url, $params = null, $requireOnline = false, $anchor = null) {
		if(true === $requireOnline && false === Config::getInstance()->isOnline()) {
			return 'javascript:void(0);';
		}

		if (!is_null($params)) {
			if (!strstr($url, "?")) {
				$url .= "?";
			}
			$attrs = array();
			foreach ($params as $key => $value) {
				array_push($attrs, $key . "=" . $value);
			}
			$url .= implode("&", $attrs);
		}

		if(!is_null($anchor)) {
			$url .= "#" . $anchor;
    }

		return $url;
	}

	/**
	 * Convert an Array to an textual array
	 * @param Array $array
	 * @return string
	 */
	public static function arrayToPhp($array) {
		return 'array(' . (count($array) > 0 ? '"' . implode('","', $array). '"' : '') . ')';
	}

}
