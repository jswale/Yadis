<?php
class MediaHelper {

	/**
	 * @var MediaHelper
	 * @access private
	 */
	private static $_instance = null;

	/**
	 * @return MediaHelper
	 */
	public static function getInstance() {
		if(is_null(self::$_instance)) {
			self::$_instance = new MediaHelper();
		}
		return self::$_instance;
	}

	public function getChooseDisplay() {
		$html = "";
		$html .= '<div class="listcontent">';
		$html .= '	<ul class="folder_items">';
		$html .= '		<li><div class="separator"></div>' . Utils::getLink(Utils::getUrl(Config::get('INDEX_FILE'),	array(ActionResolver::ARGS_MEDIA => MEDIA_VIDEO)), I18n::getInstance()->getText(array("media", MEDIA_VIDEO))). '</li>';
		$html .= '		<li><div class="separator"></div>' . Utils::getLink(Utils::getUrl(Config::get('INDEX_FILE'),	array(ActionResolver::ARGS_MEDIA => MEDIA_SERIE)), I18n::getInstance()->getText(array("media", MEDIA_SERIE))). '</li>';
		$html .= '	</ul>';
		$html .= '</div>';
		return $html;
	}

	public function getFolderChooseDisplay($mediaType) {
		$html = "";
		$html .= '<div class="listcontent">';
		$html .= '	<ul class="folder_items">';
		foreach(Config::getInstance()->getDataFolder($mediaType) as $pid => $path) {
			$html .= '		<li><div class="separator"></div>' . Utils::getLink(Utils::getUrl(Config::get('INDEX_FILE'),	array(ActionResolver::ARGS_MEDIA => $mediaType, ActionResolver::ARGS_PATH_ID => $pid)), $path). '</li>';
		}
		$html .= '	</ul>';
		$html .= '</div>';
		return $html;
	}

}