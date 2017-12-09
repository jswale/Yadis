<?php

class TplManager {

	const TPL_EXT = ".html";

	/**
	 * Retrieve a template formatted
	 * @param string $template The template name
	 * @param array $keys Keys to replace in the template
	 * @return string
	 */
	public static function format($template, $keys = null) {
		$data = file_get_contents(Config::get('TEMPLATE_PATH') . DIRECTORY_SEPARATOR . $template . self::TPL_EXT);

		// Remplacement des clés
		if(!is_null($keys)) {
			foreach($keys as $key => $value) {
				$data = preg_replace("/\\$\{" . $key . "\}/", $value, $data);
			}
		}

		// Recherche des clés de langue
		preg_match_all("|#\{([^\}]*)}|", $data,	$lng_matches, PREG_PATTERN_ORDER);
		if(count($lng_matches[0]) > 0) {
			foreach($lng_matches[0] as $id => $lng_match) {
				$data = str_replace($lng_match	, I18n::getInstance()->getText(explode(".", $lng_matches[1][$id])), $data);
			}
		}

		return $data;
	}

}