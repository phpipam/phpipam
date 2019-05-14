<?php

/*
 * set Admin menu content
 *************************************************/


# Icons
$admin_menu_icons['Server management'] 		= "fa-cogs";
$admin_menu_icons['IP related management'] 	= "fa-sitemap";
$admin_menu_icons['Tools'] 					= "fa-wrench";
$admin_menu_icons['Device management'] 		= "fa-desktop";


# Server management
$admin_menu['Server management'][] = array("show"=>true,	"icon"=>"fa-cogs", 		"name"=>"phpIPAM settings", 		"href"=>"settings", 				"description"=>"phpIPAM server settings");
$admin_menu['Server management'][] = array("show"=>true,	"icon"=>"fa-user",		"name"=>"Users", 					"href"=>"users",					"description"=>"User management");
$admin_menu['Server management'][] = array("show"=>true,	"icon"=>"fa-users", 	"name"=>"Groups", 	 				"href"=>"groups", 					"description"=>"User group management");
$admin_menu['Server management'][] = array("show"=>true,	"icon"=>"fa-server", 	"name"=>"Authentication methods", 	"href"=>"authentication-methods", 	"description"=>"Manage user authentication methods and servers");
$admin_menu['Server management'][] = array("show"=>true,    "icon"=>"fa-shield","name"=>"2FA",                          "href"=>"2fa",                      "description"=>"Two-factor authentication with Google Authenticator");
$admin_menu['Server management'][] = array("show"=>true,    "icon"=>"fa-unlock",    "name"=>"Password policy",          "href"=>"password-policy",          "description"=>"Set user password policy");
$admin_menu['Server management'][] = array("show"=>true,	"icon"=>"fa-envelope-o", "name"=>"Mail settings", 			"href"=>"mail", 					"description"=>"Set mail parameters and mail server settings");
$admin_menu['Server management'][] = array("show"=>true,	"icon"=>"fa-cogs", 		"name"=>"API", 						"href"=>"api", 						"description"=>"API settings");
if($User->settings->enablePowerDNS==1)
$admin_menu['Server management'][] = array("show"=>true,	"icon"=>"fa-database", 	"name"=>"PowerDNS", 				"href"=>"powerDNS", 				"description"=>"PowerDNS settings");
if($User->settings->enableDHCP==1)
$admin_menu['Server management'][] = array("show"=>true,	"icon"=>"fa-database", 	  	"name"=>"DHCP",  		        "href"=>"dhcp",                     "description"=>"DHCP settings");
$admin_menu['Server management'][] = array("show"=>true,	"icon"=>"fa-user-secret", 	"name"=>"Scan agents", 			"href"=>"scan-agents", 			"description"=>"phpipam Scan agents");
$admin_menu['Server management'][] = array("show"=>true,	"icon"=>"fa-language", 	"name"=>"Languages", 				"href"=>"languages", 				"description"=>"Manage languages");
$admin_menu['Server management'][] = array("show"=>true,	"icon"=>"fa-tachometer","name"=>"Widgets", 					"href"=>"widgets", 					"description"=>"Manage widget settings");
$admin_menu['Server management'][] = array("show"=>true,	"icon"=>"fa-tag", 		"name"=>"Tags", 					"href"=>"tags", 					"description"=>"Manage tags");
if($User->settings->enablePSTN==1)
$admin_menu['Server management'][] = array("show"=>true,	"icon"=>"fa-phone", 		"name"=>"PSTN prefixes", 		"href"=>"pstn-prefixes", 			"description"=>"PSTN prefixes");
$admin_menu['Server management'][] = array("show"=>true,	"icon"=>"fa-info", 		"name"=>"Edit instructions", 		"href"=>"instructions", 			"description"=>"Set phpipam instructions for end users");

# IP related management
if($User->settings->enableCustomers==1)
$admin_menu['IP related management'][] = array("show"=>true,    "icon"=>"fa-users", "name"=>"Customers",                "href"=>"customers",                 "description"=>"Customer management");
$admin_menu['IP related management'][] = array("show"=>true,	"icon"=>"fa-server", "name"=>"Sections", 				"href"=>"sections", 				"description"=>"Section management");
$admin_menu['IP related management'][] = array("show"=>true,	"icon"=>"fa-sitemap","name"=>"Subnets", 				"href"=>"subnets", 					"description"=>"Subnet management");
$admin_menu['IP related management'][] = array("show"=>true,	"icon"=>"fa-cloud",  "name"=>"VLAN", 					"href"=>"vlans", 					"description"=>"VLAN management");
if($User->settings->enableVRF==1)
$admin_menu['IP related management'][] = array("show"=>true,	"icon"=>"fa-cloud",  "name"=>"VRF", 					"href"=>"vrf", 					"description"=>"VRF management");
if($User->settings->enableNAT==1)
$admin_menu['IP related management'][] = array("show"=>true,	"icon"=>"fa-exchange", 	"name"=>"NAT", 				    "href"=>"nat", 				        "description"=>"NAT settings");
if($User->settings->enableRouting==1)
$admin_menu['IP related management'][] = array("show"=>true,    "icon"=>"fa-exchange",  "name"=>"Routing",              "href"=>"routing",                  "description"=>"Routing management");
$admin_menu['IP related management'][] = array("show"=>true,	"icon"=>"fa-cloud", 	"name"=>"Nameservers", 			"href"=>"nameservers", 				"description"=>"Recursive nameserver sets for subnets");
if($User->settings->enableFirewallZones == 1)
$admin_menu['IP related management'][] = array("show"=>true,	"icon"=>"fa-fire","name"=>"Firewall Zones", 		    "href"=>"firewall-zones", 			"description"=>"Firewall zone management");
$admin_menu['IP related management'][] = array("show"=>true,	"icon"=>"fa-upload", 	"name"=>"Import / Export", 	    "href"=>"import-export", 		    "description"=>"Import/Export IP related data (VRF, VLAN, Subnets, IP, Devices)");
$admin_menu['IP related management'][] = array("show"=>true,	"icon"=>"fa-cloud-download", 	"name"=>"RIPE import", 	"href"=>"ripe-import", 				"description"=>"Import subnets from RIPE");
if($User->settings->enableIPrequests==1 && isset($requests)) {
$request_cnt = $requests>0 ? "<span class='ipreqMenu'>$requests</span>" : "";
$admin_menu['IP related management'][] = array("show"=>true,	"icon"=>"fa-plus", 	"name"=>"IP requests $request_cnt", "href"=>"requests", 				"description"=>"Manage IP requests");
}
$admin_menu['IP related management'][] = array("show"=>true,	"icon"=>"fa-filter", "name"=>"Filter IP fields", 		"href"=>"filter-fields", 			"description"=>"Select which default address fields to display");
$admin_menu['IP related management'][] = array("show"=>true,    "icon"=>"fa-filter", "name"=>"Required IP fields",      "href"=>"required-fields",          "description"=>"Select which address fields are required to be filled when creating address.");
$admin_menu['IP related management'][] = array("show"=>true,	"icon"=>"fa-magic", 	"name"=>"Custom fields", 		"href"=>"custom-fields", 			"description"=>"Manage custom fields");


# device managements
$admin_menu['Device management'][] = array("show"=>true,    "icon"=>"fa-desktop","name"=>"Devices",                     "href"=>"devices",                  "description"=>"Device management");
if($User->settings->enableRACK == 1)
$admin_menu['Device management'][] = array("show"=>true,    "icon"=>"fa-bars",  "name"=>"Racks",                        "href"=>"racks",                    "description"=>"Rack management");
if($User->settings->enableCircuits == 1)
$admin_menu['Device management'][] = array("show"=>true,    "icon"=>"fa-random",  "name"=>"Circuits",                   "href"=>"circuits",                 "description"=>"Circuits management");
if($User->settings->enableSNMP == 1)
$admin_menu['Device management'][] = array("show"=>true,    "icon"=>"fa-cogs","name"=>"SNMP",                           "href"=>"snmp",                     "description"=>"SNMP management");
if($User->settings->enableLocations == 1)
$admin_menu['Device management'][]  = array("show"=>true,   "icon"=>"fa-map",        "name"=>"Locations",               "href"=>"locations",                "description"=>"Locations");


# Tools
$admin_menu['Tools'][] = array("show"=>true,	"icon"=>"fa-check", 				"name"=>"Version check", 			"href"=>"version-check", 			"description"=>"Check for latest version of phpipam");
$admin_menu['Tools'][] = array("show"=>true,	"icon"=>"fa-magic", 				"name"=>"Verify database", 			"href"=>"verify-database", 			"description"=>"Verify that database files are installed ok");
$admin_menu['Tools'][] = array("show"=>true,	"icon"=>"fa-search-plus", 			"name"=>"Replace fields", 			"href"=>"replace-fields", 			"description"=>"Search and replace content in database");


# inclusion check
$admin_menu_items = array(
                        'api',
                		'authentication-methods',
                        'password-policy',
                		'custom-fields',
                		'dhcp',
                		'devices',
                		'device-types',
                		'filter-fields',
                        'required-fields',
                		'firewall-zones',
                		'groups',
                		'import-export',
                		'instructions',
                		'languages',
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
                		'vlans',
                		'vrf',
                		'widgets',
                		'nat',
                		'locations',
                        'circuits',
                		'pstn-prefixes',
                        '2fa',
                        'customers',
                        'routing'
                    );

