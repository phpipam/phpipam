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
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();
# verify that user has permission to module
$User->check_module_permissions ("devices", User::ACCESS_R, true, false);

# check if site is demo
$User->is_demo();
# check maintaneance mode
$User->check_maintaneance_mode ();

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "group", $POST->csrf_cookie) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";


# remove devices from this group if delete and remove group
if($POST->action == "delete") {
	#$Admin->remove_group_from_users($POST->g_id);
	#$Admin->remove_group_from_sections($POST->g_id);
}
else {
	if(strlen($POST->name) < 2)											{ $Result->show("danger", _('Name must be at least 2 characters long')."!", true); }
}

# unique name
if($POST->action=="add") {
if($Tools->fetch_object("deviceGroups", "name", $POST->name)!==false)	{ $Result->show("danger", _('Group already exists')."!", true); }
}

# create array of values for modification
$values = array("id"   => $POST->id,
				"name" => $POST->name,
				"desc" => $POST->desc);

/* try to execute */
if(!$Admin->object_modify("deviceGroups", $POST->action, "id", $values)) 	{ $Result->show("danger",  _("Group")." ".$User->get_post_action()." "._("error")."!", false); }
else 					 													{ $Result->show("success", _("Group")." ".$User->get_post_action()." "._("success")."!", false); }
