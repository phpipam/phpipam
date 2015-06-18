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


# id must be numeric
if(!is_numeric($_POST['gid']))		{ $Result->show("danger", _("Invalid ID"), true); }

# parse result
foreach($_POST as $k=>$p) {
	if(substr($k, 0,4) == "user") {
		$users[substr($k, 4)] = substr($k, 4);
	}
}


# verify that description is present if action != delete
if(strlen($_POST['gid']==0))		{ $Result->show("danger", _('Error - no group ID'), true); }

# add each user to group
if(sizeof($users)>0) {
	foreach($users as $key=>$u) {
		if(!$Admin->add_group_to_user($_POST['gid'], $u)) {
			# get user details
			$user = $Admin->fetch_object("users", "id", $u);
			$errors[] = $user->real_name;
		}
	}
}
else {
	$errors[] = _("Please select user(s) to add to selected group!");
}

# print result
if(isset($errors)) {
	print "<div class='alert alert alert-danger'>";
	print _("Failed to add users").":<hr>";
	print "<ul>";
	foreach($errors as $e) {
		print "<li>$e</li>";
	}
	print "</ul>";
	print "</div>";
}
else {
	$Result->show("success", _('Users added to group'), true);
}

?>