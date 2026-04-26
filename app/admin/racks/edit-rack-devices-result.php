<?php

/**
 * Edit rack devices result
 ***************************/

/* functions */
require_once( __DIR__ . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database, false);
$Result 	= new Result ();
$Racks 		= new phpipam_rack ($Database);

# verify that user is logged in
$User->check_user_session();
# verify module permissions
if ($POST->action == "edit") {
    $User->check_module_permissions("racks", User::ACCESS_RW, true, true);
} else {
    $User->check_module_permissions("racks", User::ACCESS_RWA, true, true);
}
$User->check_module_permissions ("devices", User::ACCESS_R, true, false);

# check maintaneance mode
$User->check_maintaneance_mode ();

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "rack_devices", $POST->csrf_cookie) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# device type?
if (!isset($POST->devicetype) || ($POST->devicetype != 'device' && $POST->devicetype != 'content' && $POST->devicetype != 'subrack')) { $Result->show("danger", _("Invalid device type"), true, true); }

# ID must be numeric
if(!is_numeric($POST->rackid))			                              { $Result->show("danger", _("Invalid rack identifier"), true); }
if(($POST->devicetype == 'device') && !is_numeric($POST->deviceid)) { $Result->show("danger", _("Invalid device identifier"), true); }
if(!is_numeric($POST->rack_start))			                          { $Result->show("danger", _("Invalid start value"), true); }
if(!is_numeric($POST->rack_size))			                          { $Result->show("danger", _("Invalid size value"), true); }
if($POST->rack_size < 1)											{ $Result->show("danger", _('Invalid size value').'!', true); }
$POST->rack_deep = (@$POST->rack_deep=="1") ? "1" : "0";
if($POST->devicetype == 'subrack' && !is_numeric($POST->subrackid))	{ $Result->show("danger", _("Invalid rack identifier"), true); }

# name
if ($POST->devicetype == 'content' && (!isset($POST->name) || trim($POST->name === ''))) { $Result->show("danger", _("Invalid device name"), true); }

# validate rack
$rack = $Admin->fetch_object("racks", "id", $POST->rackid);
if ($rack===false)                                                     { $Result->show("danger", _("Rack does not exist"), true); }

# prevent nesting subracks
if($POST->devicetype == 'subrack' && $rack->subrack)				{ $Result->show("danger", _('Nesting subracks is not allowed').'!', true); }

# check overflow
if ($Racks->check_device_overflow($POST->rackid,$POST->rack_start,$POST->rack_size))
															{ $Result->show("danger", _('Invalid rack position (overflow)').'!', true); }
# check overlaps
if ($User->settings->rackAllowOverlap!="1") {
	if ($Racks->check_device_overlap($POST->rackid,$POST->rack_start,$POST->rack_size,$POST->rack_deep))
															{ $Result->show("danger", _('Overlaps with existing rack item').'!', true); };
}

switch ($POST->devicetype) {
    case 'device':
    # validate device
    $device = $Admin->fetch_object("devices", "id", $POST->deviceid);
    if ($device===false)                                                   { $Result->show("danger", _("Invalid Device ID"), true); }

    # set update values
    $values = ["id"=>$POST->deviceid,
				    "rack"=>$POST->rackid,
				    "rack_start"=>$POST->rack_start,
				    "rack_size"=>$POST->rack_size,
					"rack_deep"=>$POST->rack_deep,
				    ];

    # inherit location if it is not already set
    if ($User->settings->enableLocations=="1" && $POST->no_location!="1") {
	    if (($device->location=="0" || is_null($device->location)) && ($rack->location!="0" && !is_null($rack->location))) {
		    $values['location'] = $rack->location;
	    }
    }

    # update rack
    if(!$Admin->object_modify("devices", "edit", "id", $values))	        { $Result->show("danger", _("Failed to add device to rack").'!', false); }
    else																	{ $Result->show("success", _("Device added to rack").'!', false); }
    break;

    case 'content':
    # set values
    $values = ["name"=>$POST->name,
                    "rack"=>$POST->rackid,
                    "rack_start"=>$POST->rack_start,
                    "rack_size"=>$POST->rack_size,
					"rack_deep"=>$POST->rack_deep,
                    ];

    # add content
    if (!$Admin->object_modify('rackContents', 'add', null, $values))       { $Result->show("danger", _("Failed to add device to rack").'!', false); }
    else                                                                    { $Result->show("success", _("Device added to rack").'!', false); }
    break;

	case 'subrack':
	# validate the subrack
	$subrack = $Admin->fetch_object("racks", "id", $POST->subrackid);
	if ($subrack===false)			{ $Result->show("danger", _("Subrack")." "._("does not exist"), true); }
	if (!$subrack->subrack)			{ $Result->show("danger", _("Rack is not designated as a subrack"), true); }
	# is that subrack already placed somewhere?
	if ($Database->getObjectQuery("rackContents", "SELECT * from `rackContents` where `subrackId` = ? limit 1;", [$POST->subrackid]))
																			{ $Result->show("danger", _("Subrack is already in a rack"), true); }
	$values = ["name"=>unescape_input($subrack->name),
					"rack"=>$POST->rackid,
					"rack_start"=>$POST->rack_start,
					"rack_size"=>$POST->rack_size,
					"rack_deep"=>$POST->rack_deep,
					"subrackId"=>$subrack->id,
					];
	if (!$Admin->object_modify('rackContents', 'add', null, $values))		{ $Result->show("danger", _("Failed to add subrack to rack").'!', false); }
	else 																	{ $Result->show("success", _("Subrack")." "._("added to rack"), false); }
	break;
}
