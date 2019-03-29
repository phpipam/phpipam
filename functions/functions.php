<?php

/* @config file ------------------ */
require_once( dirname(__FILE__) . '/../config.php' );

/* global and missing functions */
require('global_functions.php');

/* @http only cookies ------------------- */
if(php_sapi_name()!="cli")
	ini_set('session.cookie_httponly', 1);

/* @debugging functions ------------------- */
if($debugging) {
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL ^ E_NOTICE ^ E_STRICT);
}
else {
	ini_set('display_errors', 0);
	ini_set('display_startup_errors', 0);
	error_reporting(E_ERROR ^ E_WARNING);
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
require( dirname(__FILE__) . '/classes/class.Rewrite.php' );	    //Class for DHCP
require( dirname(__FILE__) . '/classes/class.SubnetsTree.php' );	    //Class for generating list of subnets based on nested tree structure
require( dirname(__FILE__) . '/classes/class.SubnetsMenu.php' );	    //Class for generating subnets menu.
require( dirname(__FILE__) . '/classes/class.SubnetsTable.php' );	    //Class for generating JSON to populate subnet <tables> using boostrap-tables.
require( dirname(__FILE__) . '/classes/class.SubnetsMasterDropDown.php' );	    //Class for generating HTML master subnet dropdown menus
require( dirname(__FILE__) . '/classes/class.Devtype.php' );	    //
require( dirname(__FILE__) . '/classes/class.Devices.php' );	    //
require( dirname(__FILE__) . '/classes/class.Crypto.php' );	    	// Crypto class
require( dirname(__FILE__) . '/classes/class.Password_check.php' );	// Class for password check
require( dirname(__FILE__) . '/classes/class.Session_DB.php' );	    // Class for storing sessions to database


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

# create default GET parameters
$Rewrite = new Rewrite ();
$_GET = $Rewrite->get_url_params ();

/* get version */
include('version.php');
