<?php

class MovieHelper {

	const VIEWED_FILE = "viewed.json";
	const SEARCH_FILE = "search.json";
	const CAST_FILE = "cast.json";
	const DETAIL_FILE = "detail.json";
	const IMAGES_FILE = "images.json";
	const KEYWORDS_FILE = "keywords.json";
	const TRAILERS_FILE = "trailers.json";
	const TRANSLATIONS_FILE = "translations.json";
	const CODEC_FILE = "codec.json";

	const DEFAULT_LANGUAGE = "fr";

	/**
	 * @var MovieHelper
	 */
	private static $_instance = null;

	protected $_tmdb;

	public function __construct() {
		$this->_tmdb = new TMDb(Config::get('TMDB_API_KEY'),	self::DEFAULT_LANGUAGE);
	}

	public static function getInstance() {
		if (is_null(self::$_instance)) {
			self::$_instance = new MovieHelper();
		}
		return self::$_instance;
	}

	public function getMovieDetailDisplay($fileInfo) {
		// Gestures
		if(Config::get('STORE_IN_SESSION')) {
			$fileInfos = unserialize($_SESSION[Config::getSessionKey('FolderItems')]);

			$previousFileIdx = $fileInfo->getIndex()-1;
			if($previousFileIdx >= 0) {
				$previousFileInfo = $fileInfos[$previousFileIdx];
				$previousFileInfo->setIndex($previousFileIdx);
				Html::getInstance()->setGesture("RIGHT", Utils::getUrl(Config::get('INDEX_FILE'), array_merge(ActionResolver::getInstance()->getUrlArgs(), array(ActionResolver::ARGS_ACTION => ACTION_DETAIL), $previousFileInfo->getUrlArgs())));
			}

			$nextFileIdx = $fileInfo->getIndex()+1;
			if(count($fileInfos) > $nextFileIdx) {
				$nextFileInfo = $fileInfos[$nextFileIdx];
				$nextFileInfo->setIndex($nextFileIdx);
				Html::getInstance()->setGesture("LEFT", Utils::getUrl(Config::get('INDEX_FILE'), array_merge(ActionResolver::getInstance()->getUrlArgs(), array(ActionResolver::ARGS_ACTION => ACTION_DETAIL), $nextFileInfo->getUrlArgs())));
			}
		}

		// Display
		$search = $this->getOrSearch($fileInfo);
		$results = $search["results"];
		if (count($results) == 0) {
			return MovieDisplayHelper::getInstance()->getMovieSearch($fileInfo);
		}

		return MovieDisplayHelper::getInstance()->getMovieDetail($fileInfo);
	}

	/**
	 * @param FileInfo $fileInfo
	 * @param boolean $cleanBefore
	 * @return string
	 */
	public function getMoviePosterDisplay($fileInfo, $cleanBefore = false) {
		// Suppression des données précédentes
		if ($cleanBefore) {
			$this->cleanData($fileInfo);
		}

		// Recherche du détail en cache
		if ($this->isFileCached($fileInfo, self::DETAIL_FILE)) {
			return MovieDisplayHelper::getInstance()->getMoviePoster($fileInfo);
		}

		// Recherche du resultat de recherche en cache
		$search = $this->getOrSearch($fileInfo);
		if(false === $search) {
			return MovieDisplayHelper::getInstance()->getUnknownMoviePoster($fileInfo);
		}

		$results = $search["results"];
		if (count($results) == 0) {
			return MovieDisplayHelper::getInstance()->getUnknownMoviePoster($fileInfo);
		} else if (count($results) == 1) {
			$this->saveMovie($fileInfo, $results[0]["id"]);
			return MovieDisplayHelper::getInstance()->getMoviePoster($fileInfo);
		} else {
			return MovieDisplayHelper::getInstance()->getMoviePosterTmp($fileInfo, count($results));
		}
	}

	/**
	 * @param FileInfo $fileInfo
	 * @param String $filename
	 * @return boolean
	 */
	public function isFileCached($fileInfo, $filename) {
		return $this->isCached($fileInfo) && file_exists($this->getCachedPath($fileInfo) . DIRECTORY_SEPARATOR . $filename);
	}

	/**
	 * @param FileInfo $fileInfo
	 * @param String $filename
	 * @return mixed
	 */
	public function loadData($fileInfo, $filename, $returnError = false) {
		return FileUtils::loadJson($this->getCachedPath($fileInfo), $filename, $returnError);
	}

	/**
	 * @param FileInfo $fileInfo
	 */
	public function getOrSearch($fileInfo, $alias=null) {
		$search = null;
		if (is_null($alias) && $this->isFileCached($fileInfo, self::SEARCH_FILE)) {
			$search = $this->loadData($fileInfo, self::SEARCH_FILE);
		} else {
			if(Config::getInstance()->isOnline()) {
				$search = $this->getTMDb()->searchMovie(is_null($alias) ? FileUtils::cleanFileName($fileInfo->getName()) : $alias);
				$this->saveData($fileInfo, $search, self::SEARCH_FILE);
			} else {
				return false;
			}
		}
		return $search;
	}

	/**
	 * @param FileInfo $fileInfo
	 * @return boolean
	 */
	public function isViewed($fileInfo) {
		return $this->isFileCached($fileInfo, MovieHelper::VIEWED_FILE);
	}

	/**
	 * @param FileInfo $fileInfo
	 */
	public function switchViewed($fileInfo) {
		if($this->isViewed($fileInfo)) {
			$this->deleteData($fileInfo, self::VIEWED_FILE);
		} else {
			$this->saveData($fileInfo, "{}", self::VIEWED_FILE);
		}
	}

	/**
	 * @param FileInfo $fileInfo
	 */
	public function saveCodecInfos($fileInfo) {
		$path = $fileInfo->getPath();
		$filename = $fileInfo->getName();

		$toolkit = new PHPVideoToolkit($this->getConfig()->getCacheFolder(MEDIA_VIDEO));
		$toolkit->setInputFile(ActionResolver::getInstance()->getKey(ActionResolver::KEY_FULL_PATH) . ($path == DIRECTORY_SEPARATOR ? '' : DIRECTORY_SEPARATOR) . $filename);
		$data = $toolkit->getFileInfo();
		$toolkit->reset();
		$this->saveData($fileInfo, $data, self::CODEC_FILE);
	}

	/**
	 * @param FileInfo $fileInfo
	 * @return string
	 */
	public function getCachedPath($fileInfo) {
		return $this->getConfig()->getCacheFolder(MEDIA_VIDEO) . DIRECTORY_SEPARATOR . $fileInfo->getId();
	}

	/**
	 * @param FileInfo $fileInfo
	 * @param String $id
	 */
	public function saveMovie($fileInfo, $id) {

		// Create folder for the new entry
		$folder = $this->getCachedPath($fileInfo);

		$movieTranslations = $this->getTMDb()->getMovieTranslations($id);
		$this->saveData($fileInfo, $movieTranslations, self::TRANSLATIONS_FILE);

		$translations = $movieTranslations["translations"];
		$lang = "en";
		foreach ($translations as $translation) {
			if ($translation["iso_639_1"] == self::DEFAULT_LANGUAGE) {
				$lang = self::DEFAULT_LANGUAGE;
				break;
			}
		}

		$backdrops = array();
		$posters = array();

		$movieData = $this->getTMDb()->getMovie($id, $lang);
		// 		array_push($backdrops, $movieData["backdrop_path"]);
		array_push($posters, $movieData["poster_path"]);
		$this->saveData($fileInfo, $movieData, self::DETAIL_FILE);

		$movieCast = $this->getTMDb()->getMovieCast($id);
		$this->saveData($fileInfo, $movieCast, self::CAST_FILE);

		$movieKeywords = $this->getTMDb()->getMovieKeywords($id);
		$this->saveData($fileInfo, $movieKeywords, self::KEYWORDS_FILE);

		$movieTrailers = $this->getTMDb()
		->getMovieTrailers($id, self::DEFAULT_LANGUAGE);
		$this->saveData($fileInfo, $movieTrailers, self::TRAILERS_FILE);

		$movieImages = $this->getTMDb()->getMovieImages($id, FALSE);
		foreach ($movieImages["backdrops"] as $movieImage) {
			array_push($backdrops, $movieImage["file_path"]);
			break;
		}
		// 		foreach ($movieImages["posters"] as $movieImage) {
		// 			array_push($posters, $movieImage["file_path"]);
		// 		}
		$this->saveData($fileInfo, $movieImages, self::IMAGES_FILE);

		foreach ($this->getAvailablePosterSizes() as $size) {
			foreach ($posters as $poster) {
				$this->saveImage($fileInfo, $poster, TMDb::IMAGE_POSTER, $size);
			}
		}

		foreach ($this->getAvailableBackdropSizes() as $size) {
			foreach ($backdrops as $backdrop) {
				$this
				->saveImage($fileInfo, $backdrop, TMDb::IMAGE_BACKDROP,
						$size);
			}
		}

		$this->saveCodecInfos($fileInfo);
	}

	public function getTMDb() {
		return $this->_tmdb;
	}

	private function getAvailablePosterSizes() {
		return array("w92", "w185");
		// 		return $this->getAvailableImagesSizes("poster_sizes");
	}

	private function getAvailableBackdropSizes() {
		return array("w1280");
		// 		return $this->getAvailableImagesSizes("backdrop_sizes");
	}

	// 	private function getAvailableImagesSizes($key) {
	// 		$config = $this->getTMDb()->getConfig();
	// 		return array_values($config["images"][$key]);
	// 	}

	private function getConfig() {
		return Config::getInstance();
	}

	/**
	 * @param FileInfo $fileInfo
	 * @return boolean
	 */
	private function isCached($fileInfo) {
		return file_exists($this->getCachedPath($fileInfo));
	}

	/**
	 * @param FileInfo $fileInfo
	 * @param String $filename
	 * @param String $type
	 * @param String $size
	 */
	private function saveImage($fileInfo, $filename, $type, $size) {
		// Build URL
		$url = $this->getTMDb()->getImageUrl($filename, $type, $size);

		// Get the folder
		$folder = $this->getCachedPath($fileInfo) . DIRECTORY_SEPARATOR . $size;

		// Create the folder
		FileUtils::createFolder($folder);

		// Download file
		FileUtils::writeFileFromUrl($url, $folder, $filename);
	}

	/**
	 * @param FileInfo $fileInfo
	 */
	private function cleanData($fileInfo) {
		if ($this->isCached($fileInfo)) {
			FileUtils::removeDirectory($this->getCachedPath($fileInfo));
		}
	}

	/**
	 * @param FileInfo $fileInfo
	 * @param String $data
	 * @param String $filename
	 */
	private function saveData($fileInfo, $data, $filename) {
		FileUtils::saveJson($this->getCachedPath($fileInfo), $filename, $data);
	}

	/**
	 * @param FileInfo $fileInfo
	 * @param String $filename
	 */
	private function deleteData($fileInfo, $filename) {
		FileUtils::deleteFile($this->getCachedPath($fileInfo), $filename);
	}
}
