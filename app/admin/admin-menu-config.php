<?php

/*
 * set Admin menu content
 *************************************************/


# Icons
$admin_menu_icons['Server management'] 		= "fa-cogs";
$admin_menu_icons['IP related management'] 	= "fa-sitemap";
$admin_menu_icons['Tools'] 					= "fa-wrench";

# Server management
$admin_menu['Server management'][] = array("show"=>true,	"icon"=>"fa-cogs", 		"name"=>"phpIPAM settings", 		"href"=>"settings", 				"description"=>"phpIPAM server settings");
$admin_menu['Server management'][] = array("show"=>true,	"icon"=>"fa-user",		"name"=>"Users", 					"href"=>"users",					"description"=>"User management");
$admin_menu['Server management'][] = array("show"=>true,	"icon"=>"fa-users", 	"name"=>"Groups", 	 				"href"=>"groups", 					"description"=>"User group management");
$admin_menu['Server management'][] = array("show"=>true,	"icon"=>"fa-server", 	"name"=>"Authentication methods", 	"href"=>"authentication-methods", 	"description"=>"Manage user authentication methods and servers");
$admin_menu['Server management'][] = array("show"=>true,	"icon"=>"fa-envelope-o", "name"=>"Mail settings", 			"href"=>"mail", 					"description"=>"Set mail parameters and mail server settings");
$admin_menu['Server management'][] = array("show"=>true,	"icon"=>"fa-cogs", 		"name"=>"API", 						"href"=>"api", 						"description"=>"API settings");
if($User->settings->enablePowerDNS==1)
$admin_menu['Server management'][] = array("show"=>true,	"icon"=>"fa-database", 	"name"=>"PowerDNS", 				"href"=>"powerDNS", 				"description"=>"PowerDNS settings");
$admin_menu['Server management'][] = array("show"=>true,	"icon"=>"fa-user-secret", 	"name"=>"Scan agents", 				"href"=>"scan-agents", 				"description"=>"phpipam Scan agents");
$admin_menu['Server management'][] = array("show"=>true,	"icon"=>"fa-language", 	"name"=>"Languages", 				"href"=>"languages", 				"description"=>"Manage languages");
$admin_menu['Server management'][] = array("show"=>true,	"icon"=>"fa-tachometer","name"=>"Widgets", 					"href"=>"widgets", 					"description"=>"Manage widget settings");
$admin_menu['Server management'][] = array("show"=>true,	"icon"=>"fa-tag", 		"name"=>"Tags", 					"href"=>"tags", 					"description"=>"Manage tags");
$admin_menu['Server management'][] = array("show"=>true,	"icon"=>"fa-info", 		"name"=>"Edit instructions", 		"href"=>"instructions", 			"description"=>"Set phpipam instructions for end users");

# IP related management
$admin_menu['IP related management'][] = array("show"=>true,	"icon"=>"fa-server", "name"=>"Sections", 				"href"=>"sections", 				"description"=>"Section management");
$admin_menu['IP related management'][] = array("show"=>true,	"icon"=>"fa-sitemap","name"=>"Subnets", 				"href"=>"subnets", 					"description"=>"Subnet management");
$admin_menu['IP related management'][] = array("show"=>true,	"icon"=>"fa-desktop","name"=>"Devices", 				"href"=>"devices", 					"description"=>"Device management");
if($User->settings->enableFirewallZones == 1)
$admin_menu['IP related management'][] = array("show"=>true,	"icon"=>"fa-fire","name"=>"Firewall Zones", 		"href"=>"firewall-zones", 			"description"=>"Firewall zone management");
$admin_menu['IP related management'][] = array("show"=>true,	"icon"=>"fa-cloud",  "name"=>"VLAN", 					"href"=>"vlans", 					"description"=>"VLAN management");
$admin_menu['IP related management'][] = array("show"=>true,	"icon"=>"fa-cloud", 	"name"=>"Nameservers", 					"href"=>"nameservers", 					"description"=>"Recursive nameserver sets for subnets");
if($User->settings->enableVRF==1)
$admin_menu['IP related management'][] = array("show"=>true,	"icon"=>"fa-cloud",  "name"=>"VRF", 					"href"=>"vrfs", 					"description"=>"VRF management");
$admin_menu['IP related management'][] = array("show"=>true,	"icon"=>"fa-upload", 	"name"=>"Import / Export", 	"href"=>"import-export", 		"description"=>"Import/Export IP related data (VRF, VLAN, Subnets, IP, Devices)");
$admin_menu['IP related management'][] = array("show"=>true,	"icon"=>"fa-cloud-download", 	"name"=>"RIPE import", 	"href"=>"ripe-import", 				"description"=>"Import subnets from RIPE");
if($User->settings->enableIPrequests==1) {
$request_cnt = $requests>0 ? "<span class='ipreqMenu'>$requests</span>" : "";
$admin_menu['IP related management'][] = array("show"=>true,	"icon"=>"fa-plus", 	"name"=>"IP requests $request_cnt", 	"href"=>"requests", 				"description"=>"Manage IP requests");
}
$admin_menu['IP related management'][] = array("show"=>true,	"icon"=>"fa-filter", "name"=>"Filter IP fields", 		"href"=>"filter-fields", 			"description"=>"Select which default address fields to display");
$admin_menu['IP related management'][] = array("show"=>true,	"icon"=>"fa-magic", 	"name"=>"Custom fields", 		"href"=>"custom-fields", 			"description"=>"Manage custom fields");

# Tools
$admin_menu['Tools'][] = array("show"=>true,	"icon"=>"fa-check", 				"name"=>"Version check", 			"href"=>"version-check", 			"description"=>"Check for latest version of phpipam");
$admin_menu['Tools'][] = array("show"=>true,	"icon"=>"fa-magic", 				"name"=>"Verify database", 			"href"=>"verify-database", 			"description"=>"Verify that database files are installed ok");
$admin_menu['Tools'][] = array("show"=>true,	"icon"=>"fa-search-plus", 			"name"=>"Replace fields", 			"href"=>"replace-fields", 			"description"=>"Search and replace content in database");

?>
