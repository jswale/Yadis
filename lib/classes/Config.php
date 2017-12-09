<?php
class Config {
	/**
	 * @var Config
	 * @access private
	 */
	private static $_instance = null;


	/**
	 * @var Array
	 */
	private $_config = null;

	/**
	 * Online connection status
	 * @var boolean
	 * @access private
	 */
	private $_online = null;

	/**
	 * Retrieve the singleton
	 * @return Config
	 */
	public static function getInstance() {
		if(is_null(self::$_instance)) {
			self::$_instance = new Config();
			self::$_instance->init();
			self::$_instance->checkOnline();
		}
		return self::$_instance;
	}

	private function __construct() {
		$this->_config = array();
	}

	/**
	 * Set a property
	 * @param string $key
	 * @param string $value
	 */
	public static function set($key, $value) {
		self::getInstance()->_config[$key] = $value;
	}

	/**
	 * Retrieve a property
	 * @param string $key
	 * @return multitype:
	 */
	public static function get($key) {
		return self::getInstance()->_config[$key];
	}

	/**
	 * Retrieve the session key
	 * @param string $key
	 * @return string
	 */
	public static function getSessionKey($key) {
		return self::get('VERSION') . '_' . $key . "_" /*. time()*/;
	}

	/**
	 * Initialize the project properties
	 */
	public function init() {
		// Is the application initialized
		self::set('INITIALIZED', false);

		// Site name
		self::set('SITE_NAME', "Yadis");

		// Allow to store data in session
		self::set('STORE_IN_SESSION', true);

		// Is the server a diskstation ?
		self::set('DISKSTATION', false !== strpos($_SERVER["PATH"], "/usr/syno/bin"));

		// Parameters separator
		self::set('CONFIG_SEPARATOR', ';');

		// Version
		self::set('VERSION', "1.0.2");

		// Enable the backdrop display
		self::set('JS_BACKDROP_SWITCH', true);

		// Default language
		self::set('IHM_I18N', 'en');

		// Name of the index file
		self::set("INDEX_FILE", basename($_SERVER["SCRIPT_NAME"]));

		// WWW path of the site
		self::set('WWW_PATH', dirname(dirname(__DIR__)));

		// Path to the i18n files
		self::set('I18N_PATH', "ressources/i18n");

		// Path to the template ressources
		self::set("TEMPLATE_PATH", "ressources/tpl");

		self::set("CONFIG_PATH", "conf");

		// Path to the cache folder for the movies
		self::set('CACHE_VIDEO_PATH', "cache/movies");

		// Path to the cache folder for the series
		self::set('CACHE_SERIE_PATH', "cache/series");

		// Name of the user configuration file
		self::set('CONFIG_FILE', "config.environment.php");

		// Name of the json language
		self::set('I18N_FILE', "i18n.json");

		/**
		 * Required
		 */
		self::set('TVDB_API_KEY', 'AF1A17CC303EC4BA');
		self::set('TMDB_API_KEY', '');
// 		self::set('FFMPEG_BINARY', '/usr/syno/bin/ffmpeg');	// => Synology
		self::set('FFMPEG_BINARY', '/usr/bin/ffmpeg'); // => Linux
		self::set('DATA_VIDEO_PATHS', array());
		self::set('DATA_SERIE_PATHS', array());
	}

	/**
	 * Initialize the application folders
	 */
	public function initFolders() {
		FileUtils::createFolder(Config::get('CONFIG_PATH'));
		FileUtils::createFolder(Config::get('CACHE_VIDEO_PATH'));
		FileUtils::createFolder(Config::get('CACHE_SERIE_PATH'));
	}

	/**
	 * Determines the online connection status
	 */
	public function checkOnline() {
		if(is_null($this->_online)) {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'HEAD');
			curl_setopt($ch, CURLOPT_NOBODY, 1);
			curl_setopt($ch, CURLOPT_URL, "http://www.google.fr");
			curl_setopt($ch, CURLOPT_HEADER, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$response = curl_exec($ch);
			$this->_online = curl_errno($ch) == 0;

			if(!$this->_online) {
				Html::getInstance()->info(I18n::getInstance()->getText(array("error", "offline")));
			}
		}
	}

	/**
	 * Returns the online connection status
	 * @return boolean
	 */
	public function isOnline() {
		return $this->_online;
	}

	/**
	 * Retrieve the folder(s) for a given media type
	 * @param string $type MEDIA_VIDEO or MEDIA_SERIE
	 * @param string $pid If null retieve all the folders. Otherwise retrive only the path at the index $pid
	 * @return string or Array
	 */
	public function getDataFolder($type, $pid = null) {
		$paths = array();
		switch($type) {
			case MEDIA_VIDEO :
				$paths = count(Config::get('DATA_VIDEO_PATHS')) == 0 ? null : Config::get('DATA_VIDEO_PATHS');
				break;

			case MEDIA_SERIE :
				$paths = count(Config::get('DATA_SERIE_PATHS')) == 0 ? null : Config::get('DATA_SERIE_PATHS');
				break;
		}
		if(is_null($pid)) {
			return $paths;
		} else {
			return $paths[$pid];
		}
	}

	/**
	 * Retieve the cache folder for a given media type
	 * @param string $type MEDIA_VIDEO or MEDIA_SERIE
	 * @return string
	 */
	public function getCacheFolder($type) {
		switch($type) {
			case MEDIA_VIDEO :
				return Config::get('CACHE_VIDEO_PATH');
				break;

			case MEDIA_SERIE :
				return Config::get('CACHE_SERIE_PATH');
				break;
		}
	}

	/**
	 * Retrieve the config form
	 * @return string
	 */
	public function getForm() {
		$lngs = array();
		foreach(I18n::getInstance()->getI18n() as $key => $value) {
			array_push($lngs, '<option ' . ((Config::get('INITIALIZED') && ($key == Config::get('IHM_I18N'))) ? 'selected="true"' : '' ) . ' value="' . $key . '">' . $value . '</option>');
		}

		return TplManager::format("config", array(
			"INDEX_FILE" => Config::get('INDEX_FILE'),
			"ACTION_KEY" => ActionResolver::ARGS_ACTION,
			"ACTION_CLEAN_CACHE" => ACTION_CLEAN_CACHE,
			"ACTION_CONFIG_SAVE" => ACTION_CONFIG_SAVE,
			"ACTION_SEARCH_DUPLICATE" => ACTION_SEARCH_DUPLICATE,
			"ACTION_SEARCH_UNKNOWN" => ACTION_SEARCH_UNKNOWN,
			"ACTION_BUILD_INDEX" => ACTION_BUILD_INDEX,
			"I18N_LNG" => implode('', $lngs),
			"JS_BACKDROP_SWITCH" => Config::get('JS_BACKDROP_SWITCH') ? "checked" : "",
			"TMDB_API_KEY" => Config::get('TMDB_API_KEY'),
			"FFMPEG_BINARY" => Config::get('FFMPEG_BINARY'),
			"DATA_VIDEO_PATHS" => implode("\n", Config::get('DATA_VIDEO_PATHS'))
		));
	}

	/**
	 * Write the user config file
	 * @param string $i18n
	 * @param string $tmdbApiKey
	 * @param string $ffmpegBinaryPath
	 * @param string $dataVideoPaths
	 * @param boolean $jsBackdropSwitch
	 * @return string If not null then contains the error text
	 */
	public function saveConfig($i18n, $tmdbApiKey, $ffmpegBinaryPath, $dataVideoPaths, $jsBackdropSwitch) {
		$cfg = array();
		array_push($cfg, '<?php');
		array_push($cfg, 'Config::set("INITIALIZED", true);');
		array_push($cfg, 'Config::set("IHM_I18N", "' . $i18n . '");');
		array_push($cfg, 'Config::set("TMDB_API_KEY", "' . $tmdbApiKey . '");');
		array_push($cfg, 'Config::set("FFMPEG_BINARY", "' . $ffmpegBinaryPath . '");');
		array_push($cfg, 'Config::set("JS_BACKDROP_SWITCH", ' . ($jsBackdropSwitch ? 'true' : 'false') . ');');
		array_push($cfg, 'Config::set("DATA_VIDEO_PATHS", ' . Utils::arrayToPhp(explode("\r\n", $dataVideoPaths)) . ');');

		if(false === FileUtils::writeFile(Config::get('CONFIG_PATH'), Config::get('CONFIG_FILE'), implode("\r\n", $cfg))) {
			return I18n::getInstance()->getText(array("error", "writeconf"), array("FOLDER" => Config::get('CONFIG_PATH') . DIRECTORY_SEPARATOR . Config::get('CONFIG_FILE')));
		}
	}

}