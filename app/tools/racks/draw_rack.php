<?php

/**
 * Script to draw rack
 ***************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();
# verify module permissions
$User->check_module_permissions ("racks", User::ACCESS_R, true);

# init racks object
$Racks = new phpipam_rack ($Database);

# deviceId not set or empty - set to 0
if (empty($_GET['deviceId']))      { $_GET['deviceId'] = 0; }

# validate rackId
if (!is_numeric($_GET['rackId']))     { die(); }
if (!is_numeric($_GET['deviceId']))   { die(); }

# fetch rack
$rack = $User->fetch_object("racks", "id", $_GET['rackId']);
if ($rack===false)     				  { die(); }

# permission - dont draw names if user has no access to devices
$draw_names = $User->get_module_permissions ("devices")>=User::ACCESS_R ? true : false;

# back
if(@$_GET['is_back']=="1") {
	$Racks->draw_rack ($_GET['rackId'],$_GET['deviceId'], true, $draw_names);
}
else {
	$Racks->draw_rack ($_GET['rackId'],$_GET['deviceId'], false, $draw_names);
}