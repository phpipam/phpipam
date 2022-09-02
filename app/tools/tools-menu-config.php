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
						'swstandards',
						'test',
						'dhcp',
						'vendors',
						'hwstatus',
						'hwowners',
						'hwtypes',
						'hwmodels',
						'hardware',
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
						'duplicates',
						'gensiteips',
						'routing',
						'contacts'
                    );


#custom
$private_subpages = Config::get('private_subpages');
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
$tools_menu['Locations']   = array();
$tools_menu['Devices']   = array();
$tools_menu['Tools']     = array();
$tools_menu['User Menu'] = array();

# Tools
$tools_menu['Tools'][] = array("show"=>true,	"icon"=>"fa-search", 		"name"=>"Search", 		 		"href"=>"search", 										"description"=>"Search database Addresses, subnets and VLANs");
$tools_menu['Tools'][] = array("show"=>true,	"icon"=>"fa-sitemap", 		"name"=>"Generate Site IPs", 	"href"=>"gensiteips", 									"description"=>"Generate Site IPs");
$tools_menu['Tools'][] = array("show"=>true,	"icon"=>"fa-sitemap", 		"name"=>"Test", 				"href"=>"test", 										"description"=>"Test");
$tools_menu['Tools'][] = array("show"=>true,	"icon"=>"fa-calculator",	"name"=>"IP calculator", 		"href"=>"phpipam/tools/ip-calculator",					"description"=>"IPv4v6 calculator for subnet calculations");
$tools_menu['Tools'][] = array("show"=>true,	"icon"=>"fa-calculator",	"name"=>"Bandwidth calculator", "href"=>"phpipam/tools/ip-calculator/bw-calculator",	"description"=>"Bandwidth calculator");
$tools_menu['Tools'][] = array("show"=>true,	"icon"=>"fa-database", 	  	"name"=>"DHCP",  		        "href"=>"phpipam/tools/dhcp", 							"description"=>"DHCP information");
if($User->settings->enablePSTN==1 && $User->get_module_permissions ("pstn")>0)
$tools_menu['Tools'][] = array("show"=>true,	"icon"=>"fa-sitemap", 	  	"name"=>"MAC lookup",  		 	"href"=>"phpipam/tools/mac-lookup", 					"description"=>"Lookup MAC address vendor");
$tools_menu['Tools'][] 	= array("show"=>true,	"icon"=>"fa-thumbs-down",  	"name"=>"Inactive Hosts", 		"href"=>"phpipam/tools/inactive-hosts",					"description"=>"List of inactive hosts");
$tools_menu['Tools'][] 	= array("show"=>true,	"icon"=>"fa-files-o",  		"name"=>"Duplicates", 			"href"=>"phpipam/tools/duplicates",						"description"=>"List of duplicate subnets and addresses");
$tools_menu['Tools'][] 	= array("show"=>true,	"icon"=>"fa-th-large", 	 	"name"=>"Subnet masks", 		"href"=>"phpipam/tools/subnet-masks",					"description"=>"Table of all subnet masks with different representations");


# Devices
if($User->get_module_permissions ("devices")>0)
$tools_menu['Devices'][] 	= array("show"=>true,	"icon"=>"fa-desktop", 	 "name"=>"HW Owners",  			"href"=>"hwowners", 						"description"=>"All HW Owners");
if($User->get_module_permissions ("devices")>0)
$tools_menu['Devices'][] 	= array("show"=>true,	"icon"=>"fa-desktop", 	 "name"=>"HW Status",  			"href"=>"hwstatus", 						"description"=>"All HW Status");
if($User->get_module_permissions ("devices")>0)
$tools_menu['Devices'][] 	= array("show"=>true,	"icon"=>"fa-desktop", 	 "name"=>"HW Types",  			"href"=>"hwtypes", 							"description"=>"All HW Types");
if($User->get_module_permissions ("devices")>0)
$tools_menu['Devices'][] 	= array("show"=>true,	"icon"=>"fa-desktop", 	 "name"=>"HW Models",  			"href"=>"hwmodels", 						"description"=>"All HW Models");
if($User->get_module_permissions ("devices")>0)
$tools_menu['Devices'][] 	= array("show"=>true,	"icon"=>"fa-desktop", 	 "name"=>"Hardware",  			"href"=>"hardware", 						"description"=>"All Hardware");
if($User->get_module_permissions ("devices")>0)
$tools_menu['Devices'][] 	= array("show"=>true,	"icon"=>"fa-desktop", 	 "name"=>"Devices",  			"href"=>"devices", 							"description"=>"All configured devices");
if($User->get_module_permissions ("devices")>0)
$tools_menu['Devices'][] 	= array("show"=>true,	"icon"=>"fa-desktop", 	 "name"=>"SW Standards",  		"href"=>"swstandards", 						"description"=>"All Software Standards");
if($User->settings->enableVRF == 1 && $User->get_module_permissions ("vrf")>0)
$tools_menu['Devices'][] 	= array("show"=>true,	"icon"=>"fa-cloud", 	 "name"=>"VRF",  				"href"=>"vrf", 								"description"=>"VRFs and belonging networks");
if($User->get_module_permissions ("vlan")>0)
$tools_menu['Devices'][] 	= array("show"=>true,	"icon"=>"fa-cloud", 	 "name"=>"VLAN",  				"href"=>"vlan", 							"description"=>"VLANs and belonging subnets");
if($User->settings->enableCircuits == 1 && $User->get_module_permissions ("circuits")>0)
$tools_menu['Devices'][] 	= array("show"=>true,	"icon"=>"fa-random", 	 "name"=>"Circuits",  			"href"=>"circuits", 						"description"=>"Circuit information");
if($User->settings->enableRouting==1 && $User->get_module_permissions ("routing")>0)
$tools_menu['Devices'][] = array("show"=>true,	"icon"=>"fa-exchange", 	     "name"=>"Routing", 			"href"=>"phpipam/tools/routing", 			"description"=>"Routing information");
if($User->settings->enableMulticast == 1)
$tools_menu['Devices'][] 	= array("show"=>true,	"icon"=>"fa-map-o",		 "name"=>"Multicast networks", 	"href"=>"phpipam/tools/multicast-networks", "description"=>"Multicast subnets and mapping");
if($User->settings->enableFirewallZones == 1)
$tools_menu['Devices'][] 	= array("show"=>true,	"icon"=>"fa-fire",		 "name"=>"Firewall Zones", 		"href"=>"phpipam/tools/firewall-zones", 	"description"=>"Display firewall zone to device mappings");
$tools_menu['Devices'][] 	= array("show"=>true,	"icon"=>"fa-fire",		 "name"=>"Vendors", 			"href"=>"vendors", 							"description"=>"All Vendors");


# Locations
if($User->settings->enableLocations == 1 && $User->get_module_permissions ("locations")>0)
$tools_menu['Locations'][] 	= array("show"=>true,	"icon"=>"fa-map", 	     "name"=>"Locations",  			"href"=>"locations", 						"description"=>"Locations");
if($User->settings->enableLocations == 1 && $User->get_module_permissions ("locations")>0)
$tools_menu['Locations'][] 	= array("show"=>true,	"icon"=>"fa-map", 	     "name"=>"Location Contacts",  	"href"=>"contacts", 						"description"=>"Locations");
if($User->settings->enableRACK == 1 && $User->get_module_permissions ("racks")>0)
$tools_menu['Locations'][] 	= array("show"=>true,	"icon"=>"fa-bars", 	     "name"=>"Racks",  				"href"=>"racks", 							"description"=>"Rack information");


# user menu
$tools_menu['User Menu'][] = array("show"=>true,	"icon"=>"fa-user", 		"name"=>"My account",  			"href"=>"phpipam/tools/user-menu", 			"description"=>"Manage your account");