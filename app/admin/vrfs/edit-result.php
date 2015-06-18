<?php

/**
 * Script to edit VRF
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


# Hostname must be present!
if($_POST['name'] == "") { $Result->show("danger", _("Name is mandatory"), true); }


# set update array
$values = array("vrfId"=>@$_POST['vrfId'],
				"name"=>$_POST['name'],
				"description"=>$_POST['description']
				);
# update
if(!$Admin->object_modify("vrf", $_POST['action'], "vrfId", $values))	{ $Result->show("danger",  _("Failed to $_POST[action] VRF").'!', true); }
else																	{ $Result->show("success", _("VRF $_POST[action] successfull").'!', false); }


# remove all references if delete
if($_POST['action']=="delete") { $Admin->remove_object_references ("subnets", "vrfId", $_POST['vrfId']); }
?>