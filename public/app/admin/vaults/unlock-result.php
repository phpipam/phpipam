<?php

/**
 * Script to display vault edit result
 *************************************/

/* functions */
require_once __DIR__.'/../../../../functions/functions.php';

// initialize user object
$Database = new Database_PDO;
$User = new User($Database);
$Admin = new Admin($Database, false);
$Result = new Result;
$Password_check = new Password_check;

// verify that user is logged in
$User->check_user_session();
// check maintaneance mode
$User->check_maintaneance_mode();

// make sure user has access
if ($User->get_module_permissions('vaults') == User::ACCESS_NONE) {
    $Result->die('danger', _('Insufficient privileges').'.', true);
}

if ($User->Crypto->csrf_validate($POST->csrf_cookie) === false) {
    $Result->die('danger', _('Invalid CSRF cookie'));
}

// fetch vault
$vault = $Admin->fetch_object('vaults', 'id', $POST->vaultId);
if (! is_object($vault)) {
    $Result->die('danger', _('Invalid ID'));
}

// test
if ($User->Crypto->decrypt($vault->test, $POST->vaultpass) === false) {
    $Result->die('danger', _('Invalid master password'));
} else {
    // write session
    $_SESSION['vault'.$vault->id] = $POST->vaultpass;
    // OK, redirect
    $Result->warn('success', _('Vault unlocked, redirecting...'));
}
