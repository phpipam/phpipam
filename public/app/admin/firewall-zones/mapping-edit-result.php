<?php

/**
 *  firewall zone mapping-edit-result.php
 *  verify and update mapping informations
 **********************************************/

// functions
require_once __DIR__.'/../../../../functions/functions.php';

// initialize classes
$Database = new Database_PDO;
$User = new User($Database);
$Result = new Result;
$Zones = new FirewallZones($Database);

// validate session parameters
$User->check_user_session();
// perm check popup
if ($POST->action == 'edit') {
    $User->check_module_permissions('fwzones', User::ACCESS_RW, true, true);
} else {
    $User->check_module_permissions('fwzones', User::ACCESS_RWA, true, true);
}

// validate the action type
if (! in_array($POST->action, ['add', 'delete', 'edit'])) {
    $Result->die('danger', _('Invalid action.'));
}

// check the zone alias. valid values are alphanumeric characters and special characters like ".-_: "
if ($POST->alias && ! preg_match('/^[0-9a-z.\/\-_ :]+$/i', (string) $POST->alias)) {
    $Result->die('danger', _('Invalid zone alias value.'));
}

// check the interface name. valid values are alphanumeric characters and special characters like ".-_/: "
if ($POST->interface && ! preg_match('/^[0-9a-z.\/\-_ :]+$/i', (string) $POST->interface)) {
    $Result->die('danger', _('Invalid interface.'));
}

if ($POST->action != 'delete') {
    // check the zone  ID. valid value: integer
    if (! preg_match('/^[0-9]+$/i', (string) $POST->zoneId)) {
        $Result->die('danger', _('Invalid zone ID.'));
    } elseif (preg_match('/^0$/i', (string) $POST->zoneId)) {
        $Result->die('danger', _('Invalid zone ID.'));
    }

    // check the device ID. valid value: integer
    if (! preg_match('/^[0-9]+$/i', (string) $POST->deviceId)) {
        $Result->die('danger', _('Invalid device ID.'));
    } elseif (preg_match('/^0$/i', (string) $POST->deviceId)) {
        $Result->die('danger', _('Please select a device.'));
    }

    // check the mapping ID. valid value: integer
    if ($POST->id && ! preg_match('/^[0-9]+$/i', (string) $POST->id)) {
        $Result->die('danger', _('Invalid mapping ID.'));
    }
}

// build the query parameter arrary
$values = ['id' => $POST->id,
    'zoneId' => $POST->zoneId,
    'alias' => $POST->alias,
    'deviceId' => $POST->deviceId,
    'interface' => $POST->interface,
];
// modify
if (! $Zones->modify_mapping($POST->action, $values)) {
    $Result->die('danger', _('Cannot add mapping'));
} else {
    $Result->die('success', _('Mapping modified successfully'));
}
