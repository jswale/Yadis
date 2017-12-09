<?php
class FileUtils {
	public static function createFolder($folder) {
		if (!file_exists($folder)) {
			if (!@mkdir($folder, 0777, true)) {
				Html::getInstance()->stop(I18n::getInstance()->getText(array("error", "mkdir"), array("FOLDER" => $folder)));
			} else {
				// folder already exist
			}
		}
	}

	public static function writeFile($folder, $filename, $data) {
		$file = $folder . DIRECTORY_SEPARATOR . $filename;
		self::createFolder($folder);
		$fp = @fopen($file, 'w');
		if(false === $fp) {
			Html::getInstance()->stop(I18n::getInstance()->getText(array("error", "writeFile"), array("FILE" => $file)));
		}
		fwrite($fp, $data);
		fclose($fp);
	}

	public static function deleteFile($folder, $filename) {
		$file = $folder . DIRECTORY_SEPARATOR . $filename;
		if(false === @unlink($file)) {
			Html::getInstance()->stop(I18n::getInstance()->getText(array("error", "unlink"), array("FILE" => $file)));
		}
	}

	public static function writeFileFromUrl($url, $folder, $filename) {
		if(false === @file_put_contents($folder . $filename, file_get_contents($url))) {
			Html::getInstance()->stop(I18n::getInstance()->getText(array("error", "writeFile"), array("FILE" => $folder . $filename)));
		}
	}

	public static function loadJson($folder, $filename, $returnError = false) {
		$file = $folder . DIRECTORY_SEPARATOR . $filename;

		// Get the data
		$json = @file_get_contents($file);
		if(false === $json) {
			if(false === $returnError) {
				return false;
			}else {
				Html::getInstance()->stop(I18n::getInstance()->getText(array("error", "readFile"), array("FILE" => $file)));
			}
		}

		// Convert the data to array
		return json_decode($json, TRUE);
	}

	public static function saveJson($folder, $filename, $data) {
		self::writeFile($folder, $filename, json_encode($data));
	}

	public static function removeDirectory($dir) {
		if (!file_exists($dir)) {
			return false;
		}
		$files = array_diff(scandir($dir), array('.', '..'));
		foreach ($files as $file) {
			(is_dir("$dir/$file")) ? self::removeDirectory("$dir/$file")
					: unlink("$dir/$file");
		}
		return rmdir($dir);
	}

	public static function cleanFileName($filename) {
		$filename = strtolower($filename);

		$filename = str_replace(
				array(".avi", ".mkv", ".mpg", ".mov", ".divx", ".mp4"), "",
				$filename);
		$filename = str_replace(array("_", ".", "+", "-", "[", "]", "(", ")"),
				" ", $filename);
		$filename = str_replace(
				array("dvdrip", "truefrench", "french", "xvid", "brrip", "ac3",
						"divx", "vostfr", "hdtv", "x264", "bluray", "dts",
						"multi"), " ", $filename);

		$filename = preg_replace('#\(.+\)#i', '', $filename);
		$filename = preg_replace(
				'#((19)[0-9]{2})|((200)[0-9]{1})|((201)[0-9]{1}).*#', '',
				$filename); //supprime les annees 19xx a 2019
		$filename = preg_replace('#((cd ?)[1-9]{1}).*#', '', $filename);
		$filename = preg_replace('#((dvd ?)[1-9]{1}).*#', '', $filename);
		$filename = preg_replace("#1080p.*#si", "", $filename);
		$filename = preg_replace("#720p.*#si", "", $filename);

		$filename = trim($filename);

		return $filename;
	}

	public static function getExcludedFiles() {
		return array(".", "..", "Thumbs.db", "@eaDir", ".DS_Store");
	}

	public static function isVideoFile($filename) {
		$info = pathinfo($filename);
		return in_array(strtolower($info["extension"]),
				array("avi", "mkv", "mpg", "mov", "divx", "mp4"));
	}

	public static function downloadFile($url, $path) {
		$fp = fopen($path, 'w');
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_FILE, $fp);
		$data = curl_exec($ch);
		curl_close($ch);
		fclose($fp);
	}
}
