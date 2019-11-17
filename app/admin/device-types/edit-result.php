<?php

/**
 * Edit device result
 ***************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

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
$User->Crypto->csrf_cookie ("validate", "device_types", $_POST['csrf_cookie']) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# ID must be numeric
if($_POST['action']!="add" && !is_numeric($_POST['tid'])) 	{ $Result->show("danger", _("Invalid ID"), true); }

# name must be present! */
if($_POST['tname'] == "") 									{ $Result->show("danger", _('Name is mandatory').'!', false); }

# create array of values for modification
$values = array("tid"=>@$_POST['tid'],
				"tname"=>$_POST['tname'],
				"tdescription"=>@$_POST['tdescription']);

# update
if(!$Admin->object_modify("deviceTypes", $_POST['action'], "tid", $values)) 	{ $Result->show("danger",  _("Failed to $_POST[action] device type").'!', false); }
else 																			{ $Result->show("success", _("Device type $_POST[action] successful").'!', false); }

if($_POST['action']=="delete") {
    $Admin->remove_object_references ("devices", "type", $values["tid"]);
}
