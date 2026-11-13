<?php

/**
 * Script tomanage custom IP fields
 ****************************************/

/* functions */
require_once __DIR__.'/../../../../functions/functions.php';

// initialize user object
$Database = new Database_PDO;
$User = new User($Database);
$Admin = new Admin($Database);
$Result = new Result;

// verify that user is logged in
$User->check_user_session();
// admin check
$User->is_admin();
if ($User->Crypto->csrf_validate($POST->csrf_cookie) === false) {
    $Result->die('danger', _('Invalid CSRF cookie'));
}

// some verifications
if ((empty($POST->current)) || (empty($POST->next))) {
    $Result->die('danger', _('Fields cannot be empty').'!');
}

/* reorder */
if (! $Admin->reorder_custom_fields($POST->table, $POST->next, $POST->current)) {
    $Result->die('danger', _('Reordering failed').'!');
} else {
    $Result->warn('success', _('Fields reordered successfully').'!');
}
