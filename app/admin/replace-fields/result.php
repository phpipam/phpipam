<?php

/**
 *	Script to replace fields in IP address list
 ***********************************************/

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# validate csrf cookie
$User->csrf_cookie ("validate", "replace_fields", $_POST['csrf_cookie']) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# strip tags - XSS
$_POST = $User->strip_input_tags ($_POST);

//verify post
if(empty($_POST['search'])) { $Result->show("danger", _('Please enter something in search field').'!', true); }
//if device verify that it exists
if($_POST['field'] == "switch") {
	if(!$device1 = $Admin->fetch_object("devices", "hostname", $_POST['search']))	{ $Result->show("danger  alert-absolute", _('Switch').' "<i>'. $_POST['search']  .'</i>" '._('does not exist, first create switch under admin menu').'!', true); }
	if(!$device2 = $Admin->fetch_object("devices", "hostname", $_POST['replace']))	{ $Result->show("danger  alert-absolute", _('Switch').' "<i>'. $_POST['search']  .'</i>" '._('does not exist, first create switch under admin menu').'!', true); }

	//replace posts
	$_POST['search']  = $device1->id;
	$_POST['replace'] = $device2->id;
}

# update
$Admin->replace_fields ($_POST['field'], $_POST['search'], $_POST['replace']);
?>