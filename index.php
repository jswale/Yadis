<?php
session_start ();
include_once 'lib/config.project.php';
@include_once Config::get('CONFIG_PATH') . DIRECTORY_SEPARATOR . Config::get('CONFIG_FILE');

define('PHPVIDEOTOOLKIT_FFMPEG_BINARY', Config::get('FFMPEG_BINARY'));

ActionResolver::getInstance()->resolve();
?>

