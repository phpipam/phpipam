<?php

/**
 * Edit switch result
 ***************************/

/* functions */
require_once( __DIR__ . '/../../../functions/functions.php' );

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
if ($POST->action == "edit") {
	$User->check_module_permissions("devices", User::ACCESS_RW, true, true);
} else {
	$User->check_module_permissions("devices", User::ACCESS_RWA, true, true);
}

# check maintaneance mode
$User->check_maintaneance_mode ();

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "device", $POST->csrf_cookie) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# ID must be numeric
if($POST->action!="add" && !is_numeric($POST->switchid))			{ $Result->show("danger", _("Invalid ID"), true); }

# available devices set
foreach($POST as $key=>$line) {
	if (!is_blank(strstr((string) $key,"section-"))) {
		$key2 = str_replace("section-", "", $key);
		$temp[] = $key2;

		unset($POST->{$key});
	}
}
# glue sections together
$POST->sections = !empty($temp) ? implode(";", $temp) : null;

# Hostname must be present
if($POST->hostname == "") 											{ $Result->show("danger", _('Hostname is mandatory').'!', true); }

# rack checks
$POST->rack_deep = (@$POST->rack_deep=="1") ? "1" : "0";
if ($POST->rack !== "0" && $User->get_module_permissions ("racks")>User::ACCESS_R) {
    if ($User->settings->enableRACK!="1") {
        unset($POST->rack);
    }
    else {
        # validate position and size
        if (!is_numeric($POST->rack))                               { $Result->show("danger", _('Invalid rack identifier').'!', true); }
        if (!is_numeric($POST->rack_start))                         { $Result->show("danger", _('Invalid rack start position').'!', true); }
		if (!is_numeric($POST->rack_size)) 							{ $Result->show("danger", _('Invalid rack size').'!', true); }
		$POST->rack_deep = (isset($POST->rack_deep) && $POST->rack_deep=="1") ? "1" : "0";
		if ($POST->rack != 0 && $POST->rack_size < 1) 				{ $Result->show("danger", _('Invalid rack size').'!', true); }
		# validate rack
		$rack = $Racks->fetch_rack_details($POST->rack);
		if (!is_object($rack)) 										{ $Result->show("danger", _('Rack does not exist').'!', true); }
		# check overflow
		if ($Racks->check_device_overflow($POST->rack,$POST->rack_start,$POST->rack_size)) 
																	{ $Result->show("danger", _('Invalid rack position (overflow)').'!', true); }
		# check overlaps
		if ($User->settings->rackAllowOverlap!="1") {
			if ($Racks->check_device_overlap($POST->rack,$POST->rack_start,$POST->rack_size,$POST->rack_deep,$POST->switchid)) 
																	{ $Result->show("danger", _('Overlaps with existing rack item').'!', true); };
		}
    }
}

# set update values
$values = array(
				"id"          =>$POST->switchid,
				"hostname"    =>$POST->hostname,
				"ip_addr"     =>$POST->ip_addr,
				"type"        =>$POST->type,
				"description" =>$POST->description,
				"sections"    =>$POST->sections,
				"location"    =>$POST->location
				);

# fetch custom fields
$update = $Tools->update_POST_custom_fields('devices', $POST->action, $POST);
$values = array_merge($values, $update);

# rack
if (!is_blank($POST->rack) && $User->get_module_permissions ("racks")>User::ACCESS_R) {
	$values['rack']       = $POST->rack;
	$values['rack_start'] = $POST->rack_start;
	$values['rack_size']  = $POST->rack_size;
	$values['rack_deep']  = $POST->rack_deep;
}
# perms
if ($User->get_module_permissions ("locations")==User::ACCESS_NONE) {
	unset ($values['location']);
}

# update device
if ($Admin->object_modify("devices", $POST->action, "id", $values)) {
	$Result->show("success", _("Device") . " " . $User->get_post_action() . " " . _("successful") . '!', false);
}

if($POST->action=="delete"){
	# remove all references from subnets and ip addresses
	$Admin->remove_object_references ("subnets", "device", $values["id"]);
	$Admin->remove_object_references ("nat", "device", $values["id"]);
	$Admin->remove_object_references ("ipaddresses", "switch", $values["id"]);
	$Admin->remove_object_references ("pstnPrefixes", "deviceId", $values["id"]);
	$Admin->remove_object_references ("pstnNumbers", "deviceId", $values["id"]);
	$Admin->remove_object_references ("circuits", "device1", $values["id"]);
	$Admin->remove_object_references ("circuits", "device2", $values["id"]);
}
