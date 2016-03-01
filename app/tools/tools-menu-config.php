<?php

/**
 * Tools menu items
 *
 */

# icons
$tools_menu_icons['Tools'] 		= "fa-wrench";
$tools_menu_icons['Subnets'] 	= "fa-sitemap";
$tools_menu_icons['User Menu'] 	= "fa-user";

# Tools
$tools_menu['Tools'][] = array("show"=>true,	"icon"=>"fa-search", 		"name"=>"Search", 		 		"href"=>"search", 		"description"=>"Search database Addresses, subnets and VLANs");
$tools_menu['Tools'][] = array("show"=>true,	"icon"=>"fa-calculator",	"name"=>"IP calculator", 		"href"=>"ip-calculator","description"=>"IPv4v6 calculator for subnet calculations");
if($User->settings->enableChangelog == 1)
$tools_menu['Tools'][] = array("show"=>true,	"icon"=>"fa-clock-o", 		"name"=>"Changelog", 	 		"href"=>"changelog", 	"description"=>"Show changelog for all network objects");
$tools_menu['Tools'][] = array("show"=>true,	"icon"=>"fa-list", 			"name"=>"Log files", 			"href"=>"logs",		 	"description"=>"Browse phpipam log files");
if($User->settings->enableIPrequests==1) {
$tools_menu['Tools'][] = array("show"=>true,	"icon"=>"fa-plus", 			"name"=>"IP requests", 			"href"=>"requests", 	"description"=>"Manage IP requests");
}
$tools_menu['Tools'][] = array("show"=>true,	"icon"=>"fa-info", 	  		"name"=>"Instructions",  		"href"=>"instructions", "description"=>"Instructions for managing IP addresses");
if($User->settings->enablePowerDNS==1)
$tools_menu['Tools'][] = array("show"=>true,	"icon"=>"fa-database", 	  		"name"=>"PowerDNS",  		"href"=>"powerDNS", "description"=>"PowerDNS settings");

# Subnets
$tools_menu['Subnets'][] 	= array("show"=>true,	"icon"=>"fa-star", 	  	"name"=>"Favourite networks",  	"href"=>"favourites", 	"description"=>"Show favourite networks");
$tools_menu['Subnets'][] 	= array("show"=>true,	"icon"=>"fa-sitemap", 	"name"=>"Subnets",  		   	"href"=>"subnets", 		"description"=>"Show all subnets");
$tools_menu['Subnets'][] 	= array("show"=>true,	"icon"=>"fa-cloud", 	"name"=>"VLAN",  				"href"=>"vlan", 		"description"=>"Show VLANs and belonging subnets");
if($User->settings->enableVRF == 1)
$tools_menu['Subnets'][] 	= array("show"=>true,	"icon"=>"fa-cloud", 	 "name"=>"VRF",  				"href"=>"vrf", 			"description"=>"Show VRFs and belonging networks");
if($User->settings->enableRACK == 1)
$tools_menu['Subnets'][] 	= array("show"=>true,	"icon"=>"fa-bars", 	     "name"=>"Racks",  				"href"=>"racks", 		"description"=>"Show racks");
$tools_menu['Subnets'][] 	= array("show"=>true,	"icon"=>"fa-desktop", 	 "name"=>"Devices",  			"href"=>"devices", 		"description"=>"Show all configured devices");
if($User->settings->enableMulticast == 1)
$tools_menu['Subnets'][] 	= array("show"=>true,	"icon"=>"fa-map-o",		"name"=>"Multicast networks", 	"href"=>"multicast-networks", "description"=>"Show multicast subnets and mapping");
if($User->settings->enableFirewallZones == 1)
$tools_menu['Subnets'][] 	= array("show"=>true,	"icon"=>"fa-fire",		"name"=>"Firewall Zones", 		"href"=>"firewall-zones", "description"=>"Display firewall zone to device mappings");
$tools_menu['Subnets'][] 	= array("show"=>true,	"icon"=>"fa-eye", 		 "name"=>"Scanned networks", 	"href"=>"scanned-networks",	"description"=>"List of subnets to be scanned for online hosts and detect new hosts");
$tools_menu['Subnets'][] 	= array("show"=>true,	"icon"=>"fa-th-large", 	 "name"=>"Subnet masks", 		"href"=>"subnet-masks",	"description"=>"Table of all subnet masks with different representations");
// temp shares
if($User->settings->tempShare==1)
$tools_menu['Subnets'][] 	= array("show"=>true,	"icon"=>"fa-share-alt",  "name"=>"Temporary shares", 	"href"=>"temp-shares",	"description"=>"List of temporary shared objects");

# user menu
$tools_menu['User Menu'][] = array("show"=>true,	"icon"=>"fa-user", 		"name"=>"My account",  			"href"=>"user-menu", 	"description"=>"Manage your account");
?>