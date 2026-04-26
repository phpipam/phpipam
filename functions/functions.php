<?php

/* global and missing functions */
require_once('global_functions.php');

/* Enable output buffering */
require_once( __DIR__ . '/output_buffering.php' );

/* @config file ------------------ */
require_once( __DIR__ . '/classes/class.Config.php' );
$config = Config::ValueOf('config');

/**
 * proxy to use for every internet access like update check
 ******************************/
if (Config::ValueOf('proxy_enabled') == true) {
	$proxy_settings = [
		'proxy'           => 'tcp://'.Config::ValueOf('proxy_server').':'.Config::ValueOf('proxy_port'),
		'request_fulluri' => true];

	if (Config::ValueOf('proxy_use_auth') == true) {
		$proxy_auth = base64_encode(Config::ValueOf('proxy_user').':'.Config::ValueOf('proxy_pass'));
		$proxy_settings['header'] = "Proxy-Authorization: Basic ".$proxy_auth;
	}
	stream_context_set_default (['http' => $proxy_settings]);

	/* for debugging proxy config uncomment next line */
	// var_dump(stream_context_get_options(stream_context_get_default()));
}

/* Set UI language */
set_ui_language();

/* @http only cookies ------------------- */
if(php_sapi_name()!="cli")
	ini_set('session.cookie_httponly', 1);

/* @debugging functions ------------------- */
if(Config::ValueOf('debugging')==true) {
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
}
else {
	disable_php_errors();
	error_reporting(E_ERROR | E_WARNING);
}

// auto-set base if not already defined
if(!defined('BASE')) {
	$root = substr((string) $_SERVER['DOCUMENT_ROOT'],-1)=="/" ? substr((string) $_SERVER['DOCUMENT_ROOT'],0,-1) : $_SERVER['DOCUMENT_ROOT'];	// fix for missing / in some environments
	define('BASE', substr(str_replace($root, "", __DIR__),0,-9));
}

/* @classes ---------------------- */
require( __DIR__ . '/classes/class.Params.php' );		//Paramter handling class
require( __DIR__ . '/classes/class.Rewrite.php' );	//Class for POST/GET rewriting
require( __DIR__ . '/classes/class.Common.php' );		//Class common - common functions
require( __DIR__ . '/classes/class.PDO.php' );		//Class PDO - wrapper for database
require( __DIR__ . '/classes/class.User.php' );		//Class for active user management
require( __DIR__ . '/classes/class.Log.php' );		//Class for log saving
require( __DIR__ . '/classes/class.Result.php' );		//Class for result printing
require( __DIR__ . '/classes/class.Install.php' );	//Class for Install
require( __DIR__ . '/classes/class.Sections.php' );	//Class for sections
require( __DIR__ . '/classes/class.Subnets.php' );	//Class for subnets
require( __DIR__ . '/classes/class.Tools.php' );		//Class for tools
require( __DIR__ . '/classes/class.Addresses.php' );	//Class for addresses
require( __DIR__ . '/classes/class.Scan.php' );		//Class for Scanning and pinging
require( __DIR__ . '/classes/class.DNS.php' );		//Class for DNS management
require( __DIR__ . '/classes/class.PowerDNS.php' );	//Class for PowerDNS management
require( __DIR__ . '/classes/class.FirewallZones.php' );	//Class for firewall zone management
require( __DIR__ . '/classes/class.Admin.php' );		//Class for Administration
require( __DIR__ . '/classes/class.Mail.php' );		//Class for Mailing
require( __DIR__ . '/classes/class.Rackspace.php' );	//Class for Racks
require( __DIR__ . '/classes/class.SNMP.php' );	    //Class for SNMP queries
require( __DIR__ . '/classes/class.DHCP.php' );	    //Class for DHCP
require( __DIR__ . '/classes/class.SubnetsTree.php' );	    //Class for generating list of subnets based on nested tree structure
require( __DIR__ . '/classes/class.SubnetsMenu.php' );	    //Class for generating subnets menu.
require( __DIR__ . '/classes/class.SubnetsTable.php' );	    //Class for generating JSON to populate subnet <tables> using boostrap-tables.
require( __DIR__ . '/classes/class.SubnetsMasterDropDown.php' );	    //Class for generating HTML master subnet dropdown menus
require( __DIR__ . '/classes/class.Devtype.php' );	    //
require( __DIR__ . '/classes/class.Devices.php' );	    //
require( __DIR__ . '/classes/class.Crypto.php' );	    	// Crypto class
require( __DIR__ . '/classes/class.Password_check.php' );	// Class for password check
require( __DIR__ . '/classes/class.Session_DB.php' );	    // Class for storing sessions to database
require( __DIR__ . '/classes/class.LockForUpdate.php' );	    // Class for MySQL row locking
require( __DIR__ . '/classes/class.OpenStreetMap.php' );	    // Class for OSM



# create default GET parameters
$Rewrite = new Rewrite ();
$_GET = $Rewrite->get_url_params ();

$GET  = new Params ($_GET,  null, true); // Run strip_tags() on $_GET
$POST = new Params ($_POST, null, true); // Run strip_tags() on $_POST

/* get version */
require_once('version.php');
