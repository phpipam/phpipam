<?php

/* @config file ------------------- */
require_once( dirname(__FILE__) . '/../../../config.php' );

/* fix for ajax-loaded windows */
if(!isset($_SESSION)) {
	/* set cookie parameters for max lifetime */
	/*
	ini_set('session.gc_maxlifetime', '86400');
	ini_set('session.save_path', '/tmp/php_sessions/');
	*/
	session_name($phpsessname);
	session_start();
}

/* @database functions ------------------- */
require_once( dirname(__FILE__) . '/dbfunctions.php' );

/* @debugging functions ------------------- */
ini_set('display_errors', 1);
if (!$debugging) { error_reporting(E_ERROR ^ E_WARNING); }
else			 { error_reporting(E_ALL ^ E_NOTICE ^ E_STRICT); }

/* set caching array to store vlans, sections etc */
$cache = array();

/**
 * Translations
 *
 * 	recode .po to .mo > msgfmt phpipam.po -o phpipam.mo
 *	lang codes locale -a
 */

/* Check if lang is set */
if(isset($_SESSION['ipamlanguage'])) {
	if(strlen($_SESSION['ipamlanguage'])>0) 	{
		putenv("LC_ALL=$_SESSION[ipamlanguage]");
		setlocale(LC_ALL, $_SESSION['ipamlanguage']);		// set language
		bindtextdomain("phpipam", "./functions/locale");	// Specify location of translation tables
		textdomain("phpipam");								// Choose domain
	}
}

/* detext missing gettext and fake function */
if(!function_exists(gettext)) {
	function gettext ($text) 	{ return $text; }
	function _($text) 			{ return $text; }
}

/* open persistent DB connection */
$database = new database($db['host'], $db['user'], $db['pass'], $db['name'], NULL, false);
if($database->connect_error) { $dbFail = true; }

/* @general functions ------------------- */
include_once('functions-common.php');

/* @network functions ------------------- */
include_once('functions-network.php');

/* @tools functions --------------------- */
include_once('functions-tools.php');

/* @admin functions --------------------- */
include_once('functions-admin.php');

?>