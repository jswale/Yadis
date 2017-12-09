<?php
class MovieDisplayHelper {

	/**
	 * @var MovieDisplayHelper
	 */
	private static $_instance = null;

	public static function getInstance() {
		if (is_null(self::$_instance)) {
			self::$_instance = new MovieDisplayHelper();
		}
		return self::$_instance;
	}

	/**
	 * @param FileInfo $fileInfo
	 * @param integer $noFilms
	 * @return string
	 */
	public function getMoviePosterTmp($fileInfo, $noFilms) {
		return TplManager::format("movie.poster.tmp", array(
				"FILE_NAME" => $fileInfo->getName(),
				"NO_FILMS" => $noFilms,
				"ACTION_DETAIL_CHOOSE_URL" => Utils::getUrl(Config::get('INDEX_FILE'), array_merge(ActionResolver::getInstance()->getUrlArgs(), array(ActionResolver::ARGS_ACTION => ACTION_DETAIL_CHOOSE), $fileInfo->getUrlArgs()), true),
				"ACTION_DETAIL_SEARCH_URL" => Utils::getUrl(Config::get('INDEX_FILE'), array_merge(ActionResolver::getInstance()->getUrlArgs(), array(ActionResolver::ARGS_ACTION => ACTION_DETAIL_SEARCH), $fileInfo->getUrlArgs()), true)
		));
	}

	/**
	 * @param FileInfo $fileInfo
	 * @return string
	 */
	public function getUnknownMoviePoster($fileInfo) {
		return TplManager::format("movie.poster.unknown", array(
				"FILE_NAME" => $fileInfo->getName(),
				"ACTION_DETAIL_SEARCH_URL" => Utils::getUrl(Config::get('INDEX_FILE'), array_merge(ActionResolver::getInstance()->getUrlArgs(), array(ActionResolver::ARGS_ACTION => ACTION_DETAIL_SEARCH), $fileInfo->getUrlArgs()), true)
		));
	}


	/**
	 * @param FileInfo $fileInfo
	 * @return string
	 */
	public function getMovieSearch($fileInfo, $query=null) {

		if(!is_null($query)) {
			$search_result = $this->getMoviePosterChoices($fileInfo, $query);
			if(empty($search_result)) {
				$search_result = I18n::getInstance()->getText(array("movie", "search", "nomatch"), array("QUERY" => $query));
			}
		} else {
			$search_result = I18n::getInstance()->getText(array("movie", "search", "help"));
		}

		return TplManager::format("movie.search", array(
				"FILE_NAME" => $fileInfo->getName(),
				"SEARCH" => is_null($query) ? FileUtils::cleanFileName($fileInfo->getName()) : $query,
				"SEARCH_URL" => Utils::getUrl(Config::get('INDEX_FILE'), array_merge(ActionResolver::getInstance()->getUrlArgs(), array(ActionResolver::ARGS_ACTION => ACTION_DETAIL_SEARCH), $fileInfo->getUrlArgs())),
				"SEARCH_ARG" => ActionResolver::ARGS_SEARCH_VALUE,
				"SEARCH_RESULT" => $search_result
		));
	}


	/**
	 * @param FileInfo $fileInfo
	 * @return string
	 */
	public function getMoviePoster($fileInfo) {
		$movieDetail = MovieHelper::getInstance()->loadData($fileInfo, MovieHelper::DETAIL_FILE);

		$viewed = MovieHelper::getInstance()->isViewed($fileInfo);

		$genres = array();
		foreach ($movieDetail["genres"] as $genre) {
			array_push($genres, Utils::getLink(Utils::getUrl(Config::get('INDEX_FILE'),	array(ActionResolver::ARGS_ACTION => ACTION_SHOW_CATEGORIES, ActionResolver::ARGS_ID => $genre["id"])), $genre["name"]));
		}
		sort($genres);

		return TplManager::format("movie.poster", array(
				"FILE_NAME" => $fileInfo->getName(),
				"MOVIE_VIEWED" => $viewed ? "viewed" : "",
				"POSTER_SRC" => $this->getImagePath($fileInfo, $movieDetail["poster_path"], "w92"),
				"MOVIE_TITLE" => $movieDetail["title"],
				"MOVIE_DATE" => $this->getYear($movieDetail["release_date"]),
				"MOVIE_GENRES" => implode(", ", $genres),
				"MOVIE_SYNOPSIS" => Utils::truncate($movieDetail["overview"], 175, '...'),
				"ACTION_VIEW_SWITCH_IMG" => $viewed ? "unstar" : "star",
				"ACTION_VIEW_SWITCH_URL" => Utils::getUrl(Config::get('INDEX_FILE'), array_merge(ActionResolver::getInstance()->getUrlArgs(), $fileInfo->getUrlArgs(), array(ActionResolver::ARGS_ACTION => ACTION_DETAIL_SWITCH_VIEWED, ActionResolver::ARGS_STAY_IN_FOLDER => true, ActionResolver::ARGS_ANCHOR => $fileInfo->getName()))),

				"ACTION_DETAIL_URL" => Utils::getUrl(Config::get('INDEX_FILE'), array_merge(ActionResolver::getInstance()->getUrlArgs(), array(ActionResolver::ARGS_ACTION => ACTION_DETAIL), $fileInfo->getUrlArgs())),
				"ACTION_REFRESH_URL" => Utils::getUrl(Config::get('INDEX_FILE'), array_merge(ActionResolver::getInstance()->getUrlArgs(), array(ActionResolver::ARGS_ACTION => ACTION_REFRESH, ActionResolver::ARGS_SOURCE_ID => $movieDetail["id"]), $fileInfo->getUrlArgs())),
				"ACTION_REFRESH_CODEC_URL" => Utils::getUrl(Config::get('INDEX_FILE'), array_merge(ActionResolver::getInstance()->getUrlArgs(), array(ActionResolver::ARGS_ACTION => ACTION_REFRESH_CODEC, ActionResolver::ARGS_SOURCE_ID => $movieDetail["id"]), $fileInfo->getUrlArgs())),
				"ACTION_DISCONNECT_URL" => Utils::getUrl(Config::get('INDEX_FILE'), array_merge(ActionResolver::getInstance()->getUrlArgs(), array(ActionResolver::ARGS_ACTION => ACTION_DISCONNECT), $fileInfo->getUrlArgs()))
		));
	}

	/**
	 * @param FileInfo $fileInfo
	 * @return string
	 */
	public function getMoviePosterChoice($fileInfo, $alias=null) {
		return TplManager::format("movie.poster.choose", array(
				"FILE_NAME" => $fileInfo->getName(),
				"ITEMS" => $this->getMoviePosterChoices($fileInfo, $alias)
		));
	}

	/**
	 * @param FileInfo $fileInfo
	 * @param unknown_type $file
	 * @return string
	 */
	public function getMovieDetail($fileInfo) {
		// --- Movie Informations
		$movieDetail = MovieHelper::getInstance()->loadData($fileInfo, MovieHelper::DETAIL_FILE);
		if(false === $movieDetail) {
			return $this->getMoviePosterChoice($fileInfo);
		}

		$isOnline = Config::getInstance()->isOnline();
		$backdropSwitchEnable = Config::get('JS_BACKDROP_SWITCH') && $isOnline;

		// --- Genres
		$genres = array();
		foreach ($movieDetail["genres"] as $genre) {
			array_push($genres, Utils::getLink(Utils::getUrl(Config::get('INDEX_FILE'),	array(ActionResolver::ARGS_ACTION => ACTION_SHOW_CATEGORIES, ActionResolver::ARGS_ID => $genre["id"])), $genre["name"]));
		}
		sort($genres);

		// --- Subtitle Information
		$subtitleFiles = array();
		{
			$info = pathinfo($fileInfo->getName());
			$filename = $info["filename"];
			$extension = $info["extension"];
			if(file_exists(ActionResolver::getInstance()->getKey(ActionResolver::KEY_FULL_PATH) . DIRECTORY_SEPARATOR . $filename . ".srt")) {
				array_push($subtitleFiles, MovieHelper::DEFAULT_LANGUAGE);
			}
			foreach(I18n::getInstance()->getI18n() as $key => $value) {
				if(file_exists(ActionResolver::getInstance()->getKey(ActionResolver::KEY_FULL_PATH) . DIRECTORY_SEPARATOR . $filename . "." . $key . ".srt")) {
					array_push($subtitleFiles, $key);
				}
			}
		}

		// --- Casting Informations
		$castDetail = MovieHelper::getInstance()->loadData($fileInfo, MovieHelper::CAST_FILE);
		$casts = array();
		foreach ($castDetail["cast"] as $cast) {
			if(count($casts) == 7) {
				array_push($casts, "...");
				break;
			}
			array_push($casts, $cast["character"] . " (" . $cast["name"] . ")");
		}

		$directings = array();
		foreach ($castDetail["crew"] as $directing) {
			if($directing["job"] == "Director") {
				array_push($directings, $directing["name"]);
			}
		}

		// --- Codec Informations
		$codecDetail = MovieHelper::getInstance()->loadData($fileInfo, MovieHelper::CODEC_FILE);
		$codecInfos = $this	->getVideoCodecInfo($codecDetail);

		// --- Images Informations
		$imagesDetail = MovieHelper::getInstance()->loadData($fileInfo, MovieHelper::IMAGES_FILE);
		$backdrops = $imagesDetail["backdrops"];
		if(count($backdrops) == 0) {
			$backdropSwitchEnable = false;
		}
		usort($backdrops, function($a, $b){
			return $a["vote_average"] < $b["vote_average"];
		});

		$urls = array();
		if($backdropSwitchEnable){
			foreach($backdrops as $backdrop) {
				array_push($urls, MovieHelper::getInstance()->getTMDb()->getImageUrl($backdrop["file_path"], TMDb::IMAGE_BACKDROP, "w1280"));
			}
		}

		$codecs = array();
		array_push($codecs, $this->getCodec($codecInfos["video_codec"], $codecInfos["resolution"]));
		if(null != $codecInfos["video_lng"]) {
			array_push($codecs, $this->getCodec(I18n::getInstance()->getText(array("movie", "detail", "video_lng")), $codecInfos["video_lng"]));
		}
		if(null != $codecInfos["video_subtitles"]) {
			array_push($codecs, $this->getCodec(I18n::getInstance()->getText(array("movie", "detail", "video_lng")), implode(", ", $codecInfos["video_subtitles"])));
		}
		if(0 < count($subtitleFiles)) {
			array_push($codecs, $this->getCodec(I18n::getInstance()->getText(array("movie", "detail", "subtitles")), implode(", ", $subtitleFiles)));
		}
		array_push($codecs, $this->getCodec($codecInfos["display_aspect_ratio"]));
		array_push($codecs, $this->getCodec($codecInfos["audio_codec"]));

		// Is the film viewed
		$viewed = MovieHelper::getInstance()->isViewed($fileInfo);

		// Display
		return TplManager::format("movie.detail", array(
				"BACKDROP_SWITCH" => $backdropSwitchEnable ? "true" : "false",
				"BACKDROPS_URLS" => json_encode($urls),
				"BACKDROP_SRC" => !empty($movieDetail["backdrop_path"]) ? $this->getImagePath($fileInfo, $movieDetail["backdrop_path"], "w1280") : 'ressources/img/background.jpg',
				"POSTER_SRC" => $this->getImagePath($fileInfo, $movieDetail["poster_path"], "w185"),

				// 				"VIDEO_STATION_DISPLAY" => Config::get("DISKSTATION") ? "" : "none",
		// 				"VIDEO_STATION_URL" => "http://" . $_SERVER["HTTP_HOST"] . ":5000/webman/index.cgi?launchApp=SYNO.SDS.VideoPlayer.Application&launchParam=requestURL%3D%252Fwebapi%252FVideoStation%252Fvtestreaming.cgi%253Fapi%253DSYNO.VideoStation.Streaming%2526method%253Dopen%2526version%253D1%2526id%253D3635%2526accept_format%253Draw%26urlParams%3D%257B%2522stream_id%2522%253A%2522id%2522%252C%2522format%2522%253A%2522format%2522%257D%26url%3D%252Fwebapi%252FVideoStation%252Fvtestreaming.cgi%253Fapi%253DSYNO.VideoStation.Streaming%2526method%253Dstream%2526version%253D1%2526_sid%253DhYSG911HfJWTY%26closeParams%3D%257B%2522stream_id%2522%253A%2522id%2522%252C%2522format%2522%253A%2522format%2522%257D%26closeURL%3D%252Fwebapi%252FVideoStation%252Fvtestreaming.cgi%253Fapi%253DSYNO.VideoStation.Streaming%2526method%253Dclose%2526version%253D1%2526force_close%253Dtrue%26path%3D" . urlencode(urlencode($fileInfo->getPath()) . $fileInfo->getName()),

				"MOVIE_VIEWED" => $viewed ? "viewed" : "",
				"MOVIE_VIEW_SWITCH_URL" => Utils::getUrl(Config::get('INDEX_FILE'), array_merge(ActionResolver::getInstance()->getUrlArgs(), $fileInfo->getUrlArgs(), array(ActionResolver::ARGS_ACTION => ACTION_DETAIL_SWITCH_VIEWED))),

				"MOVIE_TITLE" => $movieDetail["title"],
				"MOVIE_ORIGINAL_TITLE" => $movieDetail["original_title"],
				"MOVIE_RELEASE_DATE" => $this->getYear($movieDetail["release_date"]),
				"MOVIE_DURATION" => gmdate("H\hi", intval($codecDetail["duration"]["seconds"],10)),
				"MOVIE_RATING" => $this->getVote($movieDetail["vote_average"]),
				"MOVIE_IMDB_ID" => $movieDetail["imdb_id"],
				"MOVIE_GENRES" => implode(" / ", $genres),
				"MOVIE_CAST" => implode("<br/>", $casts),
				"MOVIE_DIRECTING" => implode("<br/>", $directings),
				"MOVIE_SYNOPSIS" => $movieDetail["overview"],
				"MOVIE_CODECS" => implode("", $codecs),
		));
	}

	/**
	 * @param FileInfo $fileInfo
	 * @param string $alias
	 * @return string
	 */
	private function getMoviePosterChoices($fileInfo, $alias) {
		$items = array();

		$search = MovieHelper::getInstance()->getOrSearch($fileInfo, $alias);
		if(false !== $search) {
			$results = $search["results"];
			foreach ($results as $movie) {
				array_push($items, TplManager::format("movie.poster.choose.item", array(
						"MOVIE_TITLE" => $movie["title"],
						"POSTER_SRC" => empty($movie["poster_path"]) ? "ressources/img/no-poster-w92.jpg" : MovieHelper::getInstance()->getTMDb()->getImageUrl($movie["poster_path"], TMDb::IMAGE_POSTER, 'w92'),
						"MOVIE_DATE" => $this->getYear($movie["release_date"]),
						"ACTION_CHOOSE_URL" => Utils::getUrl(Config::get('INDEX_FILE'), array_merge(ActionResolver::getInstance()->getUrlArgs(), array(ActionResolver::ARGS_ACTION => ACTION_CHOOSE, ActionResolver::ARGS_SOURCE_ID=>$movie['id']), $fileInfo->getUrlArgs()))
				)));
			}
		}
		return implode("", $items);
	}

	/**
	 * @param string $left
	 * @param string $right
	 * @return string
	 */
	private function getCodec($left, $right = null) {
		if(null == $left && null == $right) {
			return '';
		}

		return TplManager::format("movie.detail.codec", array(
				"LEFT" => $left,
				"LEFT_DISPLAY" => null == $left ? "none" : "",
				"RIGHT" => $right,
				"RIGHT_DISPLAY" => null == $right ? "none" : "",
		));

	}

	/**
	 * @param String $vote_average
	 * @return string
	 */
	private function getVote($vote_average) {
		$vote = round($vote_average);
		$html = '';
		for($i = 1; $i <= 10; $i++) {
			$html .= '<div title="'.$vote_average.'/10" class="ux-rating-star' . ($i > $vote ? " ux-rating-star-off" : '') . '"></div>';
		}
		return $html;
	}

	/**
	 * @param FileInfo $fileInfo
	 * @param unknown_type $filename
	 * @param unknown_type $size
	 * @return string
	 */
	private function getImagePath($fileInfo, $filename, $size) {
		return Config::get('CACHE_VIDEO_PATH')  . DIRECTORY_SEPARATOR . $fileInfo->getId() . DIRECTORY_SEPARATOR . $size . $filename;
	}

	/**
	 * @param String $date
	 * @return String
	 */
	private function getYear($date) {
		$a = explode("-", $date);
		return array_shift($a);
	}

	private function getVideoCodecInfo($codecDetail) {
		$_resolutions = array(
				"426x240" => "240p",
				"640x360" => "360p",
				"854x480" => "480p",
				"1280x720" => "720p",
				"1920x1080" => "1080p"
		);

		$_ratios = array(
				"177" => "16:9",
				"133" => "4:3"
		);

		$w = $codecDetail["video"]["dimensions"]["width"];
		$h = $codecDetail["video"]["dimensions"]["height"];
		$ratio = null;
		$resolution = null;
		if(!( empty($w) || empty($h) ) ) {
			$ratio = floor(100*$w/$h);
			$ratio = array_key_exists("$ratio", $_ratios) ? $_ratios[$ratio] : ($ratio/100 . ":1");
			$resolution = $_resolutions[$w."x".$h];
		}

		$audio_codec = $codecDetail["audio"]["codec"];
		$video_codec = $codecDetail["video"]["codec"];
		$video_lng = $codecDetail["video"]["lng"];
		$video_subtitles = $codecDetail["subtitles"];

		return array(
				"height" => $h,
				"width" => $w,
				"resolution" => $resolution,
				"display_aspect_ratio" => $ratio,
				"video_codec" => $video_codec,
				"video_lng" => $video_lng,
				"audio_codec" => $audio_codec,
				"video_subtitles" => $video_subtitles
		);
	}
}
