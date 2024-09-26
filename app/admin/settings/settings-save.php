<?php

/**
 *	Site settings
 **************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Tools	 	= new Tools ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();
# check if site is demo
$User->is_demo();

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "settings", $POST->csrf_cookie) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";


//check for http/https
if ( (strpos($POST->siteURL,'http://') !== false) || (strpos($POST->siteURL,'https://') !== false) ) 	{}
else 																										{ $POST->siteURL = "http://".$POST->siteURL; }

//verify ping status fields
$POST->pingStatus = str_replace(" ", "", $POST->pingStatus);		//remove possible spaces
$POST->pingStatus = str_replace(",", ";", $POST->pingStatus);		//change possible , for ;
$statuses = pf_explode(";", $POST->pingStatus);

if(sizeof($statuses)!=2)													{ $Result->show("danger", _("Invalid ping status intervals"), true); }
if(!is_numeric($statuses[0]) || !is_numeric($statuses[1]))					{ $Result->show("danger", _("Invalid ping status intervals"), true); }

//verify email
if(filter_var($POST->siteAdminMail, FILTER_VALIDATE_EMAIL) === false)	{ $Result->show("danger", _("Invalid email"), true); }

//verify numbers
if(!is_numeric($POST->vlanMax))											{ $Result->show("danger", _("Invalid value for Max VLAN number"), true); }

//verify snmp support
if ($Admin->verify_checkbox($POST->enableSNMP)==1)
if (!in_array("snmp", get_loaded_extensions()))                             { $Result->show("danger", _("Missing snmp support in php"), true); }

//verify racktables gd support
if ($Admin->verify_checkbox($POST->enableRACK)==1)
if (!in_array("gd", get_loaded_extensions()))                               { $Result->show("danger", _("Missing gd support in php"), true); }

//remove link_field if None
if ($POST->link_field=="None") $POST->link_field = "";

# set update values
$values = array("id"=>1,
				//site settings
				"siteTitle"           =>$POST->siteTitle,
				"siteDomain"          =>$POST->siteDomain,
				"siteURL"             =>$POST->siteURL,
				"siteLoginText"       =>$POST->siteLoginText,
				"prettyLinks"         =>$POST->prettyLinks,
				"defaultLang"         =>$POST->defaultLang,
				"inactivityTimeout"   =>$POST->inactivityTimeout,
				//admin
				"siteAdminName"       =>$POST->siteAdminName,
				"siteAdminMail"       =>$POST->siteAdminMail,
				//features
				"api"                 =>$Admin->verify_checkbox($POST->api),
				"enableIPrequests"    =>$Admin->verify_checkbox($POST->enableIPrequests),
				"enableMulticast"     =>$Admin->verify_checkbox($POST->enableMulticast),
				"enableRACK"          =>$Admin->verify_checkbox($POST->enableRACK),
				"enableCircuits"      =>$Admin->verify_checkbox($POST->enableCircuits),
				"enableLocations"     =>$Admin->verify_checkbox($POST->enableLocations),
				"enableSNMP"          =>$Admin->verify_checkbox($POST->enableSNMP),
				"enablePSTN"          =>$Admin->verify_checkbox($POST->enablePSTN),
				"enableCustomers"     =>$Admin->verify_checkbox($POST->enableCustomers),
				"enableThreshold"     =>$Admin->verify_checkbox($POST->enableThreshold),
				"enableVRF"           =>$Admin->verify_checkbox($POST->enableVRF),
				"enableDNSresolving"  =>$Admin->verify_checkbox($POST->enableDNSresolving),
				"vlanDuplicate"       =>$Admin->verify_checkbox($POST->vlanDuplicate),
				"decodeMAC"       	  =>$Admin->verify_checkbox($POST->decodeMAC),
				"vlanMax"             =>$POST->vlanMax,
				"enableChangelog"     =>$Admin->verify_checkbox($POST->enableChangelog),
				"tempShare"           =>$Admin->verify_checkbox($POST->tempShare),
				"enableNAT"           =>$Admin->verify_checkbox($POST->enableNAT),
				"enablePowerDNS"      =>$Admin->verify_checkbox($POST->enablePowerDNS),
				"updateTags"          =>$Admin->verify_checkbox($POST->updateTags),
				"enforceUnique"       =>$Admin->verify_checkbox($POST->enforceUnique),
				"enableRouting"       =>$Admin->verify_checkbox($POST->enableRouting),
				"enableVaults"        =>$Admin->verify_checkbox($POST->enableVaults),
				"passkeys"            =>$Admin->verify_checkbox($POST->passkeys),
				//"enableDHCP"        =>$Admin->verify_checkbox($POST->enableDHCP),
				"enableFirewallZones" =>$Admin->verify_checkbox($POST->enableFirewallZones),
				"maintaneanceMode" 	  =>$Admin->verify_checkbox($POST->maintaneanceMode),
				"permissionPropagate" =>$Admin->verify_checkbox($POST->permissionPropagate),
				"link_field"          =>$POST->link_field,
				"log"                 =>$POST->log,
				//display
				"donate"              =>$Admin->verify_checkbox($POST->donate),
				"visualLimit"         =>$POST->visualLimit,
				"theme"         	  =>$POST->theme,
				"subnetOrdering"      =>$POST->subnetOrdering,
				"subnetView"          =>$POST->subnetView,
				//ping
				"scanPingType"        =>$POST->scanPingType,
				"pingStatus"          =>$POST->pingStatus,
				"scanPingPath"        =>$POST->scanPingPath,
				"scanFPingPath"       =>$POST->scanFPingPath,
				"scanMaxThreads"      =>$POST->scanMaxThreads
				);
// Update linked_field indexes
$Tools->verify_linked_field_indexes($POST->link_field);

if(!$Admin->object_modify("settings", "edit", "id", $values))	{ $Result->show("danger",  _("Cannot update settings"), true); }
else															{ $Result->show("success", _("Settings updated successfully"), true); }