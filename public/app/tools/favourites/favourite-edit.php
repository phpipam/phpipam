<?php

/* Edit favourite subnets */

// include required scripts
require_once __DIR__.'/../../../../functions/functions.php';

// initialize required objects
$Database = new Database_PDO;
$Result = new Result;
$User = new User($Database);
$Subnets = new Subnets($Database);

// verify that user is logged in
$User->check_user_session();

if ($User->Crypto->csrf_validate($POST->csrf) === false) {
    $Result->show('danger', _('Invalid CSRF cookie'), true, true);
}

// checks
is_numeric($POST->subnetId) ?: $Result->show('danger', _('Invalid ID'), true, true);

// execute action
if (! $User->edit_favourite($POST->action, $POST->subnetId)) {
    $Result->show('danger', _('Error editing favourite'), true, true);
} else {
    print 'success';
}
