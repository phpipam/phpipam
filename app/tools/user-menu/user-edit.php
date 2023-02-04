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

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "user-menu", $_POST['csrf_cookie']) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# verify email
if(!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL))							{ $Result->show("danger alert-absolute",  _('Email not valid!'), true); }

# verify lang
if(!is_numeric($_POST['lang']))                                                 { $Result->show("danger alert-absolute",  _('Invalid language!'), true); }

# verify password if changed (not empty)
if (!is_blank($_POST['password1'])) {
	if ($_POST['password1'] != $_POST['password2']) 							{ $Result->show("danger alert-absolute", _('Passwords do not match!'), true); }
	# validate pass against policy
	$policy = (pf_json_decode($User->settings->passwordPolicy, true));
	$Password_check->set_requirements  ($policy, pf_explode(",",$policy['allowedSymbols']));
	if (!$Password_check->validate ($_POST['password1'])) 						{ $Result->show("danger alert-danger ", _('Password validation errors').":<br> - ".implode("<br> - ", $Password_check->get_errors ()), true); }
}

# Verify Theme
if (!empty($_POST['theme'])) {
	if (!in_array($_POST['theme'], ['default', 'white', 'dark'])) 				{ $Result->show("danger alert-absolute", _('Invalid theme'), true); }
}

# set override
$_POST['compressOverride'] = @$_POST['compressOverride']=="Uncompress" ? "Uncompress" : "default";

# Update user
if (!$User->self_update ($_POST)) 												{ $Result->show("danger alert-absolute",  _('Error updating user account!'), true); }
else 																			{ $Result->show("success alert-absolute", _('Account updated successfully'), false); }

# update language
$User->update_session_language ();