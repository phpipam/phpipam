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
$admin_menu['Server management'][] = array("show"=>true,	"icon"=>"fa-cogs", 		"name"=>_("phpIPAM settings"),		"href"=>"settings", 				"description"=>_("phpIPAM server settings"));
$admin_menu['Server management'][] = array("show"=>true,	"icon"=>"fa-user",		"name"=>_("Users"),					"href"=>"users",					"description"=>_("User management"));
$admin_menu['Server management'][] = array("show"=>true,	"icon"=>"fa-users", 	"name"=>_("Groups"),				"href"=>"groups", 					"description"=>_("User group management"));
$admin_menu['Server management'][] = array("show"=>true,	"icon"=>"fa-server", 	"name"=>_("Authentication methods"),"href"=>"authentication-methods",	"description"=>_("Manage user authentication methods and servers"));
$admin_menu['Server management'][] = array("show"=>true,    "icon"=>"fa-shield",	"name"=>_("2FA"),					"href"=>"2fa",						"description"=>_("Two-factor authentication with Google Authenticator"));
$admin_menu['Server management'][] = array("show"=>true,    "icon"=>"fa-unlock",	"name"=>_("Password policy"),		"href"=>"password-policy",			"description"=>_("Set user password policy"));
$admin_menu['Server management'][] = array("show"=>true,	"icon"=>"fa-envelope-o","name"=>_("Mail settings"), 		"href"=>"mail",						"description"=>_("Set mail parameters and mail server settings"));
$admin_menu['Server management'][] = array("show"=>true,	"icon"=>"fa-cogs", 		"name"=>_("API"),					"href"=>"api",						"description"=>_("API settings"));
if($User->settings->enablePowerDNS==1)
$admin_menu['Server management'][] = array("show"=>true,	"icon"=>"fa-database",	"name"=>_("PowerDNS"),				"href"=>"powerDNS",					"description"=>_("PowerDNS settings"));
if($User->settings->enableDHCP==1)
$admin_menu['Server management'][] = array("show"=>true,	"icon"=>"fa-database",	"name"=>_("DHCP"),					"href"=>"dhcp",						"description"=>_("DHCP settings"));
$admin_menu['Server management'][] = array("show"=>true,	"icon"=>"fa-user-secret","name"=>_("Scan agents"),			"href"=>"scan-agents",				"description"=>_("phpipam Scan agents"));
$admin_menu['Server management'][] = array("show"=>true,	"icon"=>"fa-language",	"name"=>_("Languages"),				"href"=>"languages",				"description"=>_("Manage languages"));
$admin_menu['Server management'][] = array("show"=>true,	"icon"=>"fa-tachometer","name"=>_("Widgets"),				"href"=>"widgets",					"description"=>_("Manage widget settings"));
$admin_menu['Server management'][] = array("show"=>true,	"icon"=>"fa-tag",		"name"=>_("Tags"),					"href"=>"tags",						"description"=>_("Manage tags"));
if($User->settings->enablePSTN==1)
$admin_menu['Server management'][] = array("show"=>true,	"icon"=>"fa-phone",		"name"=>_("PSTN prefixes"),			"href"=>"pstn-prefixes",			"description"=>_("PSTN prefixes"));
$admin_menu['Server management'][] = array("show"=>true,	"icon"=>"fa-info",		"name"=>_("Edit instructions"),		"href"=>"instructions", 			"description"=>_("Set phpipam instructions for end users"));

# IP related management
if($User->settings->enableCustomers==1)
$admin_menu['IP related management'][] = array("show"=>true,    "icon"=>"fa-users",		"name"=>_("Customers"),			"href"=>"customers",				"description"=>_("Customer management"));
$admin_menu['IP related management'][] = array("show"=>true,	"icon"=>"fa-server",	"name"=>_("Sections"),			"href"=>"sections",					"description"=>_("Section management"));
$admin_menu['IP related management'][] = array("show"=>true,	"icon"=>"fa-sitemap",	"name"=>_("Subnets"),			"href"=>"subnets",					"description"=>_("Subnet management"));
$admin_menu['IP related management'][] = array("show"=>true,	"icon"=>"fa-cloud",		"name"=>_("VLAN"),				"href"=>"vlans",					"description"=>_("VLAN management"));
if($User->settings->enableVRF==1)
$admin_menu['IP related management'][] = array("show"=>true,	"icon"=>"fa-cloud",		"name"=>_("VRF"),				"href"=>"vrf",						"description"=>_("VRF management"));
if($User->settings->enableNAT==1)
$admin_menu['IP related management'][] = array("show"=>true,	"icon"=>"fa-exchange",	"name"=>_("NAT"),				"href"=>"nat",						"description"=>_("NAT settings"));
if($User->settings->enableRouting==1)
$admin_menu['IP related management'][] = array("show"=>true,    "icon"=>"fa-exchange",	"name"=>_("Routing"),			"href"=>"routing",					"description"=>_("Routing management"));
$admin_menu['IP related management'][] = array("show"=>true,	"icon"=>"fa-cloud",		"name"=>_("Nameservers"),		"href"=>"nameservers",				"description"=>_("Recursive nameserver sets for subnets"));
if($User->settings->enableFirewallZones == 1)
$admin_menu['IP related management'][] = array("show"=>true,	"icon"=>"fa-fire",		"name"=>_("Firewall Zones"),	"href"=>"firewall-zones",			"description"=>_("Firewall zone management"));
$admin_menu['IP related management'][] = array("show"=>true,	"icon"=>"fa-upload",	"name"=>_("Import / Export"),	"href"=>"import-export",			"description"=>_("Import/Export IP related data (VRF, VLAN, Subnets, IP, Devices)"));
$admin_menu['IP related management'][] = array("show"=>true,	"icon"=>"fa-cloud-download",	"name"=>_("RIPE import"),	"href"=>"ripe-import",			"description"=>_("Import subnets from RIPE"));
if($User->settings->enableIPrequests==1 && isset($requests)) {
$request_cnt = $requests>0 ? "<span class='ipreqMenu'>$requests</span>" : "";
$admin_menu['IP related management'][] = array("show"=>true,	"icon"=>"fa-plus",	"name"=>_("IP requests ").$request_cnt, "href"=>"requests",				"description"=>_("Manage IP requests"));
}
$admin_menu['IP related management'][] = array("show"=>true,	"icon"=>"fa-filter","name"=>_("Filter IP fields"),		"href"=>"filter-fields",			"description"=>_("Select which default address fields to display"));
$admin_menu['IP related management'][] = array("show"=>true,    "icon"=>"fa-filter","name"=>_("Required IP fields"),	"href"=>"required-fields",			"description"=>_("Select which address fields are required to be filled when creating address."));
$admin_menu['IP related management'][] = array("show"=>true,	"icon"=>"fa-magic",	"name"=>_("Custom fields"),			"href"=>"custom-fields",			"description"=>_("Manage custom fields"));


# device managements
$admin_menu['Device management'][] = array("show"=>true,    "icon"=>"fa-desktop",	"name"=>_("Devices"),				"href"=>"devices",					"description"=>_("Device management"));
if($User->settings->enableRACK == 1)
$admin_menu['Device management'][] = array("show"=>true,    "icon"=>"fa-bars",		"name"=>_("Racks"),					"href"=>"racks",					"description"=>_("Rack management"));
if($User->settings->enableCircuits == 1)
$admin_menu['Device management'][] = array("show"=>true,    "icon"=>"fa-random",	"name"=>_("Circuits"),				"href"=>"circuits",					"description"=>_("Circuits management"));
if($User->settings->enableSNMP == 1)
$admin_menu['Device management'][] = array("show"=>true,    "icon"=>"fa-cogs",		"name"=>_("SNMP"),					"href"=>"snmp",						"description"=>_("SNMP management"));
if($User->settings->enableLocations == 1)
$admin_menu['Device management'][]  = array("show"=>true,   "icon"=>"fa-map",		"name"=>_("Locations"),				"href"=>"locations",				"description"=>_("Locations"));


# Tools
$admin_menu['Tools'][] = array("show"=>true,	"icon"=>"fa-check",					"name"=>_("Version check"),			"href"=>"version-check",			"description"=>_("Check for latest version of phpipam"));
$admin_menu['Tools'][] = array("show"=>true,	"icon"=>"fa-magic",					"name"=>_("Verify database"),		"href"=>"verify-database",			"description"=>_("Verify that database files are installed ok"));
$admin_menu['Tools'][] = array("show"=>true,	"icon"=>"fa-search-plus",			"name"=>_("Replace fields"),		"href"=>"replace-fields",			"description"=>_("Search and replace content in database"));


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

