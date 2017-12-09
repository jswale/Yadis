<?php
class Html {

	/**
	 * @var Html
	 * @access private
	 */
	private static $_instance = null;

	/**
	 * The html content parts of the page
	 * @var array
	 */
	private $_html = null;

	/**
	 * The gestures links
	 * @var array
	 */
	private $_gestures = null;


	private function __construct() {
		$this->_gestures = array();
		$this->_html = array(
				'content' => array(),
				'info' => array(),
				'error' => array()
		);
	}

	/**
	 * @return Html
	 */
	public static function getInstance() {
		if(is_null(self::$_instance)) {
			self::$_instance = new Html();
		}
		return self::$_instance;
	}

	public function setGesture($direction, $link) {
		$this->_gestures[$direction] = $link;
	}

	public function append($html) {
		array_push($this->_html['content'], $html);
	}

	public function info($html) {
		array_push($this->_html['info'], $html);
	}

	public function error($html) {
		array_push($this->_html['error'], $html);
	}

	public function stop($html) {
		$this->error($html);
		$this->show();
	}

	public function show() {
		// Affichage du header
		$html = $this->getHeader();

		// Affichage de la navigation
		$html .= $this->getNavigation();

		// Affichage des gestures
		$html .= $this->getGestures();

		// Affichage des messages d'alerte
		if(count($this->_html['error']) > 0) {
			$html .= '<div class="errors">';
			$html .= implode('<br/>', $this->_html['error']);
			$html .= '</div>';
		}

		// Affichage des messages d'information
		if(count($this->_html['info']) > 0) {
			$html .= '<div class="infos">';
			$html .= implode('<br/>', $this->_html['info']);
			$html .= '</div>';
		}

		// Affichage du corps de la page
		if(count($this->_html['content']) > 0) {
			$html .= implode('', $this->_html['content']);
		}

		// Affichage du footer
		$html .= $this->getFooter();

		// Fin de l'affichage
		die($html);
	}

	private function getGestures() {
		$params = array();
		foreach(array("RIGHT", "LEFT", "UP", "DOWN") as $gesture) {
			$params[$gesture] = array_key_exists($gesture, $this->_gestures) ? $this->_gestures[$gesture] : "";
		}
		return TplManager::format("gestures", $params);
	}

	private function getHeader() {

		$title = array();
		array_push($title, Config::get('SITE_NAME'));
		if(ActionResolver::getInstance()->getKey(ActionResolver::KEY_MEDIA)) {
			array_push($title, I18n::getInstance()->getText(array("media", ActionResolver::getInstance()->getKey(ActionResolver::KEY_MEDIA))));
		}
		if(ActionResolver::getInstance()->getKey(ActionResolver::KEY_FILE)) {
			$fileInfo = ActionResolver::getInstance()->getKey(ActionResolver::KEY_FILE);
			$infos = pathinfo($fileInfo->getName());
			array_push($title, $infos["filename"]);
		}

		return TplManager::format("header", array(
				"TITLE" => implode(" :: ", $title),
				"REQUIRE_ONLINE_ACTION_DISPLAY" => Config::getInstance()->isOnline() ? "" : "none"
		));
	}

	private function getFooter() {
		return TplManager::format("footer");
	}


	private function getNavigation() {
		$levels = array();

		// Logo
		array_push($levels, array(
				"class" => "logo",
				"url" => Utils::getUrl(Config::get('INDEX_FILE'), array(ActionResolver::ARGS_ACTION => ACTION_CONFIG)),
				"name" => '<img src="ressources/img/icon-video.png"/>'
		));

		// Site name
		array_push($levels, array(
				"url" => Utils::getUrl(Config::get('INDEX_FILE')),
				"name" => Config::get('SITE_NAME')
		));

		// Media type
		$media = ActionResolver::getInstance()->getKey(ActionResolver::KEY_MEDIA);
		if(isset($media)) {
			array_push($levels, array(
					"url" => Utils::getUrl(Config::get('INDEX_FILE'), array(ActionResolver::ARGS_MEDIA => $media)),
					"name" => I18n::getInstance()->getText(array("media", $media))
			));
		}

		// Media part name
		$pid = ActionResolver::getInstance()->getKey(ActionResolver::KEY_PARAM_PID);
		if(isset($pid)) {
			array_push($levels, array(
					"url" => Utils::getUrl(Config::get('INDEX_FILE'), array(ActionResolver::ARGS_MEDIA => $media, ActionResolver::ARGS_PATH_ID => $pid)),
					"name" => basename(Config::getInstance()->getDataFolder($media, $pid))
			));
		}

		// Path
		$folders = explode(DIRECTORY_SEPARATOR, ActionResolver::getInstance()->getKey(ActionResolver::KEY_PATH));
		array_shift($folders);
		array_pop($folders);

		$current = "";
		foreach ($folders as $folder) {
			$current .= DIRECTORY_SEPARATOR . $folder;
			array_push($levels, array(
					"url" => Utils::getUrl(Config::get('INDEX_FILE'), array_merge(ActionResolver::getInstance()->getUrlArgs(), array(ActionResolver::ARGS_PATH => $current . DIRECTORY_SEPARATOR))),
					"name" => $folder
			));
		}

		// 		if(count($folders) > 0) {
		// 			Html::getInstance()->setGesture("UP", Utils::getUrl(Config::get('INDEX_FILE'), array_merge(ActionResolver::getInstance()->getUrlArgs(), array(ActionResolver::ARGS_PATH => dirname($current)))));
		// 		}

		// File name
		$fileInfo = ActionResolver::getInstance()->getKey(ActionResolver::KEY_FILE);
		if(!is_null($fileInfo)) {
			array_push($levels, array(
					"name" => '<b>' . $fileInfo->getName() . '</b>'
			));
		}

		if(is_null($fileInfo)) {
			array_push($levels, array(
					"class" => "logo",
					"style"=>"position: absolute; right: 0;",
					"url" => Utils::getUrl(Config::get('INDEX_FILE'), array(ActionResolver::ARGS_ACTION => ACTION_SHOW_CATEGORIES)),
					"name" => '<img src="ressources/img/categories.png"/>'
			));
		}

		$html = array();
		foreach($levels as $level) {
			$link = array_key_exists('url', $level) ? Utils::getLink($level["url"], $level["name"]) : $level["name"];
			array_push($html, '<li' . (array_key_exists('class', $level) ? ' class="' . $level['class'] . '"' : '') . (array_key_exists('style', $level) ? ' style="' . $level['style'] . '"' : '') . '>' . $link . '</li>');
		}

		{
			if(is_null($fileInfo)) {
				// Remove the category step
				array_pop($levels);
			}
			// Remove the current step
			array_pop($levels);
			// Remove the first step (config one)
			array_shift($levels);
			// Pick the last step for up gesture
			if(count($levels) > 0) {
				$level = array_pop($levels);
				Html::getInstance()->setGesture("UP", $level['url']);
			}
		}

		return TplManager::format("navigation", array(
				"ITEMS" => implode('<li><div class="separator"></div></li>', $html)
		));
	}

}
