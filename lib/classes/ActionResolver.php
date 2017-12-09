<?php

class ActionResolver {

	const ARGS_MEDIA = "media";
	const ARGS_PATH_ID = "pid";
	const ARGS_PATH = "path";
	const ARGS_ACTION = "action";
	const ARGS_FILE_ID = "fid";
	const ARGS_FILE_INDEX = "fidx";
	const ARGS_FILE_NAME = "fn";
	const ARGS_SOURCE_ID = "sid";
	const ARGS_SEARCH_VALUE = "sv";
	const ARGS_STAY_IN_FOLDER = "sif";
	const ARGS_ANCHOR = "a";
	const ARGS_ID = "id";

	const KEY_ID = "id";
	const KEY_MODE = "mode";
	const KEY_MEDIA = "media";
	const KEY_PARAM_PID = "pid";
	const KEY_PATH = "path";
	const KEY_ROOT = "root";
	const KEY_FULL_PATH = "fullpath";
	const KEY_FILE = "file";
	const KEY_SEARCH_VALUE = "search_value";

	/**
	 * @var ActionResolver
	 */
	private static $_instance = null;

	protected $_display;
	protected $_urlArgs;

	private function __construct() {
		$this->_urlArgs = array();
		$this->_display = array(
				self::KEY_MODE   => DISPLAY_FOLDER,
		);
	}

	/**
	 * @return ActionResolver
	 */
	public static function getInstance() {
		if(is_null(self::$_instance)) {
			self::$_instance = new ActionResolver();
		}
		return self::$_instance;
	}

	/**
	 * Retrieve the computed key
	 * @param string $key
	 */
	public function getKey($key) {
		return $this->_display[$key];
	}

	/**
	 * Retrieve the urls arguments according to the context
	 * @return Array
	 */
	public function getUrlArgs() {
		return $this->_urlArgs;
	}

	/**
	 * Resolve the arguments and the display resolution
	 */
	public function resolve() {
		$this->parse();
		$this->display();
	}

	/**
	 * Store a key
	 * @param string $key The key
	 * @param string $value The value
	 * @param boolean $url_arg If true the value will be returned by the getUrlArgs() method
	 */
	private function setKey($key, $value, $url_arg=false) {
		$this->_display[$key] = $value;
		if($url_arg !== false) {
			$this->_urlArgs[$url_arg] = $value;
		}
	}

	/**
	 * Parse the url arguments
	 */
	private function parse() {
		if(!Config::get('INITIALIZED')) {
			Html::getInstance()->error(I18n::getInstance()->getText(array("error", "conf_required")));
			Config::getInstance()->initFolders();
		}

		$skipContext = $this->skipContext();

		// Traitement du context (media / dossier / chemin)
		if(!$skipContext) {
			if(false === $this->manageContext()) {
				return;
			}
		}

		// Traitement des actions
		$this->manageAction();
	}

	/**
	 * Determine if the context management has to be skipped
	 * @return boolean
	 */
	private function skipContext() {
		if (!empty($_REQUEST[self::ARGS_ACTION]) && in_array($_REQUEST[self::ARGS_ACTION], array(ACTION_CONFIG, ACTION_CONFIG_SAVE, ACTION_SEARCH_DUPLICATE))) {
			return true;
		}
		return false;
	}

	/**
	 * Manage the current context (media > pid > path)
	 * @return boolean If true the action management will be skipped
	 */
	private function manageContext() {
		if(!Config::get('INITIALIZED')) {
			$this->setKey(self::KEY_MODE, DISPLAY_CONFIG);
			return true;
		}

		// Choix du type de média
		if(!isset($_REQUEST[self::ARGS_MEDIA])) {
			if(null == Config::getInstance()->getDataFolder(MEDIA_SERIE)) {
				$this->setKey(self::KEY_MEDIA, MEDIA_VIDEO, self::ARGS_MEDIA);
			} else {
				$this->setKey(self::KEY_MODE, DISPLAY_MEDIA_CHOOSE);
				return false;
			}
		} else {
			$this->setKey(self::KEY_MEDIA, $_REQUEST[self::ARGS_MEDIA], self::ARGS_MEDIA);
		}

		// Choix du pid
		if(!isset($_REQUEST[self::ARGS_PATH_ID])) {
			// Choix automatique
			if(count(Config::getInstance()->getDataFolder($this->getKey(self::KEY_MEDIA))) == 1) {
				$this->setKey(self::KEY_PARAM_PID, 0, self::ARGS_PATH_ID);
			} else {
				// Choix manuel
				$this->setKey(self::KEY_MODE, DISPLAY_FOLDER_CHOOSE);
				return false;
			}
		} else {
			$this->setKey(self::KEY_PARAM_PID, $_REQUEST[self::ARGS_PATH_ID], self::ARGS_PATH_ID);
		}

		// Navigation actuelle
		$this->setKey(self::KEY_PATH, isset($_REQUEST[self::ARGS_PATH]) ? $_REQUEST[self::ARGS_PATH] : DIRECTORY_SEPARATOR, self::ARGS_PATH);
		$this->setKey(self::KEY_ROOT, Config::getInstance()->getDataFolder($this->getKey(self::KEY_MEDIA), $this->getKey(self::KEY_PARAM_PID)));
		$this->setKey(self::KEY_FULL_PATH, $this->getKey(self::KEY_ROOT) . $this->getKey(self::KEY_PATH));

		return true;
	}

	/**
	 * Manage the action according to the current context
	 */
	private function manageAction() {
		// Aucune action donc fin du traitement
		if (empty($_REQUEST[self::ARGS_ACTION])) {
			return;
		}

		$fileInfo = new FileInfo();
		if(!$fileInfo->loadFromUrlArgs()) {
			$fileInfo = null;
		} else {
			$this->setKey(self::KEY_FILE, $fileInfo);
		}

		// Traitement des actions
		// 		die($_REQUEST[self::ARGS_ACTION]);
		switch ($_REQUEST[self::ARGS_ACTION]) {
			case ACTION_CHOOSE:
				MovieHelper::getInstance()->saveMovie($fileInfo, $_REQUEST[self::ARGS_SOURCE_ID]);
				Utils::jump(Config::get('INDEX_FILE'),  array_merge(ActionResolver::getInstance()->getUrlArgs(), array(ActionResolver::ARGS_ACTION => ACTION_DETAIL), $fileInfo->getUrlArgs()));
				break;

			case ACTION_DISCONNECT:
				FileUtils::removeDirectory(MovieHelper::getInstance()->getCachedPath($fileInfo));
				Utils::jump(Config::get('INDEX_FILE'), ActionResolver::getInstance()->getUrlArgs());
				break;

			case ACTION_REFRESH:
				FileUtils::removeDirectory(MovieHelper::getInstance()->getCachedPath($fileInfo));
				MovieHelper::getInstance()->saveMovie($fileInfo, $_REQUEST[self::ARGS_SOURCE_ID]);
				break;

			case ACTION_REFRESH_CODEC:
				MovieHelper::getInstance()->saveCodecInfos($fileInfo);
				break;

			case ACTION_DETAIL:
				$this->setKey(self::KEY_MODE, DISPLAY_DETAIL);
				break;

			case ACTION_DETAIL_SEARCH:
				$this->setKey(self::KEY_MODE, DISPLAY_DETAIL_SEARCH);
				$this->setKey(self::KEY_SEARCH_VALUE, $_REQUEST[self::ARGS_SEARCH_VALUE]);
				break;

			case ACTION_DETAIL_CHOOSE:
				$this->setKey(self::KEY_MODE, DISPLAY_CHOOSE);
				break;

			case ACTION_CONFIG_SAVE:
				$this->setKey(self::KEY_MODE, DISPLAY_CONFIG);
				$errors = Config::getInstance()->saveConfig($_REQUEST["I18N_LNG"], $_REQUEST["TMDB_API_KEY"], $_REQUEST["FFMPEG_BINARY"], $_REQUEST["DATA_VIDEO_PATHS"], 'on' == $_REQUEST["JS_BACKDROP_SWITCH"]);
				if(is_null($errors)) {
					Utils::jump(Config::get('INDEX_FILE'),  array(ActionResolver::ARGS_ACTION => ACTION_CONFIG));
				} else {
					Html::getInstance()->error($errors);
				}
				break;

			case ACTION_SEARCH_DUPLICATE :
				$this->setKey(self::KEY_MODE, DISPLAY_DUPLICATE);
				break;

			case ACTION_SEARCH_UNKNOWN :
				$this->setKey(self::KEY_MODE, DISPLAY_UNKNOWN);
				break;

			case ACTION_DETAIL_SWITCH_VIEWED:
				MovieHelper::getInstance()->switchViewed($fileInfo);
				if($_REQUEST[ActionResolver::ARGS_STAY_IN_FOLDER]) {
					Utils::jump(Config::get('INDEX_FILE'), ActionResolver::getInstance()->getUrlArgs(), $_REQUEST[self::ARGS_ANCHOR]);
				} else {
					$this->setKey(self::KEY_MODE, DISPLAY_DETAIL);
				}
				break;

			case ACTION_CLEAN_CACHE:
				MediaMaintenance::getInstance()->cleanCache();
				$this->setKey(self::KEY_MODE, DISPLAY_CONFIG);
				break;

			case ACTION_CONFIG:
				$this->setKey(self::KEY_MODE, DISPLAY_CONFIG);
				break;

			case ACTION_SHOW_CATEGORIES:
				$this->setKey(self::KEY_ID, $_REQUEST[self::ARGS_ID]);
				$this->setKey(self::KEY_MODE, DISPLAY_CATEGORIES);
				break;

			case ACTION_BUILD_INDEX:
				MovieCategoryHelper::getInstance()->buildIndex();
				Utils::jump(Config::get('INDEX_FILE'),  array(ActionResolver::ARGS_ACTION => ACTION_SHOW_CATEGORIES));
				break;
		}
	}

	/**
	 * Manage the display according to the computed arguments
	 */
	private function display() {
		// Instances
		$html = Html::getInstance();
		$mediaHelper = MediaHelper::getInstance();
		$movieDisplayHelper = MovieDisplayHelper::getInstance();
		$movieHelper = MovieHelper::getInstance();
		$mediaMaintenance = MediaMaintenance::getInstance();
		$movieCategoryHelper = MovieCategoryHelper::getInstance();
		$folderHelper = FolderHelper::getInstance();
		$config = Config::getInstance();

		$html->append("<!-- KEY_MODE = " . $this->getKey(ActionResolver::KEY_MODE) . " -->");
		// Display
		switch ($this->getKey(ActionResolver::KEY_MODE)) {
			case DISPLAY_MEDIA_CHOOSE:
			default:
				$html->append($mediaHelper->getChooseDisplay());
				break;

			case DISPLAY_FOLDER_CHOOSE:
			default:
				$html->append($mediaHelper->getFolderChooseDisplay($this->getKey(ActionResolver::KEY_MEDIA)));
				break;

			case DISPLAY_FOLDER:
			default:
				$html->append($folderHelper->getFolderDisplay());
				break;

			case DISPLAY_DETAIL:
			case DISPLAY_CHOOSE:
				$html->append($movieHelper->getMovieDetailDisplay($this->getKey(ActionResolver::KEY_FILE)));
				break;

			case DISPLAY_DETAIL_SEARCH:
				$html->append($movieDisplayHelper->getMovieSearch($this->getKey(ActionResolver::KEY_FILE), $this->getKey(ActionResolver::KEY_SEARCH_VALUE)));
				break;

			case DISPLAY_CONFIG:
				$html->append($config->getForm());
				break;

			case DISPLAY_DUPLICATE:
				$html->append($mediaMaintenance->showDuplicate());
				break;

			case DISPLAY_UNKNOWN:
				$html->append($mediaMaintenance->showUnknown());
				break;

			case DISPLAY_CATEGORIES:
				$html->append($movieCategoryHelper->show($this->getKey(ActionResolver::KEY_ID)));
				break;

			default:
				$html->error("Action inconnue");
				break;

		}
		Html::getInstance()->show();
	}
}
