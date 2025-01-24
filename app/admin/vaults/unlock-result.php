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
$Result         = new Result ();
$Password_check = new Password_check ();

# verify that user is logged in
$User->check_user_session();
# check maintaneance mode
$User->check_maintaneance_mode ();

# make sure user has access
if ($User->get_module_permissions ("vaults")==User::ACCESS_NONE) { $Result->show("danger", _("Insufficient privileges").".", true); }

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "vaultunlock", $POST->csrf_cookie) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

// fetch vault
$vault = $Admin->fetch_object("vaults", "id", $POST->vaultId);
// validate vault id
$vault===false ? $Result->show("danger", _("Invalid ID"), true) : null;

// test
if($User->Crypto->decrypt($vault->test, $POST->vaultpass) === false) {
	$Result->show("danger", _("Invalid master password"), true);
}
else {
	// write session
	$_SESSION['vault'.$vault->id] = $POST->vaultpass;
	// OK, redirect
	$Result->show("success", _("Vault unlocked, redirecting..."), false);
}
