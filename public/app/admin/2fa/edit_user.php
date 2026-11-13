<?php

/**
 * 2FA user edit
 *************/

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

// check if site is demo
$User->is_demo(true);

// check id
if (! is_numeric($POST->id)) {
    $Result->die('danger', _('Invalid id'), true);
}

// activate
if ($POST->action == 'activate') {
    if ($Admin->object_modify('users', 'edit', 'id', ['id' => $POST->id, '2fa' => '1']) === false) {
        $Result->die('danger', _('Failed to activate 2fa for user'), true, false, false, true);
    } else {
        $Result->die('success', _('2fa activated'), true, false, false, true);
    }
}
// deactivate
elseif ($POST->action == 'deactivate') {
    if ($Admin->object_modify('users', 'edit', 'id', ['id' => $POST->id, '2fa' => '0']) === false) {
        $Result->die('danger', _('Failed to deactivate 2fa for user'), true, false, false, true);
    } else {
        $Result->die('success', _('2fa deactivated'), true, false, false, true);
    }
}
// remove secret
elseif ($POST->action == 'remove_secret') {
    if ($Admin->object_modify('users', 'edit', 'id', ['id' => $POST->id, '2fa_secret' => null]) === false) {
        $Result->die('danger', _('Failed to remove 2fa secret for user'), true, false, false, true);
    } else {
        $Result->die('success', _('2fa secret removed'), true, false, false, true);
    }
}
// invalid action
else {
    $Result->die('success', _('2fa deactivated'), true, false, false, true);
}
