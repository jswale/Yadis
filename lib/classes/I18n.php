<?php
class I18n {

	/**
	 * @var I18n
	 * @access private
	 */
	private static $_instance = null;

	/**
	 * @var json
	 */
	private $_json = null;

	private function __construct() {
		$sessionKey = Config::getSessionKey('JSON_' . Config::get('IHM_I18N'));

		if(isset($_SESSION[$sessionKey]) && Config::get('STORE_IN_SESSION')) {
			$this->_json = $_SESSION[$sessionKey];
			//Html::getInstance()->info("Données i18n chargées depuis la session");
			return;
		}

		$this->_json = FileUtils::loadJson(Config::get('I18N_PATH') . DIRECTORY_SEPARATOR , Config::get('IHM_I18N') . ".json");
		if(!is_array($this->_json)) {
			Html::getInstance()->error('Enable to load the <b>' . strtoupper(Config::get('IHM_I18N')) . '</b> i18n data due to format errors');
		} else {
			//Html::getInstance()->info("Données i18n chargées en mémoire");
		}
		$_SESSION[$sessionKey] = $this->_json;
	}

	/**
	 * @return I18n
	 */
	public static function getInstance() {
		if(is_null(self::$_instance)) {
			self::$_instance = new I18n();
		}
		return self::$_instance;
	}

	public function getI18n() {
		$sessionKey = Config::getSessionKey('_I18N');
		if(isset($_SESSION[$sessionKey]) && Config::get('STORE_IN_SESSION')) {
			return $_SESSION[$sessionKey];
		}

		// Extraction des langues
		$json = FileUtils::loadJson(Config::get('I18N_PATH'), Config::get('I18N_FILE'));

		if(Config::get('STORE_IN_SESSION')) {
			$_SESSION[$sessionKey] = $json;
		}

		return $json;
	}

	/**
	 * Retrieve a text from the lang file
	 * @return string
	 */
	public function getText($keys, $params = null) {
		if(!is_array($keys)) {
			$keys = array($keys);
		}

		// Recherche de la clé
		$text = $this->_json;
		foreach($keys as $key) {
			if(array_key_exists($key, $text)) {
				$text = $text[$key];
			} else {
				return implode(".", $keys);
			}
		}

		// Remplacement des valeurs
		if(!is_null($params)) {
			foreach($params as $param => $value) {
				$text = preg_replace("/\\$\{" . $param . "\}/", $value, $text);
			}
		}

		return $text;
	}
}