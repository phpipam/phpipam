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

// verify that user is logged in
$User->check_user_session();
// check maintaneance mode
$User->check_maintaneance_mode();

// make sure user has access
if ($User->get_module_permissions('vaults') == User::ACCESS_NONE) {
    $Result->die('danger', _('Insufficient privileges').'.', true);
}

// fetch vault
$vault = $Admin->fetch_object('vaults', 'id', $POST->id);
if (! is_object($vault)) {
    $Result->die('danger', _('Invalid ID'), true);
}

// delete session
unset($_SESSION['vault'.$vault->id]);

// OK, redirect
$Result->warn('success', _('Vault locked').'.', true, false, false, true);
