<?php
/* set latest version */
define("VERSION", "1.5");									//decimal release version e.g 1.32
/* set latest version */
define("VERSION_VISIBLE", "1.5");							//visible version in footer e.g 1.3.2
/* set latest revision */
define("REVISION", "011");									//increment on static content changes (js/css) or point releases to avoid caching issues
/* set last possible upgrade */
define("LAST_POSSIBLE", "1.19");							//minimum required version to be able to upgrade
/* set published - hide dbversion in footer */
define("PUBLISHED", false);									//hide dbversion in footer

// Automatically set DBVERSION as everyone forgets!
function get_dbversion() {
    require('upgrade_queries.php');
    $upgrade_keys = array_keys($upgrade_queries);
    return str_replace(VERSION.".", "", end($upgrade_keys));
}

if(!defined('DBVERSION'))
define('DBVERSION', get_dbversion());

/* prefix for css/js */
define("SCRIPT_PREFIX", VERSION_VISIBLE.'_r'.REVISION.'_v'.DBVERSION);		//css and js folder prefix to prevent caching issues