<?php

/**
 * Script to display vault edit result
 *************************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database       = new Database_PDO;
$User           = new User ($Database);
$Admin          = new Admin ($Database, false);
$Tools          = new Tools ($Database);
$Result         = new Result ();
$Password_check = new Password_check ();

# verify that user is logged in
$User->check_user_session();
# check maintaneance mode
$User->check_maintaneance_mode ();

# make sure user has access
if ($User->get_module_permissions ("vaults")<User::ACCESS_RWA) { $Result->show("danger", _("Insufficient privileges").".", true); }

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "vaults", $POST->csrf_cookie) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# fetch custom fields
$custom = $Tools->fetch_custom_fields('vaults');

/* checks */
$error = array();

# add, edit
if($POST->action!="delete") {
	# name must be more than 2 and alphanumeric
	if(strlen($POST->name)<3 || strlen($POST->name)>64)			{ $error[] = "Invalid name"; }
}
# ad - check secret length
if($POST->action=="add") {
	if(strlen($POST->secret)<8)									{ $Result->show("danger", _("Secret must be at least 8 characters long!"), true); }

	//enforce password policy
	$policy = (db_json_decode($User->settings->passwordPolicy, true));
	$Password_check->set_requirements  ($policy, pf_explode(",",$policy['allowedSymbols']));
	if (!$Password_check->validate ($POST->secret)) 				{ $Result->show("danger alert-danger ", _('Secret validation errors').":<br> - ".implode("<br> - ", $Password_check->get_errors ()), true); }
}

# die if errors
if(sizeof($error) > 0) {
	$Result->show("danger", $error, true);
}
else {
	# create array of values for modification
	$values = array(
					"id"                     =>$POST->id,
					"name"                   =>$POST->name,
					"description"            =>$POST->description
					);

	# append custom
	if(sizeof($custom) > 0) {
		foreach($custom as $myField) {
			# replace possible ___ back to spaces!
			$myField['nameTest']      = str_replace(" ", "___", $myField['name']);
			if(isset($POST->{$myField['nameTest']})) { $values[$myField['name']] = $POST->{$myField['nameTest']};}
		}
	}

	# add test
	if($POST->action=="add") {
		$values['test'] = $User->Crypto->encrypt($User->Crypto->random_pseudo_bytes(32), $POST->secret);
		$values['type'] = $POST->type;
	}

	# execute
	if(!$Admin->object_modify("vaults", $POST->action, "id", $values)) 	{ $Result->show("danger",  _("Vault")." ".$User->get_post_action()." "._("error"), true); }
	else 																	{ $Result->show("success", _("Vault")." ".$User->get_post_action()." "._("success"), true); }
}
