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

# perm check popup
if($POST->action=="edit") {
    $User->check_module_permissions ("circuits", User::ACCESS_RW, true, false);
}
else {
    $User->check_module_permissions ("circuits", User::ACCESS_RWA, true, false);
}

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "circuit", $POST->csrf_cookie) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# validate action
$Admin->validate_action();

# IDs must be numeric
if($POST->action!="add" && !is_numeric($POST->id))					{ $Result->show("danger", _("Invalid ID"), true); }
if(!is_numeric($POST->provider))											{ $Result->show("danger", _("Invalid ID"), true); }

# Hostname must be present
if($POST->cid == "") 													{ $Result->show("danger", _('Circuit ID is mandatory').'!', true); }

# validate provider
if($Tools->fetch_object("circuitProviders","id",$POST->provider)===false) { $Result->show("danger", _('Invalid provider').'!', true); }

# validate type
$all_types = $Tools->fetch_all_objects ("circuitTypes", "ctname");
$type_id_array = [];
foreach($all_types as $t){ array_push($type_id_array, $t->id); }

if(!in_array($POST->type, $type_id_array))									{ $Result->show("danger", _('Invalid type').'!', true); }

# status
$statuses = array ("Active", "Inactive", "Reserved");
if(!in_array($POST->status, $statuses))									{ $Result->show("danger", _('Invalid status').'!', true); }

#Check if circuit is part of a larger circuit
if($POST->action == 'delete'){
	$logical_circuit_array = $Tools->fetch_all_logical_circuits_using_circuit($POST->id);
	if(!empty($logical_circuit_array))  		{ $Result->show("danger", _('Circuit is currently used in a larger logical circuit').'!', true); }

}



# process device / location
if($POST->device1=="0") {
	$POST->device1   = 0;
	$POST->location1 = 0;
}
elseif(strpos($POST->device1,"device_")!==false) {
	$deviceId = str_replace("device_", "", $POST->device1);
	if($Tools->fetch_object("devices","id",$deviceId)===false) 			    { $Result->show("danger", _('Invalid device A').'!', true); }
	// save
	$POST->device1   = $deviceId;
	$POST->location1 = 0;
}
else {
	$locationId = str_replace("location_", "", $POST->device1);
	if($Tools->fetch_object("locations","id",$locationId)===false) 			 { $Result->show("danger", _('Invalid location A').'!', true); }
	// save
	$POST->device1   = 0;
	$POST->location1 = $locationId;
}

if($POST->device2=="0") {
	$POST->device2   = 0;
	$POST->location2 = 0;
}
elseif(strpos($POST->device2,"device_")!==false) {
	$deviceId = str_replace("device_", "", $POST->device2);
	if($Tools->fetch_object("devices","id",$deviceId)===false) 			     { $Result->show("danger", _('Invalid device B').'!', true); }
	// save
	$POST->device2   = $deviceId;
	$POST->location2 = 0;
}
else {
	$locationId = str_replace("location_", "", $POST->device2);
	if($Tools->fetch_object("locations","id",$locationId)===false) 			 { $Result->show("danger", _('Invalid location B').'!', true); }
	// save
	$POST->device2   = 0;
	$POST->location2 = $locationId;
}

# set update values
$values = array(
				"id"        => $POST->id,
				"cid"       => $POST->cid,
  				"provider"  => $POST->provider,
  				"type"      => $POST->type,
  				"capacity"  => $POST->capacity,
  				"status"    => $POST->status,
  				"device1"   => $POST->device1,
  				"location1" => $POST->location1,
  				"device2"   => $POST->device2,
  				"location2" => $POST->location2,
  				"comment"   => $POST->comment
				);

# fetch custom fields
$update = $Tools->update_POST_custom_fields('circuits', $POST->action, $POST);
$values = array_merge($values, $update);

# append customerId
if($User->settings->enableCustomers=="1" && $User->get_module_permissions ("customers")>=User::ACCESS_RW) {
	if (is_numeric($POST->customer_id)) {
	       $values['customer_id'] = $POST->customer_id > 0 ? $POST->customer_id : NULL;
	}
}

# update
if ($Admin->object_modify("circuits", $POST->action, "id", $values)) {
	$Result->show("success", _("Circuit") . " " .  $User->get_post_action() . " " . _("successful") . "!", false);
}
