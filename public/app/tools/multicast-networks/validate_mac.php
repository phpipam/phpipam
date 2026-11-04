<?php

/**
 * validate mac address for multicast
 */

// include required scripts
require_once __DIR__.'/../../../../functions/functions.php';

// initialize required objects
$Database = new Database_PDO;
$Result = new Result;
$User = new User($Database);
$Subnets = new Subnets($Database);

// verify that user is logged in
$User->check_user_session();

// check that multicast is enabled
if ($User->settings->enableMulticast != '1') {
    exit('True');
}

// default vlan/id if not set
if (is_blank($POST->vlanId)) {
    $POST->vlanId = 0;
}

if (is_blank($POST->id)) {
    $POST->id = 0;
}

// validations
if (strlen((string) $POST->mac) > 21) {
    exit('True');
}

if (! is_numeric($POST->sectionId)) {
    exit('True');
}

if (! is_numeric($POST->vlanId)) {
    exit('True');
}

if (! is_numeric($POST->id)) {
    exit('True');
}

// if address is not multicast return true
if ($Subnets->validate_ip($POST->ip) === false) {
    exit('True');
}

if ($Subnets->is_multicast($POST->ip) === false) {
    exit('True');
}

// validate
// change last parameter to section / vlan
$text = $Subnets->validate_multicast_mac($POST->mac, $POST->sectionId, $POST->vlanId, MCUNIQUE, $POST->id);

// validate mac
if ($text === true) {
    exit('True');
}

print $text;
