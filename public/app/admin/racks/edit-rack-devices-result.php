<?php

/**
 * Edit rack devices result
 ***************************/

/* functions */
require_once __DIR__.'/../../../../functions/functions.php';

// initialize user object
$Database = new Database_PDO;
$User = new User($Database);
$Admin = new Admin($Database, false);
$Result = new Result;
$Racks = new phpipam_rack($Database);

// verify that user is logged in
$User->check_user_session();
// verify module permissions
if ($POST->action == 'edit') {
    $User->check_module_permissions('racks', User::ACCESS_RW, true, true);
} else {
    $User->check_module_permissions('racks', User::ACCESS_RWA, true, true);
}

$User->check_module_permissions('devices', User::ACCESS_R, true, false);

// check maintaneance mode
$User->check_maintaneance_mode();

if ($User->Crypto->csrf_validate($POST->csrf_cookie) === false) {
    $Result->die('danger', _('Invalid CSRF cookie'));
}

// device type?
if (! isset($POST->devicetype) || (! in_array($POST->devicetype, ['device', 'content', 'subrack']))) {
    $Result->die('danger', _('Invalid device type'), true);
}

// ID must be numeric
if (! is_numeric($POST->rackid)) {
    $Result->die('danger', _('Invalid rack identifier'));
}

if (($POST->devicetype == 'device') && ! is_numeric($POST->deviceid)) {
    $Result->die('danger', _('Invalid device identifier'));
}

if (! is_numeric($POST->rack_start)) {
    $Result->die('danger', _('Invalid start value'));
}

if (! is_numeric($POST->rack_size)) {
    $Result->die('danger', _('Invalid size value'));
}

if ($POST->rack_size < 1) {
    $Result->die('danger', _('Invalid size value').'!');
}

$POST->rack_deep = (@$POST->rack_deep == '1') ? '1' : '0';
if ($POST->devicetype == 'subrack' && ! is_numeric($POST->subrackid)) {
    $Result->die('danger', _('Invalid rack identifier'));
}

// name
if ($POST->devicetype == 'content' && (! isset($POST->name) || trim($POST->name === ''))) {
    $Result->die('danger', _('Invalid device name'));
}

// validate rack
$rack = $Admin->fetch_object('racks', 'id', $POST->rackid);
if ($rack === false) {
    $Result->die('danger', _('Rack does not exist'));
}

// prevent nesting subracks
if ($POST->devicetype == 'subrack' && $rack->subrack) {
    $Result->die('danger', _('Nesting subracks is not allowed').'!');
}

// check overflow
if ($Racks->check_device_overflow($POST->rackid, $POST->rack_start, $POST->rack_size)) {
    $Result->die('danger', _('Invalid rack position (overflow)').'!');
}

// check overlaps
if ($User->settings->rackAllowOverlap != '1') {
    if ($Racks->check_device_overlap($POST->rackid, $POST->rack_start, $POST->rack_size, $POST->rack_deep)) {
        $Result->die('danger', _('Overlaps with existing rack item').'!');
    }
}

switch ($POST->devicetype) {
    case 'device':
        // validate device
        $device = $Admin->fetch_object('devices', 'id', $POST->deviceid);
        if ($device === false) {
            $Result->die('danger', _('Invalid Device ID'));
        }

        // set update values
        $values = ['id' => $POST->deviceid,
            'rack' => $POST->rackid,
            'rack_start' => $POST->rack_start,
            'rack_size' => $POST->rack_size,
            'rack_deep' => $POST->rack_deep,
        ];

        // inherit location if it is not already set
        if ($User->settings->enableLocations == '1' && $POST->no_location != '1') {
            if (($device->location == '0' || is_null($device->location)) && ($rack->location != '0' && ! is_null($rack->location))) {
                $values['location'] = $rack->location;
            }
        }

        // update rack
        if (! $Admin->object_modify('devices', 'edit', 'id', $values)) {
            $Result->warn('danger', _('Failed to add device to rack').'!');
        } else {
            $Result->warn('success', _('Device added to rack').'!');
        }

        break;

    case 'content':
        // set values
        $values = ['name' => $POST->name,
            'rack' => $POST->rackid,
            'rack_start' => $POST->rack_start,
            'rack_size' => $POST->rack_size,
            'rack_deep' => $POST->rack_deep,
        ];

        // add content
        if (! $Admin->object_modify('rackContents', 'add', null, $values)) {
            $Result->warn('danger', _('Failed to add device to rack').'!');
        } else {
            $Result->warn('success', _('Device added to rack').'!');
        }

        break;

    case 'subrack':
        // validate the subrack
        $subrack = $Admin->fetch_object('racks', 'id', $POST->subrackid);
        if ($subrack === false) {
            $Result->die('danger', _('Subrack').' '._('does not exist'));
        }

        if (! $subrack->subrack) {
            $Result->die('danger', _('Rack is not designated as a subrack'));
        }

        // is that subrack already placed somewhere?
        if ($Database->getObjectQuery('rackContents', 'SELECT * from `rackContents` where `subrackId` = ? limit 1;', [$POST->subrackid])) {
            $Result->die('danger', _('Subrack is already in a rack'));
        }

        $values = ['name' => unescape_input($subrack->name),
            'rack' => $POST->rackid,
            'rack_start' => $POST->rack_start,
            'rack_size' => $POST->rack_size,
            'rack_deep' => $POST->rack_deep,
            'subrackId' => $subrack->id,
        ];
        if (! $Admin->object_modify('rackContents', 'add', null, $values)) {
            $Result->warn('danger', _('Failed to add subrack to rack').'!');
        } else {
            $Result->warn('success', _('Subrack').' '._('added to rack'));
        }

        break;
}
