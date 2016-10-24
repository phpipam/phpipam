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
						'pstn-prefixes'
                    );


#custom
if (isset($private_subpages)) {
    if(is_array($private_subpages)) {
        if (sizeof($private_subpages)>0) {
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
    }
}

# arrays
$tools_menu['Tools']     = array();
$tools_menu['Subnets']   = array();
$tools_menu['User Menu'] = array();

# Tools
$tools_menu['Tools'][] = array("show"=>true,	"icon"=>"fa-search", 		"name"=>"Search", 		 		"href"=>"search", 		"description"=>"Search database Addresses, subnets and VLANs");
$tools_menu['Tools'][] = array("show"=>true,	"icon"=>"fa-calculator",	"name"=>"IP calculator", 		"href"=>"ip-calculator","description"=>"IPv4v6 calculator for subnet calculations");
if($User->settings->enableChangelog == 1)
$tools_menu['Tools'][] = array("show"=>true,	"icon"=>"fa-clock-o", 		"name"=>"Changelog", 	 		"href"=>"changelog", 	"description"=>"Changelog for all network objects");
$tools_menu['Tools'][] = array("show"=>true,	"icon"=>"fa-list", 			"name"=>"Log files", 			"href"=>"logs",		 	"description"=>"Browse phpipam log files");
if($User->settings->enableIPrequests==1) {
$tools_menu['Tools'][] = array("show"=>true,	"icon"=>"fa-plus", 			"name"=>"IP requests", 			"href"=>"requests", 	"description"=>"Manage IP requests");
}
$tools_menu['Tools'][] = array("show"=>true,	"icon"=>"fa-info", 	  		"name"=>"Instructions",  		"href"=>"instructions", "description"=>"Instructions for managing IP addresses");
if($User->settings->enablePowerDNS==1)
$tools_menu['Tools'][] = array("show"=>true,	"icon"=>"fa-database", 	  	"name"=>"PowerDNS",  		    "href"=>"powerDNS", "description"=>"PowerDNS");
if($User->settings->enableDHCP==1)
$tools_menu['Tools'][] = array("show"=>true,	"icon"=>"fa-database", 	  	"name"=>"DHCP",  		        "href"=>"dhcp", "description"=>"DHCP information");
if($User->settings->enablePSTN==1)
$tools_menu['Tools'][] = array("show"=>true,	"icon"=>"fa-phone", 	  	"name"=>"PSTN prefixes",  		 "href"=>"pstn-prefixes", "description"=>"PSTN prefixes");

# Subnets
$tools_menu['Subnets'][] 	= array("show"=>true,	"icon"=>"fa-star", 	  	"name"=>"Favourite networks",  	"href"=>"favourites", 	"description"=>"Favourite networks");
$tools_menu['Subnets'][] 	= array("show"=>true,	"icon"=>"fa-sitemap", 	"name"=>"Subnets",  		   	"href"=>"subnets", 		"description"=>"All subnets");
$tools_menu['Subnets'][] 	= array("show"=>true,	"icon"=>"fa-cloud", 	"name"=>"VLAN",  				"href"=>"vlan", 		"description"=>"VLANs and belonging subnets");
if($User->settings->enableVRF == 1)
$tools_menu['Subnets'][] 	= array("show"=>true,	"icon"=>"fa-cloud", 	 "name"=>"VRF",  				"href"=>"vrf", 			"description"=>"VRFs and belonging networks");
if($User->settings->enableNAT==1)
$tools_menu['Subnets'][] = array("show"=>true,	"icon"=>"fa-exchange", 	      "name"=>"NAT", 				"href"=>"nat", 				  "description"=>"NAT translations");

if($User->settings->enableRACK == 1)
$tools_menu['Subnets'][] 	= array("show"=>true,	"icon"=>"fa-bars", 	     "name"=>"Racks",  				"href"=>"racks", 		"description"=>"Rack information");
if($User->settings->enableLocations == 1)
$tools_menu['Subnets'][] 	= array("show"=>true,	"icon"=>"fa-map", 	     "name"=>"Locations",  			"href"=>"locations", 	"description"=>"Locations");
$tools_menu['Subnets'][] 	= array("show"=>true,	"icon"=>"fa-desktop", 	 "name"=>"Devices",  			"href"=>"devices", 		"description"=>"All configured devices");
if($User->settings->enableMulticast == 1)
$tools_menu['Subnets'][] 	= array("show"=>true,	"icon"=>"fa-map-o",		"name"=>"Multicast networks", 	"href"=>"multicast-networks", "description"=>"Multicast subnets and mapping");
if($User->settings->enableFirewallZones == 1)
$tools_menu['Subnets'][] 	= array("show"=>true,	"icon"=>"fa-fire",		"name"=>"Firewall Zones", 		"href"=>"firewall-zones", "description"=>"Display firewall zone to device mappings");
$tools_menu['Subnets'][] 	= array("show"=>true,	"icon"=>"fa-eye", 		 "name"=>"Scanned networks", 	"href"=>"scanned-networks",	"description"=>"List of subnets to be scanned for online hosts and detect new hosts");
$tools_menu['Subnets'][] 	= array("show"=>true,	"icon"=>"fa-th-large", 	 "name"=>"Subnet masks", 		"href"=>"subnet-masks",	"description"=>"Table of all subnet masks with different representations");
// temp shares
if($User->settings->tempShare==1)
$tools_menu['Subnets'][] 	= array("show"=>true,	"icon"=>"fa-share-alt",  "name"=>"Temporary shares", 	"href"=>"temp-shares",	"description"=>"List of temporary shared objects");
$tools_menu['Subnets'][] 	= array("show"=>true,	"icon"=>"fa-thumbs-down",  "name"=>"Inactive Hosts", 	"href"=>"inactive-hosts",	"description"=>"List of inactive hosts");
if($User->settings->enableThreshold==1)
$tools_menu['Subnets'][] 	= array("show"=>true,	"icon"=>"fa-bullhorn",  "name"=>"Threshold", 	"href"=>"threshold",	"description"=>"List of thresholded subnets");


# user menu
$tools_menu['User Menu'][] = array("show"=>true,	"icon"=>"fa-user", 		"name"=>"My account",  			"href"=>"user-menu", 	"description"=>"Manage your account");

?>