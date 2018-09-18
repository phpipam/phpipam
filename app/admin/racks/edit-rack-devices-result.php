<?php

/**
 * Edit rack devices result
 ***************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database, false);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();
# verify module permissions
$User->check_module_permissions ("racks", 2, true, false);
$User->check_module_permissions ("devices", 1, true, false);

# check maintaneance mode
$User->check_maintaneance_mode ();

# strip input tags
$_POST = $Admin->strip_input_tags($_POST);

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "rack_devices", $_POST['csrf_cookie']) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# device type?
if (!isset($_POST['devicetype']) || (($_POST['devicetype'] != 'device') && ($_POST['devicetype'] != 'content'))) { $Result->show("danger", _("Invalid device type"), true, true); }

# ID must be numeric
if(!is_numeric($_POST['rackid']))			                              { $Result->show("danger", _("Invalid ID"), true); }
if(($_POST['devicetype'] == 'device') && !is_numeric($_POST['deviceid'])) { $Result->show("danger", _("Invalid ID"), true); }
if(!is_numeric($_POST['rack_start']))			                          { $Result->show("danger", _("Invalid start value"), true); }
if(!is_numeric($_POST['rack_size']))			                          { $Result->show("danger", _("Invalid size value"), true); }

# name
if (($_POST['devicetype'] == 'content') && (!isset($_POST['name']) || (trim($_POST['name'] === '')))) { $Result->show("danger", _("Invalid device name"), true); }

# validate rack
$rack = $Admin->fetch_object("racks", "id", $_POST['rackid']);
if ($rack===false)                                                     { $Result->show("danger", _("Invalid ID"), true); }

# check size
if($rack->hasBack!="0") {
	if($_POST['rack_start']+($_POST['rack_size']-1)>(2*$rack->size))   { $Result->show("danger", _("Invalid rack position (overflow)"), true); }
}
else {
	if($_POST['rack_start']+($_POST['rack_size']-1)>$rack->size)       { $Result->show("danger", _("Invalid rack position (overflow)"), true); }
}

switch ($_POST['devicetype']) {
    case 'device':
    # validate device
    $device = $Admin->fetch_object("devices", "id", $_POST['deviceid']);
    if ($device===false)                                                   { $Result->show("danger", _("Invalid Device ID"), true); }

    # set update values
    $values = array("id"=>@$_POST['deviceid'],
				    "rack"=>@$_POST['rackid'],
				    "rack_start"=>@$_POST['rack_start'],
				    "rack_size"=>@$_POST['rack_size'],
				    );

    # inherit location if it is not already set
    if ($User->settings->enableLocations=="1" && @$_POST['no_location']!="1") {
	    if (($device->location=="0" || is_null($device->location)) && ($rack->location!="0" && !is_null($rack->location))) {
		    $values['location'] = $rack->location;
	    }
    }

    # update rack
    if(!$Admin->object_modify("devices", "edit", "id", $values))	        { $Result->show("success", _("Failed to add device to rack").'!', false); }
    else																	{ $Result->show("success", _("Device added to rack").'!', false); }
    break;

    case 'content':
    # set values
    $values = array("name"=>@$_POST['name'],
                    "rack"=>@$_POST['rackid'],
                    "rack_start"=>@$_POST['rack_start'],
                    "rack_size"=>@$_POST['rack_size'],
                    );

    # add content
    if (!$Admin->object_modify('rackContents', 'add', null, $values))       { $Result->show("success", _("Failed to add device to rack").'!', false); }
    else                                                                    { $Result->show("success", _("Device added to rack").'!', false); }
    break;
}