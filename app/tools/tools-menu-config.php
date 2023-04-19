<?php

/**
 * Tools menu items
 *
 */

# default
$tools_menu = [];

# icons
$tools_menu_icons[_('Tools')]     = "fa-wrench";
$tools_menu_icons[_('Subnets')]   = "fa-sitemap";
$tools_menu_icons[_('User Menu')] = "fa-user";
$tools_menu_icons[_('Devices')]   = "fa-desktop";

# inclusion check
$tools_menu_items = [
	"changelog" => _("changelog"),
	"dhcp" => _("dhcp"),
	"devices" => _("devices"),
	"favourites" => _("favourites"),
	"firewall-zones" => _("firewall-zones"),
	"instructions" => _("instructions"),
	"ip-calculator" => _("ip-calculator"),
	"logs" => _("logs"),
	"multicast-networks" => _("multicast-networks"),
	"pass-change" => _("pass-change"),
	"powerDNS" => _("powerDNS"),
	"request-ip" => _("request-ip"),
	"requests" => _("requests"),
	"racks" => _("racks"),
	"scanned-networks" => _("scanned-networks"),
	"documentation"=> _("documentation"),
	"search" => _("search"),
	"subnet-masks" => _("subnet-masks"),
	"subnets" => _("subnets"),
	"temp-shares" => _("temp-shares"),
	"user-menu" => _("user-menu"),
	"vlan" => _("vlan"),
	"vrf" => _("vrf"),
	"inactive-hosts" => _("inactive-hosts"),
	"threshold" => _("threshold"),
	"nat" => _("nat"),
	"locations" => _("locations"),
	"pstn-prefixes" => _("pstn-prefixes"),
	"mac-lookup" => _("mac-lookup"),
	"circuits" => _("circuits"),
	"customers" => _("customers"),
	"duplicates" => _("duplicates"),
	"routing" => _("routing"),
	"vaults" => _("vaults"),
	];


#custom
$private_subpages = Config::ValueOf('private_subpages');
if(is_array($private_subpages) && sizeof($private_subpages)>0) {
    # array and icon
    $tools_menu[_('Custom tools')] = [];
    $tools_menu_icons[_('Custom tools')] = "fa-star";
    // loop
    foreach ($private_subpages as $s) {
        // title
        $tools_menu[_('Custom tools')][] = ["show"=>true, "icon"=>"fa-angle-right", "href"=>$s, "name"=>ucwords($s), "description"=>ucwords($s)." "._("custom tool")];
        // add to inclusion check
        $tools_menu_items[$s] = $s;
    }
}

# arrays
$tools_menu[_('Subnets')]   = [];
$tools_menu[_('Devices')]   = [];
$tools_menu[_('Tools')]     = [];
$tools_menu[_('User Menu')] = [];

# Tools
$tools_menu['Tools'][] = array("show"=>true,	"icon"=>"fa-search", 		"name"=>"Search", 		 		"href"=>"search", 		"description"=>"Search database Addresses, subnets and VLANs");
$tools_menu['Tools'][] = array("show"=>true,	"icon"=>"fa-calculator",	"name"=>"IP calculator", 		"href"=>"ip-calculator","description"=>"IPv4v6 calculator for subnet calculations");
$tools_menu['Tools'][] = array("show"=>true,	"icon"=>"fa-calculator",	"name"=>"Bandwidth calculator", "href"=>"ip-calculator/bw-calculator","description"=>"Bandwidth calculator");
if($User->settings->enableChangelog == 1)
$tools_menu['Tools'][] = array("show"=>true,	"icon"=>"fa-clock-o", 		"name"=>"Changelog", 	 		"href"=>"changelog", 	"description"=>"Changelog for all network objects");
$tools_menu['Tools'][] = array("show"=>true,	"icon"=>"fa-list", 			"name"=>"Log files", 			"href"=>"logs",		 	"description"=>"Browse phpipam log files");
if($User->settings->enableIPrequests==1) {
$tools_menu['Tools'][] = array("show"=>true,	"icon"=>"fa-plus", 			"name"=>"IP requests", 			"href"=>"requests", 	"description"=>"Manage IP requests");
}
$tools_menu['Tools'][] = array("show"=>true,	"icon"=>"fa-info", 	  		"name"=>"Instructions",  		"href"=>"instructions", "description"=>"Instructions for managing IP addresses");
if($User->settings->enablePowerDNS==1 && $User->get_module_permissions ("pdns")>0)
$tools_menu['Tools'][] = array("show"=>true,	"icon"=>"fa-database", 	  	"name"=>"PowerDNS",  		    "href"=>"powerDNS", "description"=>"PowerDNS");
if($User->settings->enableDHCP==1 && $User->get_module_permissions ("dhcp")>0)
$tools_menu['Tools'][] = array("show"=>true,	"icon"=>"fa-database", 	  	"name"=>"DHCP",  		        "href"=>"dhcp", "description"=>"DHCP information");
if($User->settings->enablePSTN==1 && $User->get_module_permissions ("pstn")>0)
$tools_menu['Tools'][] = array("show"=>true,	"icon"=>"fa-phone", 	  	"name"=>"PSTN prefixes",  		 "href"=>"pstn-prefixes", "description"=>"PSTN prefixes");
$tools_menu['Tools'][] = array("show"=>true,	"icon"=>"fa-sitemap", 	  	"name"=>"MAC lookup",  		 	 "href"=>"mac-lookup", "description"=>"Lookup MAC address vendor");


# Subnets
if($User->settings->enableCustomers == 1 && $User->get_module_permissions ("customers")>=User::ACCESS_R)
$tools_menu[_('Subnets')][] =   ["show"=>true, "icon"=>"fa-users",       "href"=>"customers",                   "name"=>_("Customers"),            "description"=>_("Customers")];
$tools_menu[_('Subnets')][] =   ["show"=>true, "icon"=>"fa-star",        "href"=>"favourites",                  "name"=>_("Favourite networks"),   "description"=>_("Favourite networks")];
$tools_menu[_('Subnets')][] =   ["show"=>true, "icon"=>"fa-sitemap",     "href"=>"subnets",                     "name"=>_("Subnets"),              "description"=>_("All subnets")];
if($User->get_module_permissions ("vlan")>=User::ACCESS_R)
$tools_menu[_('Subnets')][] =   ["show"=>true, "icon"=>"fa-cloud",       "href"=>"vlan",                        "name"=>_("VLAN"),                 "description"=>_("VLANs and belonging subnets")];
if($User->settings->enableVRF == 1 && $User->get_module_permissions ("vrf")>=User::ACCESS_R)
$tools_menu[_('Subnets')][] =   ["show"=>true, "icon"=>"fa-cloud",       "href"=>"vrf",                         "name"=>_("VRF"),                  "description"=>_("VRFs and belonging networks")];
if($User->settings->enableNAT==1 && $User->get_module_permissions ("nat")>=User::ACCESS_R)
$tools_menu[_('Subnets')][] =   ["show"=>true, "icon"=>"fa-exchange",    "href"=>"nat",                         "name"=>_("NAT"),                  "description"=>_("NAT translations")];
if($User->settings->enableRouting==1 && $User->get_module_permissions ("routing")>=User::ACCESS_R)
$tools_menu[_('Subnets')][] =   ["show"=>true, "icon"=>"fa-exchange",    "href"=>"routing",                     "name"=>_("Routing"),              "description"=>_("Routing information")];
if($User->settings->enableMulticast == 1)
$tools_menu[_('Subnets')][] =   ["show"=>true, "icon"=>"fa-map-o",       "href"=>"multicast-networks",          "name"=>_("Multicast networks"),   "description"=>_("Multicast subnets and mapping")];
if($User->settings->enableFirewallZones == 1)
$tools_menu[_('Subnets')][] =   ["show"=>true, "icon"=>"fa-fire",        "href"=>"firewall-zones",              "name"=>_("Firewall Zones"),       "description"=>_("Display firewall zone to device mappings")];
$tools_menu[_('Subnets')][] =   ["show"=>true, "icon"=>"fa-eye",         "href"=>"scanned-networks",            "name"=>_("Scanned networks"),     "description"=>_("List of subnets to be scanned for online hosts and detect new hosts")];
$tools_menu[_('Subnets')][] =   ["show"=>true, "icon"=>"fa-th-large",    "href"=>"subnet-masks",                "name"=>_("Subnet masks"),         "description"=>_("Table of all subnet masks with different representations")];
if($User->settings->tempShare==1)
$tools_menu[_('Subnets')][] =   ["show"=>true, "icon"=>"fa-share-alt",   "href"=>"temp-shares",                 "name"=>_("Temporary shares"),     "description"=>_("List of temporary shared objects")];
$tools_menu[_('Subnets')][] =   ["show"=>true, "icon"=>"fa-thumbs-down", "href"=>"inactive-hosts",              "name"=>_("Inactive Hosts"),       "description"=>_("List of inactive hosts")];
$tools_menu[_('Subnets')][] =   ["show"=>true, "icon"=>"fa-files-o",     "href"=>"duplicates",                  "name"=>_("Duplicates"),           "description"=>_("List of duplicate subnets and addresses")];
if($User->settings->enableThreshold==1)
$tools_menu[_('Subnets')][] =   ["show"=>true, "icon"=>"fa-bullhorn",    "href"=>"threshold",                   "name"=>_("Threshold"),            "description"=>_("List of thresholded subnets")];

# devices
if($User->get_module_permissions ("devices")>=User::ACCESS_R)
$tools_menu[_('Devices')][] =   ["show"=>true, "icon"=>"fa-desktop",     "href"=>"devices",                     "name"=>_("Devices"),              "description"=>_("All configured devices")];
if($User->settings->enableRACK == 1 && $User->get_module_permissions ("racks")>=User::ACCESS_R)
$tools_menu[_('Devices')][] =   ["show"=>true, "icon"=>"fa-bars",        "href"=>"racks",                       "name"=>_("Racks"),                "description"=>_("Rack information")];
if($User->settings->enableCircuits == 1 && $User->get_module_permissions ("circuits")>=User::ACCESS_R)
$tools_menu[_('Devices')][] =   ["show"=>true, "icon"=>"fa-random",      "href"=>"circuits",                    "name"=>_("Circuits"),             "description"=>_("Circuit information")];
if($User->settings->enableLocations == 1 && $User->get_module_permissions ("locations")>=User::ACCESS_R)
$tools_menu[_('Devices')][] =   ["show"=>true, "icon"=>"fa-map",         "href"=>"locations",                   "name"=>_("Locations"),            "description"=>_("Locations")];

# user menu
$tools_menu[_('User Menu')][] = ["show"=>true, "icon"=>"fa-user",        "href"=>"user-menu",                   "name"=>_("My account"),           "description"=>_("Manage your account")];
