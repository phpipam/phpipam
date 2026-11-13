<?php

/**
 *  firewall zone zones-result.php
 *  verify and update zone informations
 *****************************************/

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
if ($POST->action != 'add' && $POST->action != 'delete') {
    $Result->die('danger', _('Invalid action.'));
}

// check the mastersubnet  ID. valid value: integer
if ($POST->masterSubnetId && ! preg_match('/^[0-9]+$/i', (string) $POST->masterSubnetId)) {
    $Result->die('danger', _('Invalid subnet ID.'));
} elseif (! $POST->masterSubnetId) {
    $Result->die('danger', _('Please choose a appropriate network to bind to the firewall zone.'));
}

// validate network ID informations
if ($POST->network) {
    foreach ($POST->network as $network) {
        if (! preg_match('/^[0-9]+$/i', (string) $network)) {
            $Result->die('danger', _('Invalid network ID.'));
        }
    }
}

// check if the network information should be delivered as form data
if ($POST->noZone == 1) {
    // update
    if (! $Zones->check_zone_network($POST->masterSubnetId)) {
        $Result->die('danger', _('Cannot add the network to the zone.'));
    } else {
        $Result->die('success', _('Network successfully added.'));
    }
}

// check the zone ID. valid value: integer
if ($POST->netZoneId && ! preg_match('/^[0-9]+$/i', (string) $POST->netZoneId)) {
    $Result->die('danger', _('Invalid zone ID.'));
} else {
    // update
    if ($POST->action == 'add') {
        if (! $Zones->add_zone_network($POST->netZoneId, $POST->masterSubnetId)) {
            $Result->die('danger', _('Cannot add the network to the zone.'));
        } else {
            $Result->die('success', _('Network successfully added.'));
        }
    } elseif ($POST->action == 'delete') {
        if (! $Zones->delete_zone_network($POST->netZoneId, $POST->masterSubnetId)) {
            $Result->die('danger', _('Cannot delete the network mapping.'));
        } else {
            $Result->die('success', _('Successfully deleted the network mapping.'));
        }
    }
}
