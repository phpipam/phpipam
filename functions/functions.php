<?php

/* @config file ------------------ */
require_once( dirname(__FILE__) . '/../config.php' );

/* @http only cookies ------------------- */
ini_set('session.cookie_httponly', 1);

/* @debugging functions ------------------- */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
if (!$debugging) { error_reporting(E_ERROR ^ E_WARNING); }
else			 { error_reporting(E_ALL ^ E_NOTICE ^ E_STRICT); }

/**
 * detect missing gettext and fake function
 */
if(!function_exists('gettext')) {
	function gettext ($text) 	{ return $text; }
	function _($text) 			{ return $text; }
}

// auto-set base if not already defined
if(!defined('BASE')) {
	$root = substr($_SERVER['DOCUMENT_ROOT'],-1)=="/" ? substr($_SERVER['DOCUMENT_ROOT'],0,-1) : $_SERVER['DOCUMENT_ROOT'];	// fix for missing / in some environments
	define('BASE', substr(str_replace($root, "", dirname(__FILE__)),0,-9));
}

// Fix JSON_UNESCAPED_UNICODE for PHP 5.3
defined('JSON_UNESCAPED_UNICODE') or define('JSON_UNESCAPED_UNICODE', 256);

/* @classes ---------------------- */
require( dirname(__FILE__) . '/classes/class.Common.php' );		//Class common - common functions
require( dirname(__FILE__) . '/classes/class.PDO.php' );		//Class PDO - wrapper for database
require( dirname(__FILE__) . '/classes/class.User.php' );		//Class for active user management
require( dirname(__FILE__) . '/classes/class.Log.php' );		//Class for log saving
require( dirname(__FILE__) . '/classes/class.Result.php' );		//Class for result printing
require( dirname(__FILE__) . '/classes/class.Install.php' );	//Class for Install
require( dirname(__FILE__) . '/classes/class.Sections.php' );	//Class for sections
require( dirname(__FILE__) . '/classes/class.Subnets.php' );	//Class for subnets
require( dirname(__FILE__) . '/classes/class.Tools.php' );		//Class for tools
require( dirname(__FILE__) . '/classes/class.Addresses.php' );	//Class for addresses
require( dirname(__FILE__) . '/classes/class.Scan.php' );		//Class for Scanning and pinging
require( dirname(__FILE__) . '/classes/class.DNS.php' );		//Class for DNS management
require( dirname(__FILE__) . '/classes/class.PowerDNS.php' );	//Class for PowerDNS management
require( dirname(__FILE__) . '/classes/class.FirewallZones.php' );	//Class for firewall zone management
require( dirname(__FILE__) . '/classes/class.Admin.php' );		//Class for Administration
require( dirname(__FILE__) . '/classes/class.Mail.php' );		//Class for Mailing
require( dirname(__FILE__) . '/classes/class.Rackspace.php' );	//Class for Racks
require( dirname(__FILE__) . '/classes/class.SNMP.php' );	    //Class for SNMP queries
require( dirname(__FILE__) . '/classes/class.DHCP.php' );	    //Class for DHCP

# save settings to constant
if(@$_GET['page']!="install" ) {
	# database object
	$Database 	= new Database_PDO;
	# try to fetch settings
	try { $settings = $Database->getObject("settings", 1); }
	catch (Exception $e) { $settings = false; }
	if ($settings!==false) {
		if (phpversion() < "5.4") {
			define('SETTINGS', json_encode($settings));
		}
		else{
			define('SETTINGS', json_encode($settings, JSON_UNESCAPED_UNICODE));
		}
	}
}

/**
 * create links function
 *
 *	if rewrite is enabled in settings use rewrite, otherwise ugly links
 *
 *	levels: $el
 */
function create_link ($l0 = null, $l1 = null, $l2 = null, $l3 = null, $l4 = null, $l5 = null, $l6 = null ) {
	# get settings
	global $User;

	# set normal link array
	$el = array("page", "section", "subnetId", "sPage", "ipaddrid", "tab");
	// override for search
	if ($l0=="tools" && $l1=="search")
    $el = array("page", "section", "ip", "addresses", "subnets", "vlans", "ip");

	# set rewrite
	if($User->settings->prettyLinks=="Yes") {
		if(!is_null($l6))		{ $link = "$l0/$l1/$l2/$l3/$l4/$l5/$l6"; }
		elseif(!is_null($l5))	{ $link = "$l0/$l1/$l2/$l3/$l4/$l5/"; }
		elseif(!is_null($l4))	{ $link = "$l0/$l1/$l2/$l3/$l4/"; }
		elseif(!is_null($l3))	{ $link = "$l0/$l1/$l2/$l3/"; }
		elseif(!is_null($l2))	{ $link = "$l0/$l1/$l2/"; }
		elseif(!is_null($l1))	{ $link = "$l0/$l1/"; }
		elseif(!is_null($l0))	{ $link = "$l0/"; }
		else					{ $link = ""; }

		# IP search fix
		if ($l0=="tools" && $l1=="search" && isset($l2) && substr($link,-1)=="/") {
    		$link = substr($link, 0, -1);
		}
	}
	# normal
	else {
		if(!is_null($l6))		{ $link = "?$el[0]=$l0&$el[1]=$l1&$el[2]=$l2&$el[3]=$l3&$el[4]=$l4&$el[5]=$l5&$el[6]=$l6"; }
		elseif(!is_null($l5))	{ $link = "?$el[0]=$l0&$el[1]=$l1&$el[2]=$l2&$el[3]=$l3&$el[4]=$l4&$el[5]=$l5"; }
		elseif(!is_null($l4))	{ $link = "?$el[0]=$l0&$el[1]=$l1&$el[2]=$l2&$el[3]=$l3&$el[4]=$l4"; }
		elseif(!is_null($l3))	{ $link = "?$el[0]=$l0&$el[1]=$l1&$el[2]=$l2&$el[3]=$l3"; }
		elseif(!is_null($l2))	{ $link = "?$el[0]=$l0&$el[1]=$l1&$el[2]=$l2"; }
		elseif(!is_null($l1))	{ $link = "?$el[0]=$l0&$el[1]=$l1"; }
		elseif(!is_null($l0))	{ $link = "?$el[0]=$l0"; }
		else					{ $link = ""; }
	}
	# prepend base
	$link = BASE.$link;

	# result
	return $link;
}

/* get version */
include('version.php');
