<?php

/**
 * Script to display usermod result
 *************************************/


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
$User->csrf_cookie ("validate", "group", $_POST['csrf_cookie']) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";


# remove users from this group if delete and remove group from sections
if($_POST['action'] == "delete") {
	$Admin->remove_group_from_users($_POST['g_id']);
	$Admin->remove_group_from_sections($_POST['g_id']);
}
else {
	if(strlen($_POST['g_name']) < 2)										{ $Result->show("danger", _('Name must be at least 2 characters long')."!", true); }
}

# unique name
if($_POST['action']=="add") {
if($Admin->fetch_object("userGroups", "g_name", $_POST['g_name'])!==false)	{ $Result->show("danger", _('Group already exists')."!", true); }
}

# create array of values for modification
$values = array("g_id"=>@$_POST['g_id'],
				"g_name"=>$_POST['g_name'],
				"g_desc"=>@$_POST['g_desc']);

/* try to execute */
if(!$Admin->object_modify("userGroups", $_POST['action'], "g_id", $values)) { $Result->show("danger",  _("Group $_POST[action] error")."!", false); }
else 					 													{ $Result->show("success", _("Group $_POST[action] success")."!", false); }

# from list of usernames provided from AD result if some user matches add him to group
if (strlen($_POST['gmembers'])>0) {
	// save id
	$gid = $Admin->lastId;
	// to array
	$gmembers = explode(";", $_POST['gmembers']);
	// check
	foreach ($gmembers as $gm) {
		// check if user exists
		$user=$Admin->fetch_object("users","username",$gm);
		if ($user!==false) {
			// add to group
			$Admin->add_group_to_user ($gid, $user->id);
		}
	}
}

?>