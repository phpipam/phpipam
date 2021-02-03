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

# verify that user is logged in
$User->check_user_session();
# check maintaneance mode
$User->check_maintaneance_mode ();

# strip input tags
$_POST = $Admin->strip_input_tags($_POST);

// fetch vault
$vault = $Admin->fetch_object("vaults", "id", $_POST['id']);
// validate vault id
$vault===false ? $Result->show("danger", _("Invalid ID"), true, true) : null;

// delete session
unset($_SESSION['vault'.$vault->id]);

// OK, redirect
$Result->show("success", _("Vault locked").".", false, true, false, false, true);