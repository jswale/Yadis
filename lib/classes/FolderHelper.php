<?php
class FolderHelper {

	/**
	 * @var FolderHelper
	 * @access private
	 */
	private static $_instance = null;

	/**
	 * @return FolderHelper
	 */
	public static function getInstance() {
		if(is_null(self::$_instance)) {
			self::$_instance = new FolderHelper();
		}
		return self::$_instance;
	}

	public function getFolderDisplay() {
		// Variables
		$files = array();
		$folders = array();

		// Get root path
		$path = ActionResolver::getInstance()->getKey(ActionResolver::KEY_PATH);
		$root = ActionResolver::getInstance()->getKey(ActionResolver::KEY_FULL_PATH);

		if(!file_exists($root)) {
			Html::getInstance()->error(I18n::getInstance()->getText(array("error", "unknown_folder"), array("FOLDER" => $root)));
			return;
		}

		// Scan for entries
		$entries = @scandir($root);
		if(false === $entries) {
			Html::getInstance()->error(I18n::getInstance()->getText(array("error", "scandir"), array("FOLDER" => $root)));
			return;
		}
		$entries = array_diff($entries, FileUtils::getExcludedFiles());
		foreach ($entries as $entry) {
			// Parcours des fichiers
			$entry_path = $root . $entry;
			if (is_dir($entry_path)) {
				array_push($folders, $entry);

			} else if(FileUtils::isVideoFile($entry)) {
				array_push($files, new FileInfo($entry, $path));
			}
		}

		$html = '';
		$html .= '<div class="listcontent">';

		// Affichage des répertoires
		if (count($folders) > 0) {
			asort($folders, SORT_LOCALE_STRING);
			$html .= '<ul class="folder_items">';
			foreach ($folders as $folder) {
				$html .= '<li><div class="separator"></div>' . Utils::getLink(Utils::getUrl(Config::get('INDEX_FILE'),	array_merge(ActionResolver::getInstance()->getUrlArgs(), array(ActionResolver::ARGS_PATH => $path . $folder . DIRECTORY_SEPARATOR))), $folder). '</li>';
			}
			$html .= "</ul>";
			$html .= "<div class='h10'></div>";
		}

		// Affichage des fichiers
		if (count($files) > 0) {
			usort($files, function($a, $b){
				return strcmp($a->getName(),$b->getName());
			});
			if(Config::get('STORE_IN_SESSION')) {
				$_SESSION[Config::getSessionKey('FolderItems')] = serialize($files);
			}

			$html .= '<div class="media_items">';
			foreach ($files as $index => $fileInfo) {
				$fileInfo->setIndex($index);
				$html .= MovieHelper::getInstance()->getMoviePosterDisplay($fileInfo);
			}
			$html .= '</div>';
		} else {
			$html .= "<div class='h10'></div>";
			$html .= '<div class="help">' . I18n::getInstance()->getText(array("msg", "emptyfolder")) . '</div>';
		}
		$html .= '</div>';

		return $html;
	}

}