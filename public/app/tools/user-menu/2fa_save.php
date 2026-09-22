<?php

/**
 *
 * User selfMod check end execute
 *
 */

# include required scripts
require_once  __DIR__ . '/../../../../functions/functions.php';

# initialize required objects
$Database       = new Database_PDO;
$Result         = new Result;
$User           = new User ($Database);
$Admin          = new Admin ($Database, false);
$ga             = new PragmaRX\Google2FA\Google2FA();

# verify that user is logged in
$User->check_user_session();
# check if site is demo
$User->is_demo();

# validate csrf cookie
$User->Crypto->csrf_validate($POST->csrf_cookie) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# change ?
if($POST->{'2fa'} && $User->user->{'2fa'}) {
	$Result->show("info", _("No change"), true);
}

# can user change ?
if (! $User->settings->{'2fa_userchange'}) {
	$Result->show("danger", _("You are not allowed to change 2fa settings. Please contact system administrator."), true);
}

# init values
$values       = [];
$values['id'] = $User->user->id;

# 2fa and 2fa_secret
if($POST->{'2fa'}) {
	$values['2fa'] = 2;
	# create
	$values['2fa_secret'] = $ga->generateSecretKey(User::TOTP_SECRET_LEN);
}
# remove 2fa
else {
	$values['2fa'] = 0;
	$values['2fa_secret'] = NULL;	// remove old 2fa secret
}


# update
if(!$Admin->object_modify("users", "edit", "id", $values)) 	{ $Result->show("danger alert-absolute",  _("2fa update error"), true); }
else 														{ $Result->show("success alert-absolute", _("2fa update success"), true); }