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
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();
# check if site is demo
$User->is_demo();
# check maintaneance mode
$User->check_maintaneance_mode ();

# id must be numeric
if(!is_numeric($POST->gid))		{ $Result->show("danger", _("Invalid ID"), true); }

# parse result
foreach($POST as $k=>$p) {
	if(substr($k, 0,4) == "user") {
		$users[substr($k, 4)] = substr($k, 4);
	}
}

# remove each user from group
if(sizeof($users)>0) {
	foreach($users as $key=>$u) {
		if(!$Admin->remove_group_from_user($POST->gid, $u)) {
			# get user details
			$user = $Admin->fetch_object("users", "id", $u);
			$errors[] = $user->real_name;
		}
	}
}
else {
	$errors[] = _("Please select user(s) to remove from group!");
}

# print result
if(isset($errors)) {
	print "<div class='alert alert alert-danger'>";
	print _("Failed to remove users").":<hr>";
	print "<ul>";
	foreach($errors as $e) {
		print "<li>$e</li>";
	}
	print "</ul>";
	print "</div>";
}
else {
	$Result->show("success", _('Users removed from group'), true);
}
