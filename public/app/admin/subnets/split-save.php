<?php

/*
 * Print resize subnet
 *********************/

/* functions */
require_once __DIR__.'/../../../../functions/functions.php';

// initialize user object
$Database = new Database_PDO;
$User = new User($Database);
$Admin = new Admin($Database, false);
$Subnets = new Subnets($Database);
$Addresses = new Addresses($Database);
$Result = new Result;

// verify that user is logged in
$User->check_user_session();
// check maintaneance mode
$User->check_maintaneance_mode();

if ($User->Crypto->csrf_validate($POST->csrf_cookie) === false) {
    $Result->die('danger', _('Invalid CSRF cookie'));
}

// id must be numeric
if (! is_numeric($POST->subnetId)) {
    $Result->die('danger', _('Invalid ID'));
}

// get subnet details
$subnet_old = $Subnets->fetch_subnet(null, $POST->subnetId);

// verify that user has write permissions for subnet
$subnetPerm = $Subnets->check_permission($User->user, $subnet_old->id);
if ($subnetPerm < 3) {
    $Result->die('danger', _('You do not have permissions to resize subnet').'!');
}

// verify
$Subnets->subnet_split($subnet_old, $POST->number, $POST->prefix, $POST->group, $POST->custom_fields);

// all good
$Result->die('success', _('Subnet split successfully').'!');
