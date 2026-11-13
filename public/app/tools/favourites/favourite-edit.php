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
    $Result->die('danger', _('Invalid CSRF cookie'), true);
}

// checks
is_numeric($POST->subnetId) ?: $Result->die('danger', _('Invalid ID'), true);

// execute action
if (! $User->edit_favourite($POST->action, $POST->subnetId)) {
    $Result->die('danger', _('Error editing favourite'), true);
} else {
    print 'success';
}
