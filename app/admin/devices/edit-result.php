<?php

/**
 * Edit switch result
 ***************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database, false);
$Tools	 	= new Tools ($Database);
$Racks      = new phpipam_rack ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();
# perm check popup
if($_POST['action']=="edit") {
    $User->check_module_permissions ("devices", User::ACCESS_RW, true, false);
}
else {
    $User->check_module_permissions ("devices", User::ACCESS_RWA, true, false);
}

# check maintaneance mode
$User->check_maintaneance_mode ();

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "device", $_POST['csrf_cookie']) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# get modified details
$device = $Admin->strip_input_tags($_POST);

# ID must be numeric
if($_POST['action']!="add" && !is_numeric($_POST['switchid']))			{ $Result->show("danger", _("Invalid ID"), true); }

# available devices set
foreach($device as $key=>$line) {
	if (!is_blank(strstr($key,"section-"))) {
		$key2 = str_replace("section-", "", $key);
		$temp[] = $key2;

		unset($device[$key]);
	}
}
# glue sections together
$device['sections'] = !empty($temp) ? implode(";", $temp) : null;

# Hostname must be present
if($device['hostname'] == "") 											{ $Result->show("danger", _('Hostname is mandatory').'!', true); }

# rack checks
if (!is_blank(@$device['rack']) && $User->get_module_permissions ("racks")>=User::ACCESS_R) {
    if ($User->settings->enableRACK!="1") {
        unset($device['rack']);
    }
    else {
        # validate position and size
        if (!is_numeric($device['rack']))                               { $Result->show("danger", _('Invalid rack identifier').'!', true); }
        if (!is_numeric($device['rack_start']))                         { $Result->show("danger", _('Invalid rack start position').'!', true); }
        if (!is_numeric($device['rack_size']))                          { $Result->show("danger", _('Invalid rack size').'!', true); }
        # validate rack
        $rack = $Racks->fetch_rack_details ($device['rack']);
        if ($rack===false)                                              { $Result->show("danger", _('Rack does not exist').'!', true); }
    }
}

# fetch custom fields
$custom = $Tools->fetch_custom_fields('devices');
if(sizeof($custom) > 0) {
	foreach($custom as $myField) {

		//replace possible ___ back to spaces
		$myField['nameTest'] = str_replace(" ", "___", $myField['name']);
		if(isset($_POST[$myField['nameTest']])) { $_POST[$myField['name']] = $_POST[$myField['nameTest']];}

		//booleans can be only 0 and 1!
		if($myField['type']=="tinyint(1)") {
			if($device[$myField['name']]>1) {
				$device[$myField['name']] = 0;
			}
		}
		//not null!
		if($myField['Null']=="NO" && is_blank($device[$myField['name']])) { $Result->show("danger", $myField['name']." "._("can not be empty!"), true); }

		# save to update array
		$update[$myField['name']] = $device[$myField['nameTest']];
	}
}

# set update values
$values = array(
				"id"          =>$device['switchid'],
				"hostname"    =>$device['hostname'],
				"ip_addr"     =>$device['ip_addr'],
				"type"        =>$device['type'],
				"description" =>$device['description'],
				"sections"    =>$device['sections'],
				"location"    =>@$device['location']
				);
# custom fields
if(isset($update)) {
	$values = array_merge($values, $update);
}
# rack
if (!is_blank(@$device['rack']) && $User->get_module_permissions ("racks")>=User::ACCESS_R) {
	$values['rack']       = $device['rack'];
	$values['rack_start'] = $device['rack_start'];
	$values['rack_size']  = $device['rack_size'];
}
# perms
if ($User->get_module_permissions ("locations")==User::ACCESS_NONE) {
	unset ($values['location']);
}

# update device
if(!$Admin->object_modify("devices", $_POST['action'], "id", $values))	{}
else { $Result->show("success", _("Device")." ".$device["action"]." "._("successful").'!', false); }

if($_POST['action']=="delete"){
	# remove all references from subnets and ip addresses
	$Admin->remove_object_references ("subnets", "device", $values["id"]);
	$Admin->remove_object_references ("nat", "device", $values["id"]);
	$Admin->remove_object_references ("ipaddresses", "switch", $values["id"]);
	$Admin->remove_object_references ("pstnPrefixes", "deviceId", $values["id"]);
	$Admin->remove_object_references ("pstnNumbers", "deviceId", $values["id"]);
	$Admin->remove_object_references ("circuits", "device1", $values["id"]);
	$Admin->remove_object_references ("circuits", "device2", $values["id"]);
}
