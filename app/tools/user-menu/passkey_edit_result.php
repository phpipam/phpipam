<?php

/**
 *
 * Rename passkey
 *
 */

# include required scripts
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize required objects
$Database       = new Database_PDO;
$Result         = new Result;
$User           = new User ($Database);

# verify that user is logged in
$User->check_user_session();

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "passkeyedit", $POST->csrf_cookie) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# fetch passkey
$passkey = $User->get_user_passkey_by_keyId ($POST->keyid);

# validate
if(is_null($passkey)) {
	$Result->show("danger", _("Passkey not found"), true);
}
elseif ($passkey->user_id!=$User->user->id) {
	$Result->show("danger", _("Passkey not found"), true);
}
else {
	if($POST->action=="edit" || $POST->action=="add") {
		if($User->rename_passkey ($passkey->id, $POST->comment)) { $Result->show("success", _("Passkey renamed"), false); }
		else 														{ $Result->show("success", _("Failed to rename passkey"), false); }
	}
	else {
		if($User->delete_passkey ($passkey->id)) 					{ $Result->show("success", _("Passkey removed"), false); }
		else 														{ $Result->show("success", _("Failed to remove passkey"), false); }
	}
}