<?php

/*
 * set Admin menu content
 *************************************************/


# Icons
$admin_menu_icons['Server management'] 		= "fa-cogs";
$admin_menu_icons['Base Table management'] 	= "fa-database";
$admin_menu_icons['Location management'] 	= "fa-globe";
$admin_menu_icons['Tools'] 					= "fa-wrench";
$admin_menu_icons['IP related management'] 	= "fa-sitemap";

# arrays
$admin_menu['Server management']   = array();
$admin_menu['Base Table management']   = array();
$admin_menu['Location management']   = array();
$admim_menu['Tools']     = array();
$admin_menu['IP related management'] = array();

# Server management
$admin_menu['Server management'][] = array("show"=>true,	"icon"=>"fa-user",		"name"=>"Users", 					"href"=>"phpipam/administration/users",					"description"=>"User management");
$admin_menu['Server management'][] = array("show"=>true,	"icon"=>"fa-users", 	"name"=>"Groups", 	 				"href"=>"phpipam/administration/groups", 					"description"=>"User group management");

# IP related management
if($User->settings->enableCustomers==1)
$admin_menu['IP related management'][] = array("show"=>true,    "icon"=>"fa-sitemap", "name"=>"Address Types",          "href"=>"addresstypes",                 			"description"=>"Address Type management");
$admin_menu['IP related management'][] = array("show"=>true,	"icon"=>"fa-server", "name"=>"Sections", 				"href"=>"phpipam/administration/sections", 				"description"=>"Section management");
$admin_menu['IP related management'][] = array("show"=>true,	"icon"=>"fa-sitemap","name"=>"Subnets", 				"href"=>"phpipam/administration/subnets", 					"description"=>"Subnet management");
$admin_menu['IP related management'][] = array("show"=>true,	"icon"=>"fa-cloud",  "name"=>"VLAN", 					"href"=>"vlans", 					"description"=>"VLAN management");
if($User->settings->enableVRF==1)
$admin_menu['IP related management'][] = array("show"=>true,	"icon"=>"fa-cloud",  "name"=>"VRF", 					"href"=>"vrf", 					"description"=>"VRF management");
if($User->settings->enableNAT==1)
$admin_menu['IP related management'][] = array("show"=>true,	"icon"=>"fa-exchange", 	"name"=>"NAT", 				    "href"=>"phpipam/administration/nat", 				        "description"=>"NAT settings");
if($User->settings->enableRouting==1)
$admin_menu['IP related management'][] = array("show"=>true,    "icon"=>"fa-exchange",  "name"=>"Routing",              "href"=>"phpipam/administration/routing",                  "description"=>"Routing management");
$admin_menu['IP related management'][] = array("show"=>true,	"icon"=>"fa-cloud", 	"name"=>"Nameservers", 			"href"=>"phpipam/administration/nameservers", 				"description"=>"Recursive nameserver sets for subnets");
if($User->settings->enableFirewallZones == 1)
$admin_menu['IP related management'][] = array("show"=>true,	"icon"=>"fa-fire","name"=>"Firewall Zones", 		    "href"=>"phpipam/administration/firewall-zones", 			"description"=>"Firewall zone management");
$admin_menu['IP related management'][] = array("show"=>true,	"icon"=>"fa-upload", 	"name"=>"Import / Export", 	    "href"=>"phpipam/administration/import-export", 		    "description"=>"Import/Export IP related data (VRF, VLAN, Subnets, IP, Devices)");
$admin_menu['IP related management'][] = array("show"=>true,	"icon"=>"fa-cloud-download", 	"name"=>"RIPE import", 	"href"=>"phpipam/administration/ripe-import", 				"description"=>"Import subnets from RIPE");
if($User->settings->enableIPrequests==1 && isset($requests)) {
$request_cnt = $requests>0 ? "<span class='ipreqMenu'>$requests</span>" : "";
$admin_menu['IP related management'][] = array("show"=>true,	"icon"=>"fa-plus", 	"name"=>"IP requests $request_cnt", "href"=>"phpipam/administration/requests", 				"description"=>"Manage IP requests");
}
$admin_menu['IP related management'][] = array("show"=>true,	"icon"=>"fa-filter", "name"=>"Filter IP fields", 		"href"=>"phpipam/administration/filter-fields", 			"description"=>"Select which default address fields to display");
$admin_menu['IP related management'][] = array("show"=>true,    "icon"=>"fa-filter", "name"=>"Required IP fields",      "href"=>"phpipam/administration/required-fields",          "description"=>"Select which address fields are required to be filled when creating address.");
$admin_menu['IP related management'][] = array("show"=>true,	"icon"=>"fa-magic", 	"name"=>"Custom fields", 		"href"=>"phpipam/administration/custom-fields", 			"description"=>"Manage custom fields");

# Base Table management
$admin_menu['Base Table management'][] = array("show"=>true,    "icon"=>"fa-cloud","name"=>"VLAN Definitions",                     "href"=>"vlandefs",                  "description"=>"VLAN Definition Management");
if($User->settings->enableLocations == 1)
$admin_menu['Base Table management'][] = array("show"=>true,    "icon"=>"fa-globe","name"=>"Connection Types",                     "href"=>"loccons",                  "description"=>"Connections Types Management");



# Location management
if($User->settings->enableLocations == 1)
$admin_menu['Location management'][] = array("show"=>true,    "icon"=>"fa-bars","name"=>"Regions",                        "href"=>"regions",                    "description"=>"Region management");
if($User->settings->enableLocations == 1)
$admin_menu['Location management'][] = array("show"=>true,    "icon"=>"fa-random","name"=>"Sub-Regions",                   "href"=>"subregions",                 "description"=>"Sub-Region management");
if($User->settings->enableLocations == 1)
$admin_menu['Location management'][] = array("show"=>true,    "icon"=>"fa-globe","name"=>"Countries",                     "href"=>"countries",                  "description"=>"Country Management");
if($User->settings->enableLocations == 1)
$admin_menu['Location management'][]  = array("show"=>true,   "icon"=>"fa-map","name"=>"Locations",               "href"=>"locations",                "description"=>"Locations");
if($User->settings->enableLocations == 1)
$admin_menu['Location management'][] = array("show"=>true,    "icon"=>"fa-globe","name"=>"Location Status",                     "href"=>"locstatus",                  "description"=>"Location Status Management");
if($User->settings->enableLocations == 1)
$admin_menu['Location management'][] = array("show"=>true,    "icon"=>"fa-globe","name"=>"Location Types",                     "href"=>"loctypes",                  "description"=>"Location Sizes Management");
if($User->settings->enableLocations == 1)
$admin_menu['Location management'][] = array("show"=>true,    "icon"=>"fa-globe","name"=>"Locations Sizes",                     "href"=>"locsizes",                  "description"=>"Location Types Management");
if($User->settings->enableLocations == 1)
$admin_menu['Location management'][] = array("show"=>true,    "icon"=>"fa-globe","name"=>"Studio Types",                     "href"=>"studiotypes",                  "description"=>"Studio Types Management");

# Tools
$admin_menu['Tools'][] = array("show"=>true,	"icon"=>"fa-check", 				"name"=>"Site Languages", 			"href"=>"languages2", 			"description"=>"Site Languages Management");
$admin_menu['Tools'][] = array("show"=>true,	"icon"=>"fa-gear", 					"name"=>"Generate Site IPs", 		"href"=>"gensiteips", 			"description"=>"Generate Site IPs");
$admin_menu['Tools'][] = array("show"=>true,	"icon"=>"fa-magic", 				"name"=>"Verify database", 			"href"=>"phpipam/administration/verify-database", 			"description"=>"Verify that database files are installed ok");
$admin_menu['Tools'][] = array("show"=>true,	"icon"=>"fa-search-plus", 			"name"=>"Replace fields", 			"href"=>"phpipam/administration/replace-fields", 			"description"=>"Search and replace content in database");


# inclusion check
$admin_menu_items = array(
                        'addresstypes',
                        'api',
                		'authentication-methods',
                        'password-policy',
                		'custom-fields',
                		'dhcp',
                		'countries',
                		'regions',
                		'subregions',
                		'locstatus',
                		'loctypes',
                		'locsizes',
                		'studiotypes',
                		'loccons',
                		'regions',
                		'filter-fields',
                        'required-fields',
                		'firewall-zones',
                		'groups',
                		'import-export',
                		'instructions',
                		'languages',
                 		'languages2',
						'mail',
                		'nameservers',
                		'powerDNS',
                		'racks',
                		'replace-fields',
                		'requests',
                		'ripe-import',
                		'scan-agents',
                		'sections',
                		'settings',
                		'snmp',
                		'subnets',
                		'tags',
                		'users',
                		'verify-database',
                		'version-check',
                		'vlandefs',
                		'vlans',
                		'vrf',
                		'widgets',
                		'nat',
                		'locations',
                        'circuits',
                		'pstn-prefixes',
                        '2fa',
                        'customers',
                        'gensiteips',
                        'routing',
                        'device-types',
						'contacts'
                    );

