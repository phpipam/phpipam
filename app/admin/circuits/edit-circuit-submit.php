<?php

/**
 * Edit provider result
 ***************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database, false);
$Tools	 	= new Tools ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();
# check maintaneance mode
$User->check_maintaneance_mode ();

# check permissions
if(!($User->is_admin(false) || $User->user->editCircuits=="Yes")) { $Result->show("danger", _("You are not allowed to modify Circuit details"), true); }

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "circuit", $_POST['csrf_cookie']) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";
# validate action
$Admin->validate_action ($_POST['action'], true);
# get modified details
$circuit = $Admin->strip_input_tags($_POST);

# IDs must be numeric
if($circuit['action']!="add" && !is_numeric($circuit['id']))					{ $Result->show("danger", _("Invalid ID"), true); }
if(!is_numeric($circuit['provider']))											{ $Result->show("danger", _("Invalid ID"), true); }

# Hostname must be present
if($circuit['cid'] == "") 													{ $Result->show("danger", _('Circuit ID is mandatory').'!', true); }

# validate provider
if($Tools->fetch_object("circuitProviders","id",$circuit['provider'])===false) { $Result->show("danger", _('Invalid provider').'!', true); }

# validate type
$type_desc = $Database->getFieldInfo ("circuits", "type");
$all_types = explode(",", str_replace(array("enum","(",")","'"), "",$type_desc->Type));
if(!in_array($circuit['type'], $all_types))									{ $Result->show("danger", _('Invalid type').'!', true); }

# status
$statuses = array ("Active", "Inactive", "Reserved");
if(!in_array($circuit['status'], $statuses))									{ $Result->show("danger", _('Invalid status').'!', true); }

# process device / location
if($circuit['device1']=="0") {
	$circuit['device1']   = 0;
	$circuit['location1'] = 0;
}
elseif(strpos($circuit['device1'],"device_")!==false) {
	$deviceId = str_replace("device_", "", $circuit['device1']);
	if($Tools->fetch_object("devices","id",$deviceId)===false) 			    { $Result->show("danger", _('Invalid device A').'!', true); }
	// save
	$circuit['device1']   = $deviceId;
	$circuit['location1'] = 0;
}
else {
	$locationId = str_replace("location_", "", $circuit['device1']);
	if($Tools->fetch_object("locations","id",$locationId)===false) 			 { $Result->show("danger", _('Invalid location A').'!', true); }
	// save
	$circuit['device1']   = 0;
	$circuit['location1'] = $locationId;
}

if($circuit['device2']=="0") {
	$circuit['device2']   = 0;
	$circuit['location2'] = 0;
}
elseif(strpos($circuit['device2'],"device_")!==false) {
	$deviceId = str_replace("device_", "", $circuit['device2']);
	if($Tools->fetch_object("devices","id",$deviceId)===false) 			     { $Result->show("danger", _('Invalid device B').'!', true); }
	// save
	$circuit['device2']   = $deviceId;
	$circuit['location2'] = 0;
}
else {
	$locationId = str_replace("location_", "", $circuit['device2']);
	if($Tools->fetch_object("locations","id",$locationId)===false) 			 { $Result->show("danger", _('Invalid location B').'!', true); }
	// save
	$circuit['device2']   = 0;
	$circuit['location2'] = $locationId;
}


# fetch custom fields
$custom = $Tools->fetch_custom_fields('circuits');
if(sizeof($custom) > 0) {
	foreach($custom as $myField) {

		//replace possible ___ back to spaces
		$myField['nameTest'] = str_replace(" ", "___", $myField['name']);
		if(isset($circuit[$myField['nameTest']])) { $circuit[$myField['name']] = $circuit[$myField['nameTest']];}

		//booleans can be only 0 and 1!
		if($myField['type']=="tinyint(1)") {
			if($circuit[$myField['name']]>1) {
				$circuit[$myField['name']] = 0;
			}
		}
		//not null!
		if($myField['Null']=="NO" && strlen($circuit[$myField['name']])==0) { $Result->show("danger", $myField['name'].'" can not be empty!', true); }

		# save to update array
		$update[$myField['name']] = $circuit[$myField['nameTest']];
	}
}

# set update values
$values = array(
				"id"        => $circuit['id'],
				"cid"       => $circuit['cid'],
				"provider"  => $circuit['provider'],
				"type"      => $circuit['type'],
				"capacity"  => $circuit['capacity'],
				"status"    => $circuit['status'],
				"device1"   => $circuit['device1'],
				"location1" => $circuit['location1'],
				"device2"   => $circuit['device2'],
				"location2" => $circuit['location2'],
				"comment"   => $circuit['comment']
				);
# custom fields
if(isset($update)) {
	$values = array_merge($values, $update);
}

# update device
if(!$Admin->object_modify("circuits", $circuit['action'], "id", $values))	{}
else																	{ $Result->show("success", _("Circuit $circuit[action] successfull").'!', false); }
