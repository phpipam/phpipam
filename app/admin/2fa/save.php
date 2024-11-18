<?php

/**
 *	Site settings
 **************************/

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

// validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "2fa", $POST->csrf_cookie) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

// check 2fa_provider
$providers = ['none', "Google_Authenticator"];
if(!in_array($POST->{'2fa_provider'}, $providers)) 							{ $Result->show("danger", _("Invalid provider"), true); }

// verify name
if(strlen($POST->{'2fa_name'})>32 || is_blank($POST->{'2fa_name'}))			{ $Result->show("danger", _("Invalid application name"), true); }

// verify length
if(!is_numeric($POST->{'2fa_length'}))										{ $Result->show("danger", _("Invalid value for length"), true); }
if($POST->{'2fa_length'}>32 || $POST->{'2fa_length'}<26)						{ $Result->show("danger", _("Invalid length"), true); }

// make sure all git submodules are included
if (!file_exists(dirname(__FILE__)."/../../../functions/GoogleAuthenticator/PHPGangsta/GoogleAuthenticator.php"))	{ $Result->show("danger", _("GoogleAuthenticator submodule missing."), true); }
if (!file_exists(dirname(__FILE__)."/../../../functions/qrcodejs/qrcode.js"))	{ $Result->show("danger", _("QRCode submodule missing."), true); }

// change
$POST->{'2fa_userchange'} = isset($POST->{'2fa_userchange'}) ? $POST->{'2fa_userchange'} : 0;

# set update values
$values = [
			"id"             => 1,
			"2fa_name"       => $POST->{'2fa_name'},
			"2fa_length"     => $POST->{'2fa_length'},
			"2fa_provider"   => $POST->{'2fa_provider'},
			"2fa_userchange" => $POST->{'2fa_userchange'}
			];
// update
if(!$Admin->object_modify("settings", "edit", "id", $values))	{ $Result->show("danger",  _("Cannot update settings"), false); }
else															{ $Result->show("success", _("Settings updated successfully"), false); }

// force ?
if(isset($POST->{'2fa_force'})) {
	if ($POST->{'2fa_force'}=="On") {
		$new_status = $POST->{'2fa_provider'}=="none" ? 0 : 1;
		$old_status = $new_status==1 ? 0 : 1;
		// update
		if($Admin->update_object_references ("users", "2fa", $old_status, $new_status)===false) { $Result->show("danger",  _("Failed to update all users!"), false); }
	}
}
