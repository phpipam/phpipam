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
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "settings", $_POST['csrf_cookie']) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";


//check for http/https
if ( (strpos($_POST['siteURL'],'http://') !== false) || (strpos($_POST['siteURL'],'https://') !== false) ) 	{}
else 																										{ $_POST['siteURL'] = "http://".$_POST['siteURL']; }

//verify ping status fields
$_POST['pingStatus'] = str_replace(" ", "", $_POST['pingStatus']);		//remove possible spaces
$_POST['pingStatus'] = str_replace(",", ";", $_POST['pingStatus']);		//change possible , for ;
$statuses = explode(";", $_POST['pingStatus']);

if(sizeof($statuses)!=2)													{ $Result->show("danger", _("Invalid ping status intervals"), true); }
if(!is_numeric($statuses[0]) || !is_numeric($statuses[1]))					{ $Result->show("danger", _("Invalid ping status intervals"), true); }

//verify email
if(filter_var($_POST['siteAdminMail'], FILTER_VALIDATE_EMAIL) === false)	{ $Result->show("danger", _("Invalid email"), true); }

//verify numbers
if(!is_numeric($_POST['vlanMax']))											{ $Result->show("danger", _("Invalid value for Max VLAN number"), true); }

//verify snmp support
if ($Admin->verify_checkbox(@$_POST['enableSNMP'])==1)
if (!in_array("snmp", get_loaded_extensions()))                             { $Result->show("danger", _("Missing snmp support in php"), true); }

//verify racktables gd support
if ($Admin->verify_checkbox(@$_POST['enableRACK'])==1)
if (!in_array("gd", get_loaded_extensions()))                               { $Result->show("danger", _("Missing gd support in php"), true); }

//remove link_field if None
if ($_POST['link_field']=="None") $_POST['link_field'] = "";

# set update values
$values = array("id"=>1,
				//site settings
				"siteTitle"           =>@$_POST['siteTitle'],
				"siteDomain"          =>@$_POST['siteDomain'],
				"siteURL"             =>@$_POST['siteURL'],
				"siteLoginText"       =>@$_POST['siteLoginText'],
				"prettyLinks"         =>@$_POST['prettyLinks'],
				"defaultLang"         =>@$_POST['defaultLang'],
				"inactivityTimeout"   =>@$_POST['inactivityTimeout'],
				//admin
				"siteAdminName"       =>@$_POST['siteAdminName'],
				"siteAdminMail"       =>@$_POST['siteAdminMail'],
				//features
				"api"                 =>$Admin->verify_checkbox(@$_POST['api']),
				"enableIPrequests"    =>$Admin->verify_checkbox(@$_POST['enableIPrequests']),
				"enableMulticast"     =>$Admin->verify_checkbox(@$_POST['enableMulticast']),
				"enableRACK"          =>$Admin->verify_checkbox(@$_POST['enableRACK']),
				"enableCircuits"      =>$Admin->verify_checkbox(@$_POST['enableCircuits']),
				"enableLocations"     =>$Admin->verify_checkbox(@$_POST['enableLocations']),
				"enableSNMP"          =>$Admin->verify_checkbox(@$_POST['enableSNMP']),
				"enablePSTN"          =>$Admin->verify_checkbox(@$_POST['enablePSTN']),
				"enableThreshold"     =>$Admin->verify_checkbox(@$_POST['enableThreshold']),
				"enableVRF"           =>$Admin->verify_checkbox(@$_POST['enableVRF']),
				"enableDNSresolving"  =>$Admin->verify_checkbox(@$_POST['enableDNSresolving']),
				"vlanDuplicate"       =>$Admin->verify_checkbox(@$_POST['vlanDuplicate']),
				"decodeMAC"       	  =>$Admin->verify_checkbox(@$_POST['decodeMAC']),
				"vlanMax"             =>@$_POST['vlanMax'],
				"pwMin"             =>@$_POST['pwMin'],
				"enableChangelog"     =>$Admin->verify_checkbox(@$_POST['enableChangelog']),
				"tempShare"           =>$Admin->verify_checkbox(@$_POST['tempShare']),
				"enableNAT"           =>$Admin->verify_checkbox(@$_POST['enableNAT']),
				"enablePowerDNS"      =>$Admin->verify_checkbox(@$_POST['enablePowerDNS']),
				"updateTags"          =>$Admin->verify_checkbox(@$_POST['updateTags']),
				"enforceUnique"       =>$Admin->verify_checkbox(@$_POST['enforceUnique']),
				//"enableDHCP"        =>$Admin->verify_checkbox(@$_POST['enableDHCP']),
				"enableFirewallZones" =>$Admin->verify_checkbox(@$_POST['enableFirewallZones']),
				"maintaneanceMode" 	  =>$Admin->verify_checkbox(@$_POST['maintaneanceMode']),
				"permissionPropagate" =>$Admin->verify_checkbox(@$_POST['permissionPropagate']),
				"link_field"          =>@$_POST['link_field'],
				"log"                 =>@$_POST['log'],
				//display
				"donate"              =>$Admin->verify_checkbox(@$_POST['donate']),
				"visualLimit"         =>@$_POST['visualLimit'],
				"theme"         	  =>@$_POST['theme'],
				"subnetOrdering"      =>@$_POST['subnetOrdering'],
				"subnetView"          =>@$_POST['subnetView'],
				//ping
				"scanPingType"        =>@$_POST['scanPingType'],
				"pingStatus"          =>@$_POST['pingStatus'],
				"scanPingPath"        =>@$_POST['scanPingPath'],
				"scanFPingPath"       =>@$_POST['scanFPingPath'],
				"scanMaxThreads"      =>@$_POST['scanMaxThreads']
				);
if(!$Admin->object_modify("settings", "edit", "id", $values))	{ $Result->show("danger",  _("Cannot update settings"), true); }
else															{ $Result->show("success", _("Settings updated successfully"), true); }
