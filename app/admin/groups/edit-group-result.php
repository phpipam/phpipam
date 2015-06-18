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


# remove users from this group if delete and remove group from sections
if($_POST['action'] == "delete") {
	$Admin->remove_group_from_users($_POST['g_id']);
	$Admin->remove_group_from_sections($_POST['g_id']);
}
else {
	if(strlen($_POST['g_name']) < 2)										{ $Result->show("danger", _('Name must be at least 2 characters long')."!", true); }
}

# create array of values for modification
$values = array("g_id"=>@$_POST['g_id'],
				"g_name"=>$_POST['g_name'],
				"g_desc"=>@$_POST['g_desc']);

/* try to execute */
if(!$Admin->object_modify("userGroups", $_POST['action'], "g_id", $values)) { $Result->show("danger",  _("Group $_POST[action] error")."!", true); }
else 					 													{ $Result->show("success", _("Group $_POST[action] success")."!", true); }

?>