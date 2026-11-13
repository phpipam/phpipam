<?php

/**
 *  subnet-to-zone-save.php
 *  save subnet to zone binding
 *********************************/

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

// validate $POST->subnetId values
if (! preg_match('/^[0-9]+$/i', (string) $POST->subnetId)) {
    $Result->die('danger', _('Invalid subnet ID. Do not manipulate the POST values!'));
}

// validate $POST->zoneId values
if (! preg_match('/^[0-9]+$/i', (string) $POST->zoneId) || $POST->zoneId == 0) {
    $Result->die('danger', _('Invalid or no zone ID. '));
}

// validate $POST->deviceId values
if ($POST->deviceId && ! preg_match('/^[0-9]+$/i', (string) $POST->deviceId)) {
    $Result->die('danger', _('Invalid device ID. Do not manipulate the POST values!'));
}

// check the zone alias. valid values are alphanumeric characters and special characters like ".-_: "
if ($POST->alias && ! preg_match('/^[0-9a-z.\/\-_ :]+$/i', (string) $POST->alias)) {
    $Result->die('danger', _('Invalid zone alias value.'));
}

// check the interface name. valid values are alphanumeric characters and special characters like ".-_/: "
if ($POST->interface && ! preg_match('/^[0-9a-z.\/\-_ :]+$/i', (string) $POST->interface)) {
    $Result->die('danger', _('Invalid interface.'));
}

// check if there is any device mapping necessary
if ($POST->deviceId) {
    // build the query parameter arrary
    $values = ['id' => '',
        'zoneId' => $POST->zoneId,
        'alias' => $POST->alias,
        'deviceId' => $POST->deviceId,
        'interface' => $POST->interface,
    ];
    // modify
    if (! $Zones->modify_mapping('add', $values)) {
        $Result->die('danger', _('Cannot add mapping'));
    }
}

// check and then add the network to the zone if possible
if ($Zones->check_zone_network($POST->subnetId)) {
    if (! $Zones->add_zone_network($POST->zoneId, $POST->subnetId)) {
        $Result->die('danger', _('Cannot add this network to a zone.'));
    } else {
        $Result->die('success', _('Zone modified successfully'));
    }
}
