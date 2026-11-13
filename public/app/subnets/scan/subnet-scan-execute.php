<?php

/*
 * Discover new hosts with ping
 *******************************/

/* functions */
require_once __DIR__.'/../../../../functions/functions.php';

// initialize user object
$Database = new Database_PDO;
$User = new User($Database);
$Tools = new Tools($Database);
$Admin = new Admin($Database, false);
$Sections = new Sections($Database);
$Subnets = new Subnets($Database);
$Addresses = new Addresses($Database);
$Scan = new Scan($Database, $User->settings);
$DNS = new DNS($Database, $User->settings);
$Result = new Result;

// verify that user is logged in
$User->check_user_session();
// check maintaneance mode
$User->check_maintaneance_mode();

if ($User->Crypto->csrf_validate($POST->csrf_cookie) === false) {
    $Result->die('danger', _('Invalid CSRF cookie'));
}

// scan type
$type = ! empty($POST->type) ? $POST->type : '';

// flag for ajax-loaded
$ajax_loaded = isset($POST->ajax_loaded);

if ($type !== 'snmp-route' && ($type !== 'snmp-route-all' || ! $ajax_loaded)) {
    // subnet Id must be a integer
    if (! is_numeric($POST->subnetId)) {
        $Result->die('danger', _('Invalid ID'));
    }

    // verify that user has write permissionss for subnet
    if ($Subnets->check_permission($User->user, $POST->subnetId) < 2) {
        $Result->die('danger', _('You do not have permissions to modify hosts in this subnet').'!', true);
    }

    // fetch subnet details
    $subnet = $Subnets->fetch_subnet(null, $POST->subnetId);
    $subnet !== false ?: $Result->die('danger', _('Invalid ID'), true);

    // fake sectionId for snmp-route-all scan
    $POST->sectionId = $subnet->sectionId;

    // full
    if ($POST->type != 'update-icmp' && $subnet->isFull == 1) {
        $Result->die('warning', _('Cannot scan as subnet is market as used'), true);
    }

    // verify php path
    if (! file_exists($Scan->php_exec)) {
        $Result->die('danger', _('Invalid php path'), true);
    }
}

switch ($type) {
    // scan
    case 'scan-icmp':
    case 'scan-telnet':
    case 'scan-snmp-arp':
    case 'snmp-mac':
    case 'snmp-route':
    case 'snmp-route-all':
        // discovery
    case 'update-icmp':
    case 'update-snmp-arp':
        $csrf = $POST->csrf_cookie;
        require __DIR__."/../../subnets/scan/subnet-scan-execute-$type.php";
        break;
    default:
        $Result->die('danger', _('Invalid scan type').' ('.$type.')');
}
