<?php

/**
 *set users widgets
 */

// include required scripts
require_once __DIR__.'/../../../../functions/functions.php';

// initialize required objects
$Database = new Database_PDO;
$Result = new Result;
$User = new User($Database);

// verify that user is logged in
$User->check_user_session();
// check if site is demo
$User->is_demo();

if ($User->Crypto->csrf_validate($POST->csrf_cookie) === false) {
    $Result->die('danger', _('Invalid CSRF cookie'));
}

/* save widgets */
if (! $User->self_update_widgets($POST->widgets)) {
    $Result->die('danger', _('Error updating'));
} else {
    $Result->die('success', _('Widgets updated'));
}
