<?php
class MovieCategoryHelper {

	const MOVIE_INDEX_FILE = "index.json";

	/**
	 * @var MovieCategoryHelper
	 * @access private
	 */
	private static $_instance = null;

	/**
	 * @return MovieCategoryHelper
	 */
	public static function getInstance() {
		if(is_null(self::$_instance)) {
			self::$_instance = new MovieCategoryHelper();
		}
		return self::$_instance;
	}

	public function show($id = null) {
		$data = FileUtils::loadJson(Config::getInstance()->getCacheFolder(MEDIA_VIDEO), self::MOVIE_INDEX_FILE);
		if(false === $data) {
			Html::getInstance()->error("No index file");
		}

		if(is_null($id)) {
			$html = $this->getListDisplay($data);
		} else {
			$html = $this->getListDetailDisplay($data, $id);
		}

		return TplManager::format("categories.list", array(
				"INDEX_FILE" => Config::get('INDEX_FILE'),
				"ACTION_KEY" => ActionResolver::ARGS_ACTION,
				"ACTION_BUILD_INDEX" => ACTION_BUILD_INDEX,
				"ITEMS" => $html
		));
	}

	public function buildIndex() {
		$files = array();
		foreach(Config::getInstance()->getDataFolder(MEDIA_VIDEO) as $path) {
			$files = array_merge($files, MediaMaintenance::getInstance()->getAllFiles($path . DIRECTORY_SEPARATOR));
		}

		$genres = array();
		$noFilms = 0;

		foreach($files as $fileInfo) {
			$data = MovieHelper::getInstance()->loadData($fileInfo, MovieHelper::DETAIL_FILE);
			if(false !== $data) {
				foreach($data["genres"] as $genre) {
					if(!array_key_exists($genre["id"], $genres)) {
						$genres[$genre["id"]] = array(
								name => $genre["name"],
								movies => array()
						);
					}

					$p = &$genres[$genre["id"]]["movies"];
					if(!array_key_exists($data["id"], $p)) {
						$p[$data["id"]] = array(
								title => $data["title"],
								id => $fileInfo->getId(),
								files => array()
						);
					}
					$path = $fileInfo->getPath();
					foreach(Config::getInstance()->getDataFolder(MEDIA_VIDEO) as $dataPath) {
						if(0 == strpos($path, $dataPath)) {
							$path = substr($path, strlen($dataPath));
							break;
						}
					}
					array_push($p[$data["id"]]["files"], $path);
					$noFilms++;
				}
			}
		}

		FileUtils::saveJson(Config::getInstance()->getCacheFolder(MEDIA_VIDEO), self::MOVIE_INDEX_FILE, $genres);
	}


	private function getListDisplay($data) {
		$genres = array();
		foreach ($data as $genreId => $genre) {
			$genres[$genreId] = $genre["name"];
		}
		asort($genres);

		$html = '';
		$html .= '<div class="listcontent">';
		$html .= '<ul class="folder_items">';
		foreach ($genres as $id => $name) {
			$lib = $name . " (".count($data[$id]["movies"]).")";
			$html .= '<li><div class="separator"></div>' . Utils::getLink(Utils::getUrl(Config::get('INDEX_FILE'),	array(ActionResolver::ARGS_ACTION => ACTION_SHOW_CATEGORIES, ActionResolver::ARGS_ID => $id)), $lib). '</li>';
		}
		$html .= "</ul>";
		$html .= "</div>";

		return $html;
	}


	private function getListDetailDisplay($data, $categoryId) {
		$films = array();
		foreach ($data[$categoryId]["movies"] as $filmId => $film) {
			$films[$film["title"]] = $film;
		}
		ksort($films);

		$html = '';
		$html .= '<div class="listcontent">';
		$html .= '<ul class="folder_items">';
		$html .= '  <li style="text-align:center;font-weight:bold;">'.$data[$categoryId]["name"].'</li>';
		$html .= "</ul>";
		$html .= "<div class='h10'></div>";

		$html .= '<div class="media_items">';
		foreach ($films as $name => $film) {
			$file = $film["files"][0];
			$fileInfo = new FileInfo(basename($file), dirname($file));
			$html .= MovieHelper::getInstance()->getMoviePosterDisplay($fileInfo);
		}
		$html .= "</div>";
		$html .= "</div>";

		return $html;
	}

}