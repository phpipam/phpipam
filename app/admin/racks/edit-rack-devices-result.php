<?php

/**
 * Edit rack devices result
 ***************************/

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();
# check maintaneance mode
$User->check_maintaneance_mode ();

# strip input tags
$_POST = $Admin->strip_input_tags($_POST);

# validate csrf cookie
$User->csrf_cookie ("validate", "rack_devices", $_POST['csrf_cookie']) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# ID must be numeric
if(!is_numeric($_POST['rackid']))			                           { $Result->show("danger", _("Invalid ID"), true); }
if(!is_numeric($_POST['deviceid']))			                           { $Result->show("danger", _("Invalid ID"), true); }
if(!is_numeric($_POST['rack_start']))			                       { $Result->show("danger", _("Invalid start value"), true); }
if(!is_numeric($_POST['rack_size']))			                       { $Result->show("danger", _("Invalid size value"), true); }

# validate rack
$rack = $Admin->fetch_object("racks", "id", $_POST['rackid']);
if ($rack===false)                                                     { $Result->show("danger", _("Invalid ID"), true); }

# check size
if($_POST['rack_start']+($_POST['rack_size']-1)>$rack->size)            { $Result->show("danger", _("Invalid rack position (overflow)"), true); }

# set update values
$values = array("id"=>@$_POST['deviceid'],
				"rack"=>@$_POST['rackid'],
				"rack_start"=>@$_POST['rack_start'],
				"rack_size"=>@$_POST['rack_size'],
				);

# update rack
if(!$Admin->object_modify("devices", "edit", "id", $values))	        { $Result->show("success", _("Failed to add device to rack").'!', false); }
else																	{ $Result->show("success", _("Device added to rack").'!', false); }

?>