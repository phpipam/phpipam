<?php

/**
 *	Script to replace fields in IP address list
 ***********************************************/

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
$User->Crypto->csrf_cookie ("validate", "replace_fields", $POST->csrf_cookie) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

//verify post
if(empty($POST->search)) { $Result->show("danger", _('Please enter something in search field').'!', true); }
//if device verify that it exists
if($POST->field == "switch") {
	if(!$device1 = $Admin->fetch_object("devices", "hostname", $POST->search))	{ $Result->show("danger  alert-absolute", _('Switch').' "<i>'. $POST->search  .'</i>" '._('does not exist, first create switch under admin menu').'!', true); }
	if(!$device2 = $Admin->fetch_object("devices", "hostname", $POST->replace))	{ $Result->show("danger  alert-absolute", _('Switch').' "<i>'. $POST->search  .'</i>" '._('does not exist, first create switch under admin menu').'!', true); }

	//replace posts
	$POST->search  = $device1->id;
	$POST->replace = $device2->id;
}

# update
$Admin->replace_fields ($POST->field, $POST->search, $POST->replace);