<?php

/*
 * Print edit subnet
 *********************/

/* functions */
require_once __DIR__.'/../../../../functions/functions.php';

// initialize user object
$Database = new Database_PDO;
$User = new User($Database);
$Admin = new Admin($Database, false);
$Subnets = new Subnets($Database);
$Result = new Result;

// verify that user is logged in
$User->check_user_session();
// check maintaneance mode
$User->check_maintaneance_mode();

if ($User->Crypto->csrf_validate($POST->csrf_cookie) === false) {
    $Result->die('danger', _('Invalid CSRF cookie'));
}

// check subnet permissions
if ($Subnets->check_permission($User->user, $POST->subnetId) != 3) {
    $Result->die('danger', _('You do not have permissions to add edit/delete this subnet').'!');
}

// ID must be numeric
if (! is_numeric($POST->subnetId)) {
    $Result->die('danger', _('Invalid ID'));
}

if (! is_numeric($POST->linked_subnet)) {
    $Result->die('danger', _('Invalid ID'));
}

// submit
$values = [
    'id' => $POST->subnetId,
    'linked_subnet' => $POST->linked_subnet,
];

// verify that user has write permissions for subnet
if ($Subnets->modify_subnet('edit', $values) !== false) {
    $Result->warn('success', _('Subnet linked'));
}
