<?php
class MediaMaintenance {

	/**
	 * @var MediaMaintenance
	 * @access private
	 */
	private static $_instance = null;

	/**
	 * @return MediaMaintenance
	 */
	public static function getInstance() {
		if(is_null(self::$_instance)) {
			self::$_instance = new MediaMaintenance();
		}
		return self::$_instance;
	}

	public function showDuplicate() {
		// Récupération de tout les fichiers
		$files = array();
		foreach(Config::getInstance()->getDataFolder(MEDIA_VIDEO) as $path) {
			$files = array_merge($files, $this->getAllFiles($path . DIRECTORY_SEPARATOR));
		}

		// Recherche des doublons
		$datas = array();
		Html::getInstance()->append("<b>Liste des doublons :</b>");
		Html::getInstance()->append("<ul>");
		foreach($files as $fileInfo) {
			$data = MovieHelper::getInstance()->loadData($fileInfo, MovieHelper::DETAIL_FILE);
			if(false !== $data) {
				if(array_key_exists($data["id"], $datas)) {
					Html::getInstance()->append("<li>- SRC: " . $datas[$data["id"]] . " | DUPLICATE: " . $fileInfo->getPath() . "</li>");
				} else {
					$datas[$data["id"]] = $fileInfo->getPath() ;
				}
			}
		}
		Html::getInstance()->append("</ul>");
	}

	public function showUnknown() {
		// Récupération de tout les fichiers
		$files = array();
		foreach(Config::getInstance()->getDataFolder(MEDIA_VIDEO) as $path) {
			$files = array_merge($files, $this->getAllFiles($path . DIRECTORY_SEPARATOR));
		}


		// Recherche des doublons
		Html::getInstance()->append('<div class="listcontent">');
		Html::getInstance()->append('<div class="media_items">');
		foreach($files as $fileInfo) {
			$data = MovieHelper::getInstance()->loadData($fileInfo, MovieHelper::DETAIL_FILE);
			if(false === $data) {
				Html::getInstance()->append(MovieHelper::getInstance()->getMoviePosterDisplay($fileInfo));
			}
		}
		Html::getInstance()->append("</div>");
		Html::getInstance()->append("</div>");
	}

	public function cleanCache() {
		// Récupération de tout les fichiers
		$files = array();
		foreach(Config::getInstance()->getDataFolder(MEDIA_VIDEO) as $path) {
			$files = array_merge($files, $this->getAllFiles($path . DIRECTORY_SEPARATOR));
		}

		// Récupération des ids
		$caches = array();
		foreach($files as $fileInfo) {
			array_push($caches, $fileInfo->getId());
		}

		// Recherche des fichiers de cache inutilisés
		$root = Config::getInstance()->getCacheFolder(MEDIA_VIDEO) . DIRECTORY_SEPARATOR;
		$entries = @scandir($root);
		if(false === $entries) {
			Html::getInstance()->error(I18n::getInstance()->getText(array("error", "scandir"), array("FOLDER" => $root)));
			Html::getInstance()->show();
		}

		$entries = array_diff($entries, FileUtils::getExcludedFiles());
		$cache_deleted = 0;
		foreach ($entries as $entry) {
			$entry_path = $root . $entry;
			if (is_dir($entry_path)) {
				if(!in_array($entry, $caches)) {
					FileUtils::removeDirectory($entry_path);
					$cache_deleted++;
				}
			}
		}
		if($cache_deleted>0) {
			Html::getInstance()->info(I18n::getInstance()->getText(array("config", "cache_deleted"), array("COUNT" => $cache_deleted)));
		}
	}

	/** @return FileInfo */
	public function getAllFiles($root) {
		$files = array();

		$entries = @scandir($root);
		if(false === $entries) {
			Html::getInstance()->error(I18n::getInstance()->getText(array("error", "scandir"), array("FOLDER" => $root)));
			Html::getInstance()->show();
		}

		$entries = array_diff($entries, FileUtils::getExcludedFiles());
		foreach ($entries as $entry) {
			$entry_path = $root . $entry;

			if (is_dir($entry_path)) {
				$files = array_merge($files, $this->getAllFiles($entry_path . DIRECTORY_SEPARATOR));

			} else if(FileUtils::isVideoFile($entry)) {
				array_push($files, new FileInfo($entry, $entry_path));

				// 			} else {
				// 				Html::getInstance()->error("Fichier $entry ignoré");

			}
		}
		return $files;
	}

}
