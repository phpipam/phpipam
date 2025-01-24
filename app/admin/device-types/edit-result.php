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

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "device_types", $POST->csrf_cookie) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# ID must be numeric
if($POST->action!="add" && !is_numeric($POST->tid)) 	{ $Result->show("danger", _("Invalid ID"), true); }

# name must be present! */
if($POST->tname == "") 									{ $Result->show("danger", _('Name is mandatory').'!', false); }

# create array of values for modification
$values = array("tid"=>$POST->tid,
				"tname"=>$POST->tname,
				"tdescription"=>$POST->tdescription);

# update
if(!$Admin->object_modify("deviceTypes", $POST->action, "tid", $values)) {
    $Result->show("danger", _("Failed to")." ".$User->get_post_action()." "._("device type").'!', false);
}
else {
    $Result->show("success", _("Device type")." ".$User->get_post_action()." "._("successful").'!', false);
}

if($POST->action=="delete") {
    $Admin->remove_object_references ("devices", "type", $values["tid"]);
}
