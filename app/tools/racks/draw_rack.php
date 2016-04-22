<?php

/**
 * Script to draw rack
 ***************************/

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# init racks object
$Racks = new phpipam_rack ($Database);

# deviceId not set or empty - set to 0
if (empty($_GET['deviceId']))      { $_GET['deviceId'] = 0; }

# validate rackId
if (!is_numeric($_GET['rackId']))     { die(); }
if (!is_numeric($_GET['deviceId']))   { die(); }

# draw
$Racks->draw_rack ($_GET['rackId'],$_GET['deviceId']);
?>
