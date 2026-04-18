<?php

/**
 * Script to display usermod result
 *************************************/


/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Tools	 	= new Tools ($Database);
$Devices 	= new Devices ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();
# verify that user has permission to module
$User->check_module_permissions ("devices", User::ACCESS_R, true, false);

# check if site is demo
$User->is_demo();
# check maintaneance mode
$User->check_maintaneance_mode ();

if(!is_numeric($POST->g_id))		{ $Result->show("danger", _("Invalid Device group ID"), true, true); }

$values = [];

# parse result
foreach($POST as $k=>$p) {
	if(substr($k, 0,6) == "device") {
		# create array of values for modification
		$values['d_id'] = substr($k, 6);
		$values['g_id'] = $POST->g_id;

		if(!$Devices->object_modify("device_to_group", "add", "id", $values)) {
			$Result->show("danger",  _("Device")." ". substr($k, 6) . " ".$User->get_post_action()." "._("error")."!", false);
		} else {
			$Result->show("success", _("Device")." ". substr($k, 6) . " ".$User->get_post_action()." "._("success")."!", false);
		}
	}
}