<?php

/**
 * Tools menu items
 *
 */

# default
$tools_menu = array();

# icons
$tools_menu_icons['Tools'] 		= "fa-wrench";
$tools_menu_icons['Subnets'] 	= "fa-sitemap";
$tools_menu_icons['User Menu'] 	= "fa-user";
$tools_menu_icons['Devices'] 	= "fa-desktop";

# inclusion check
$tools_menu_items = array(
						'changelog',
						'dhcp',
						'devices',
						'favourites',
						'firewall-zones',
						'instructions',
						'ip-calculator',
						'logs',
						'multicast-networks',
						'pass-change',
						'powerDNS',
						'request-ip',
						'requests',
						'racks',
						'scanned-networks',
						'search',
						'subnet-masks',
						'subnets',
						'temp-shares',
						'user-menu',
						'vlan',
						'vrf',
						'inactive-hosts',
						"threshold",
						'nat',
						'locations',
						'pstn-prefixes',
						'mac-lookup',
						'circuits',
						'customers',
						"duplicates",
						"routing"
                    );


#custom
$private_subpages = Config::ValueOf('private_subpages');
if(is_array($private_subpages) && sizeof($private_subpages)>0) {
    # array and icon
    $tools_menu['Custom tools'] = array();
    $tools_menu_icons['Custom tools'] = "fa-star";
    // loop
    foreach ($private_subpages as $s) {
        // title
        $tools_menu['Custom tools'][] = array("show"=>true,	"icon"=>"fa-angle-right", "name"=>ucwords($s),  "href"=>$s, 	"description"=>ucwords($s)." "._("custom tool"));
        // add to inclusion check
        $tools_menu_items[] = $s;
    }
}

# arrays
$tools_menu['Subnets']   = array();
$tools_menu['Devices']   = array();
$tools_menu['Tools']     = array();
$tools_menu['User Menu'] = array();

# Tools
$tools_menu['Tools'][] = array("show"=>true,	"icon"=>"fa-search",		"name"=>_("Search"),				"href"=>"search",				"description"=>_("Search database Addresses, subnets and VLANs"));
$tools_menu['Tools'][] = array("show"=>true,	"icon"=>"fa-calculator",	"name"=>_("IP calculator"),			"href"=>"ip-calculator",		"description"=>_("IPv4v6 calculator for subnet calculations"));
$tools_menu['Tools'][] = array("show"=>true,	"icon"=>"fa-calculator",	"name"=>_("Bandwidth calculator"),	"href"=>"ip-calculator/bw-calculator","description"=>_("Bandwidth calculator"));
if($User->settings->enableChangelog == 1)
$tools_menu['Tools'][] = array("show"=>true,	"icon"=>"fa-clock-o",		"name"=>_("Changelog"),				"href"=>"changelog",			"description"=>_("Changelog for all network objects"));
$tools_menu['Tools'][] = array("show"=>true,	"icon"=>"fa-list",			"name"=>_("Log files"),				"href"=>"logs",					"description"=>_("Browse phpipam log files"));
if($User->settings->enableIPrequests==1) {
$tools_menu['Tools'][] = array("show"=>true,	"icon"=>"fa-plus",			"name"=>_("IP requests"),			"href"=>"requests",				"description"=>_("Manage IP requests"));
}

$tools_menu['Tools'][] = array("show"=>true,	"icon"=>"fa-info",			"name"=>_("Instructions"),			"href"=>"instructions",			"description"=>_("Instructions for managing IP addresses"));
if($User->settings->enablePowerDNS==1 && $User->get_module_permissions ("pdns")>=User::ACCESS_R)
$tools_menu['Tools'][] = array("show"=>true,	"icon"=>"fa-database",		"name"=>_("PowerDNS"),				"href"=>"powerDNS",				"description"=>_("PowerDNS"));
if($User->settings->enableDHCP==1 && $User->get_module_permissions ("dhcp")>=User::ACCESS_R)
$tools_menu['Tools'][] = array("show"=>true,	"icon"=>"fa-database",		"name"=>_("DHCP"),					"href"=>"dhcp",					"description"=>_("DHCP information"));
if($User->settings->enablePSTN==1 && $User->get_module_permissions ("pstn")>=User::ACCESS_R)
$tools_menu['Tools'][] = array("show"=>true,	"icon"=>"fa-phone",			"name"=>_("PSTN prefixes"),			"href"=>"pstn-prefixes",		"description"=>_("PSTN prefixes"));
$tools_menu['Tools'][] = array("show"=>true,	"icon"=>"fa-sitemap",		"name"=>_("MAC lookup"),			"href"=>"mac-lookup",			"description"=>_("Lookup MAC address vendor"));


# Subnets
if($User->settings->enableCustomers == 1 && $User->get_module_permissions ("customers")>=User::ACCESS_R)
$tools_menu['Subnets'][] 	= array("show"=>true,	"icon"=>"fa-users",		"name"=>_("Customers"),				"href"=>"customers",			"description"=>_("Customers"));
$tools_menu['Subnets'][] 	= array("show"=>true,	"icon"=>"fa-star",		"name"=>_("Favourite networks"),	"href"=>"favourites",			"description"=>_("Favourite networks"));
$tools_menu['Subnets'][] 	= array("show"=>true,	"icon"=>"fa-sitemap",	"name"=>_("Subnets"),				"href"=>"subnets",				"description"=>_("All subnets"));
if($User->get_module_permissions ("vlan")>=User::ACCESS_R)
$tools_menu['Subnets'][] 	= array("show"=>true,	"icon"=>"fa-cloud",		"name"=>_("VLAN"),					"href"=>"vlan",					"description"=>_("VLANs and belonging subnets"));
if($User->settings->enableVRF == 1 && $User->get_module_permissions ("vrf")>=User::ACCESS_R)
$tools_menu['Subnets'][] 	= array("show"=>true,	"icon"=>"fa-cloud",		"name"=>_("VRF"),					"href"=>"vrf",					"description"=>_("VRFs and belonging networks"));
if($User->settings->enableNAT==1 && $User->get_module_permissions ("nat")>=User::ACCESS_R)
$tools_menu['Subnets'][] = array("show"=>true,	"icon"=>"fa-exchange",		"name"=>_("NAT"),					"href"=>"nat",					"description"=>_("NAT translations"));
if($User->settings->enableRouting==1 && $User->get_module_permissions ("routing")>=User::ACCESS_R)
$tools_menu['Subnets'][] = array("show"=>true,	"icon"=>"fa-exchange",		"name"=>_("Routing"),				"href"=>"routing",				"description"=>_("Routing information"));
if($User->settings->enableMulticast == 1)
$tools_menu['Subnets'][] 	= array("show"=>true,	"icon"=>"fa-map-o",		"name"=>_("Multicast networks"),	"href"=>"multicast-networks",	"description"=>_("Multicast subnets and mapping"));
if($User->settings->enableFirewallZones == 1)
$tools_menu['Subnets'][] 	= array("show"=>true,	"icon"=>"fa-fire",		"name"=>_("Firewall Zones"),		"href"=>"firewall-zones",		"description"=>_("Display firewall zone to device mappings"));
$tools_menu['Subnets'][] 	= array("show"=>true,	"icon"=>"fa-eye",		"name"=>_("Scanned networks"),		"href"=>"scanned-networks",		"description"=>_("List of subnets to be scanned for online hosts and detect new hosts"));
$tools_menu['Subnets'][] 	= array("show"=>true,	"icon"=>"fa-th-large",	"name"=>_("Subnet masks"),			"href"=>"subnet-masks",			"description"=>_("Table of all subnet masks with different representations"));

// temp shares
if($User->settings->tempShare==1)
$tools_menu['Subnets'][] 	= array("show"=>true,	"icon"=>"fa-share-alt",	"name"=>_("Temporary shares"),		"href"=>"temp-shares",			"description"=>_("List of temporary shared objects"));
$tools_menu['Subnets'][] 	= array("show"=>true,	"icon"=>"fa-thumbs-down", "name"=>_("Inactive Hosts"),		"href"=>"inactive-hosts",		"description"=>_("List of inactive hosts"));
$tools_menu['Subnets'][] 	= array("show"=>true,	"icon"=>"fa-files-o",	"name"=>_("Duplicates"),			"href"=>"duplicates",			"description"=>_("List of duplicate subnets and addresses"));
if($User->settings->enableThreshold==1)
$tools_menu['Subnets'][] 	= array("show"=>true,	"icon"=>"fa-bullhorn",	"name"=>_("Threshold"),				"href"=>"threshold",			"description"=>_("List of thresholded subnets"));

# devices
if($User->get_module_permissions ("devices")>0)
$tools_menu['Devices'][] 	= array("show"=>true,	"icon"=>"fa-desktop",	"name"=>_("Devices"),				"href"=>"devices",				"description"=>-("All configured devices"));
if($User->settings->enableRACK == 1 && $User->get_module_permissions ("racks")>0)
$tools_menu['Devices'][] 	= array("show"=>true,	"icon"=>"fa-bars",		"name"=>_("Racks"),					"href"=>"racks",				"description"=>_("Rack information"));
if($User->settings->enableCircuits == 1 && $User->get_module_permissions ("circuits")>0)
$tools_menu['Devices'][] 	= array("show"=>true,	"icon"=>"fa-random",	"name"=>_("Circuits"),				"href"=>"circuits",				"description"=>_("Circuit information"));
if($User->settings->enableLocations == 1 && $User->get_module_permissions ("locations")>0)
$tools_menu['Devices'][] 	= array("show"=>true,	"icon"=>"fa-map",		"name"=>_("Locations"),				"href"=>"locations",			"description"=>_("Locations"));

# user menu
$tools_menu['User Menu'][] = array("show"=>true,	"icon"=>"fa-user",		"name"=>_("My account"),			"href"=>"user-menu",			"description"=>_("Manage your account"));
