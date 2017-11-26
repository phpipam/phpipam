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

# create default GET parameters
create_get_params ();

/**
 * This function will emulate GET paramters to simplify .htaccess
 *
 * Old rules:
 *
 * 	RewriteRule ^(.*)/(.*)/(.*)/(.*)/(.*)/(.*)/$ index.php?page=$1&section=$2&subnetId=$3&sPage=$4&ipaddrid=$5&tab=$6 [L]
 *	RewriteRule ^(.*)/(.*)/(.*)/(.*)/(.*)/$ index.php?page=$1&section=$2&subnetId=$3&sPage=$4&ipaddrid=$5 [L,QSA]
 *	RewriteRule ^(.*)/(.*)/(.*)/(.*)/$ index.php?page=$1&section=$2&subnetId=$3&sPage=$4 [L,QSA]
 *	RewriteRule ^(.*)/(.*)/(.*)/$ index.php?page=$1&section=$2&subnetId=$3 [L,QSA]
 *	RewriteRule ^(.*)/(.*)/$ index.php?page=$1&section=$2 [L,QSA]
 *	RewriteRule ^(.*)/$ index.php?page=$1 [L]
 *
 *
 * # IE login dashboard fix
 *	RewriteRule ^login/dashboard/$ dashboard/ [R]
 * 	RewriteRule ^logout/dashboard/$ dashboard/ [R]
 *  # search override
 *  RewriteRule ^tools/search/(.*)$ index.php?page=tools&section=search&ip=$1 [L]
 *
 *
 * API
 * 	# exceptions
 *	RewriteRule ^(.*)/addresses/search_hostname/(.*)/$ ?app_id=$1&controller=addresses&id=search_hostname&id2=$2 [L,QSA]
 *	RewriteRule ^(.*)/prefix/external_id/(.*)/$ ?app_id=$1&controller=prefix&id=external_id&id2=$2 [L,QSA]
 *	RewriteRule ^(.*)/prefix/external_id/(.*) ?app_id=$1&controller=prefix&id=external_id&id2=$2 [L,QSA]
 *	RewriteRule ^(.*)/(.*)/cidr/(.*)/(.*)/$ ?app_id=$1&controller=$2&id=cidr&id2=$3&id3=$4 [L,QSA]
 *	RewriteRule ^(.*)/(.*)/cidr/(.*)/(.*) ?app_id=$1&controller=$2&id=cidr&id2=$3&id3=$4 [L,QSA]
 *	# controller rewrites
 *	RewriteRule ^(.*)/(.*)/(.*)/(.*)/(.*)/(.*)/$ ?app_id=$1&controller=$2&id=$3&id2=$4&id3=$5&id4=$6 [L,QSA]
 *	RewriteRule ^(.*)/(.*)/(.*)/(.*)/(.*)/$ ?app_id=$1&controller=$2&id=$3&id2=$4&id3=$5 [L,QSA]
 *	RewriteRule ^(.*)/(.*)/(.*)/(.*)/$ ?app_id=$1&controller=$2&id=$3&id2=$4 [L,QSA]
 *	RewriteRule ^(.*)/(.*)/(.*)/$ ?app_id=$1&controller=$2&id=$3 [L,QSA]
 *	RewriteRule ^(.*)/(.*)/$ ?app_id=$1&controller=$2 [L,QSA]
 *	RewriteRule ^(.*)/$ ?app_id=$1 [L,QSA]
 *
 *
 * @method create_get_params
 *
 * @return [type]
 */
function create_get_params () {
	// parse and create GET params - only for pretty_link enabled !
	if(strpos($_SERVER['REQUEST_URI'], "index.php")===false) {
		if(BASE!="/") {
			$uri_parts = array_values(array_filter(explode("/", str_replace(BASE, "", $_SERVER['REQUEST_URI']))));
		}
		else {
			$uri_parts = array_values(array_filter(explode("/", $_SERVER['REQUEST_URI'])));
		}

		// if some exist process it
		if(sizeof($uri_parts)>0) {
			# API
			if ($uri_parts[0]=="api") {
				unset($uri_parts[0]);
				foreach ($uri_parts as $k=>$l) {
					switch ($k) {
						case 1  : $_GET['app_id']     = $l;	break;
						case 2  : $_GET['controller'] = $l;	break;
						case 3  : $_GET['id']    	  = $l;	break;
						case 4  : $_GET['id2'] 		  = $l;	break;
						case 5  : $_GET['id3']        = $l;	break;
						case 5  : $_GET['id4']        = $l;	break;
						default : $_GET[$k]           = $l;	break;
					}
				}
			}
			# passthroughs
			elseif($uri_parts[0]!="app") {
				foreach ($uri_parts as $k=>$l) {
					switch ($k) {
						case 0  : $_GET['page'] 	= $l;	break;
						case 1  : $_GET['section']  = $l;	break;
						case 2  : $_GET['subnetId'] = $l;	break;
						case 3  : $_GET['sPage']    = $l;	break;
						case 4  : $_GET['ipaddrid'] = $l;	break;
						case 5  : $_GET['tab']      = $l;	break;
						default : $_GET[$k]         = $l;	break;
					}
				}
			}
		}
		else {
			# set default page
			$_GET['page'] = "dashboard";
		}


		// fixes
		if(isset($_GET['page'])) {
			// dashboard fix
			if($_GET['page']=="login" || $_GET['page']=="logout") {
				if(isset($_GET['section'])) {
					if ($_GET['section']=="dashboard") {
						$_GET['page'] = "dashboard";
					}
				}
			}
			// search fix
			elseif ($_GET['page']=="tools") {
				if(isset($_GET['section']) && isset($_GET['subnetId'])) {
					if ($_GET['section']=="search") {
						$_GET['ip']     = $_GET['subnetId'];
						$_REQUEST['ip'] = $_GET['ip'];
						unset($_GET['subnetId']);
					}
				}
			}
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
		if(!is_null($l6))		{ $link = "index.php?$el[0]=$l0&$el[1]=$l1&$el[2]=$l2&$el[3]=$l3&$el[4]=$l4&$el[5]=$l5&$el[6]=$l6"; }
		elseif(!is_null($l5))	{ $link = "index.php?$el[0]=$l0&$el[1]=$l1&$el[2]=$l2&$el[3]=$l3&$el[4]=$l4&$el[5]=$l5"; }
		elseif(!is_null($l4))	{ $link = "index.php?$el[0]=$l0&$el[1]=$l1&$el[2]=$l2&$el[3]=$l3&$el[4]=$l4"; }
		elseif(!is_null($l3))	{ $link = "index.php?$el[0]=$l0&$el[1]=$l1&$el[2]=$l2&$el[3]=$l3"; }
		elseif(!is_null($l2))	{ $link = "index.php?$el[0]=$l0&$el[1]=$l1&$el[2]=$l2"; }
		elseif(!is_null($l1))	{ $link = "index.php?$el[0]=$l0&$el[1]=$l1"; }
		elseif(!is_null($l0))	{ $link = "index.php?$el[0]=$l0"; }
		else					{ $link = ""; }
	}
	# prepend base
	$link = BASE.$link;

	# result
	return $link;
}

/* get version */
include('version.php');
