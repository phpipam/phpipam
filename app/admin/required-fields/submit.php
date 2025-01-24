<?php

/**
 * save required IP fields
 ****************************************/


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

# set fields to update
$values = array("id"=>1,
				"IPrequired"=>implode(';', $POST->as_array()));

# update
if(!$Admin->object_modify("settings", "edit", "id", $values))   { $Result->show("danger alert-absolute",  _("Update failed"), true); }
else															{ $Result->show("success alert-absolute", _('Update successfull'), true); }