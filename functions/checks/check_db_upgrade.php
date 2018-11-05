<?php

/**
 * Check if database needs upgrade to newer version
 ****************************************************/

# use required functions
if(!isset($User->settings->dbversion)) {
	$User->settings->dbversion = 0;
}

/* redirect */
if($User->cmp_version_strings($User->settings->version.'.'.$User->settings->dbversion,VERSION.'.'.DBVERSION) < 0) {
	$User->settings->prettyLinks="No";
	header("Location: ".create_link("upgrade"));
	die();
}