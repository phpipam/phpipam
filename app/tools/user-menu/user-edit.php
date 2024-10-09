<?php

/**
 *
 * User selfMod check end execute
 *
 */

header('Content-Type: text/html; charset=utf-8');

# include required scripts
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize required objects
$Database       = new Database_PDO;
$Result         = new Result;
$User           = new User ($Database);
$Password_check = new Password_check ();

# verify that user is logged in
$User->check_user_session();
# check if site is demo
$User->is_demo();

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "user-menu", $POST->csrf_cookie) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# verify email
if(!filter_var($POST->email, FILTER_VALIDATE_EMAIL))							{ $Result->show("danger alert-absolute",  _('Email not valid!'), true); }

# verify lang
if(!is_numeric($POST->lang))                                                 { $Result->show("danger alert-absolute",  _('Invalid language!'), true); }

# verify password if changed (not empty)
if (!is_blank($POST->password1)) {
	if ($POST->password1 != $POST->password2) 							{ $Result->show("danger alert-absolute", _('Passwords do not match!'), true); }
	# validate pass against policy
	$policy = (db_json_decode($User->settings->passwordPolicy, true));
	$Password_check->set_requirements  ($policy, pf_explode(",",$policy['allowedSymbols']));
	if (!$Password_check->validate ($POST->password1)) 						{ $Result->show("danger alert-danger ", _('Password validation errors').":<br> - ".implode("<br> - ", $Password_check->get_errors ()), true); }
}

# Verify Theme
if (!empty($POST->theme)) {
	if (!in_array($POST->theme, ['default', 'white', 'dark'])) 				{ $Result->show("danger alert-absolute", _('Invalid theme'), true); }
}

# passkeys
if ($User->settings->dbversion >= 40 && $User->settings->{'passkeys'}=="1") {
	// fetch passkeys
	$user_passkeys = $User->get_user_passkeys($User->user->id);
	// check
	if(isset($POST->passkey_only)) {
		if(sizeof($user_passkeys)==0) {
			$Result->show("warning alert-absolute", _('There are no passkeys set for user. Resetting passkey login only to false.'), false);
			print "<div class='clearfix'></div>";
			$POST->passkey_only = 0;
		}
		else {
			$POST->passkey_only = 1;
		}
	}
	else {
		$POST->passkey_only = 0;
	}
}

# set override
$POST->compressOverride = $POST->compressOverride=="Uncompress" ? "Uncompress" : "default";

# Update user
if (!$User->self_update($POST->as_array())) 												{ $Result->show("danger alert-absolute",  _('Error updating user account!'), true); }
else 																			{ $Result->show("success alert-absolute", _('Account updated successfully'), false); }

# update language
$User->update_session_language ();