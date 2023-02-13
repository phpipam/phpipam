<?php

/**
 * Script to disaply vault edit result
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

# strip input tags
$_POST = $Admin->strip_input_tags($_POST);

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "vaultunlock", $_POST['csrf_cookie']) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

// fetch vault
$vault = $Admin->fetch_object("vaults", "id", $_POST['vaultId']);
// validate vault id
$vault===false ? $Result->show("danger", _("Invalid ID"), true) : null;

// test
if($User->Crypto->decrypt($vault->test, $_POST["vaultpass"])!="test") {
	$Result->show("danger", _("Invalid master password"), true);
}
else {
	// write session
	$_SESSION['vault'.$vault->id] = $_POST['vaultpass'];
	// OK, redirect
	$Result->show("success", _("Vault unlocked, redirecting..."), false);
}