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
$User->check_module_permissions ("racks", User::ACCESS_RW, true, false);
$User->check_module_permissions ("devices", User::ACCESS_R, true, false);

# check maintaneance mode
$User->check_maintaneance_mode ();

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "rack_devices", $POST->csrf_cookie) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# device type?
if (!isset($POST->devicetype) || (($POST->devicetype != 'device') && ($POST->devicetype != 'content'))) { $Result->show("danger", _("Invalid device type"), true, true); }

# ID must be numeric
if(!is_numeric($POST->rackid))			                              { $Result->show("danger", _("Invalid ID"), true); }
if(($POST->devicetype == 'device') && !is_numeric($POST->deviceid)) { $Result->show("danger", _("Invalid ID"), true); }
if(!is_numeric($POST->rack_start))			                          { $Result->show("danger", _("Invalid start value"), true); }
if(!is_numeric($POST->rack_size))			                          { $Result->show("danger", _("Invalid size value"), true); }

# name
if (($POST->devicetype == 'content') && (!isset($POST->name) || (trim($POST->name === '')))) { $Result->show("danger", _("Invalid device name"), true); }

# validate rack
$rack = $Admin->fetch_object("racks", "id", $POST->rackid);
if ($rack===false)                                                     { $Result->show("danger", _("Invalid ID"), true); }

# check size
if($rack->hasBack!="0") {
	if($POST->rack_start+($POST->rack_size-1)>(2*$rack->size))   { $Result->show("danger", _("Invalid rack position (overflow)"), true); }
}
else {
	if($POST->rack_start+($POST->rack_size-1)>$rack->size)       { $Result->show("danger", _("Invalid rack position (overflow)"), true); }
}

switch ($POST->devicetype) {
    case 'device':
    # validate device
    $device = $Admin->fetch_object("devices", "id", $POST->deviceid);
    if ($device===false)                                                   { $Result->show("danger", _("Invalid Device ID"), true); }

    # set update values
    $values = array("id"=>$POST->deviceid,
				    "rack"=>$POST->rackid,
				    "rack_start"=>$POST->rack_start,
				    "rack_size"=>$POST->rack_size,
				    );

    # inherit location if it is not already set
    if ($User->settings->enableLocations=="1" && $POST->no_location!="1") {
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
    $values = array("name"=>$POST->name,
                    "rack"=>$POST->rackid,
                    "rack_start"=>$POST->rack_start,
                    "rack_size"=>$POST->rack_size,
                    );

    # add content
    if (!$Admin->object_modify('rackContents', 'add', null, $values))       { $Result->show("success", _("Failed to add device to rack").'!', false); }
    else                                                                    { $Result->show("success", _("Device added to rack").'!', false); }
    break;
}