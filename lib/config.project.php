<?php
function __autoload($class_name) {
	include_once 'lib/classes/'. $class_name . '.php';
}

// Display mode
define("DISPLAY_CONFIG", 1);
define("DISPLAY_FOLDER", 2);
define("DISPLAY_DETAIL", 3);
define("DISPLAY_CHOOSE", 4);
define("DISPLAY_FOLDER_CHOOSE", 5);
define("DISPLAY_MEDIA_CHOOSE", 6);
define("DISPLAY_DETAIL_SEARCH", 7);
define("DISPLAY_DUPLICATE", 8);
define("DISPLAY_UNKNOWN", 9);
define("DISPLAY_CATEGORIES", 10);

// Actions
define("ACTION_CONFIG", 'cfg');
define("ACTION_CONFIG_SAVE", 'cfg_save');
define("ACTION_SEARCH_DUPLICATE", 'sd');
define("ACTION_CLEAN_CACHE", 'cc');
define("ACTION_BUILD_INDEX", 'bi');
define("ACTION_CHOOSE", 'c');
define("ACTION_DETAIL", 'd');
define("ACTION_REFRESH", 'r');
define("ACTION_DISCONNECT", 't');
define("ACTION_DETAIL_CHOOSE", 'dc');
define("ACTION_REFRESH_CODEC", 'rc');
define("ACTION_DETAIL_SEARCH", 'ds');
define("ACTION_DETAIL_SWITCH_VIEWED", 'sv');
define("ACTION_DETAIL_SWITCH_VIEWED", 'sv');
define("ACTION_SHOW_CATEGORIES", 'sc');

// Media types
define("MEDIA_VIDEO", 'video');
define("MEDIA_SERIE", 'serie');
