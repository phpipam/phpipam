<?php

/* functions */
require_once __DIR__.'/../../../../functions/functions.php';

// initialize user object
$Database = new Database_PDO;
$User = new User($Database);
$Admin = new Admin($Database, false);
$Tools = new Tools($Database);
$Result = new Result;

// verify that user is logged in
$User->check_user_session();

// perm check popup
if ($POST->action == 'edit') {
    $User->check_module_permissions('pstn', User::ACCESS_RW, true, false);
} else {
    $User->check_module_permissions('pstn', User::ACCESS_RWA, true, false);
}

if ($User->Crypto->csrf_validate($POST->csrf_cookie) === false) {
    $Result->die('danger', _('Invalid CSRF cookie'));
}

// validations
if ($POST->action == 'delete' || $POST->action == 'edit') {
    if ($Admin->fetch_object('pstnNumbers', 'id', $POST->id) === false) {
        $Result->warn('danger', _('Invalid PSTN number identifier'));
    }
}

if ($POST->action == 'add' || $POST->action == 'edit') {
    // name
    if (strlen((string) $POST->name) < 3) {
        $Result->die('danger', _('Name must have at least 3 characters'));
    }

    // number
    if (! is_numeric($POST->number)) {
        $Result->die('danger', _('Number must be numeric'));
    }

    // check prefix
    $prefix = $Tools->fetch_object('pstnPrefixes', 'id', $POST->prefix);
    if ($prefix === false) {
        $Result->die('danger', _('Invalid prefix'));
    }

    // duplicate check
    if ($POST->action == 'add') {
        if ($Tools->check_number_duplicates($prefix->id, $POST->number)) {
            $Result->die('danger', _('Duplicate number'));
        }
    }

    // ranges
    $prefix->prefix_raw = $Tools->prefix_normalize($prefix->prefix);
    $prefix->prefix_raw_start = $Tools->prefix_normalize($prefix->prefix.$prefix->start);
    $prefix->prefix_raw_stop = $Tools->prefix_normalize($prefix->prefix.$prefix->stop);

    // pad number
    $POST->number = str_pad((string) $POST->number, (strlen((string) $prefix->prefix_raw_start) - strlen((string) $prefix->prefix_raw)), '0', STR_PAD_LEFT);

    $POST->prefix_number = $Tools->prefix_normalize($prefix->prefix.$POST->number);

    // number must be inside range
    if ($POST->prefix_number < $prefix->prefix_raw_start || $POST->prefix_number > $prefix->prefix_raw_stop) {
        $Result->die('danger', _('Number not inside prefix'));
    }
}

// set values
$values = [
    'id' => $POST->id,
    'name' => $POST->name,
    'prefix' => $POST->prefix,
    'number' => $POST->number,
    'owner' => $POST->owner,
    'state' => $POST->state,
    'deviceId' => $POST->deviceId,
    'description' => $POST->description,
];
// remove device
if ($User->get_module_permissions('devices') < User::ACCESS_RW) {
    unset($values['deviceId']);
}

// fetch custom fields
$update = $Tools->update_POST_custom_fields('pstnNumbers', $POST->action, $POST);
$values = array_merge($values, $update);

// execute update
if (! $Admin->object_modify('pstnNumbers', $POST->action, 'id', $values)) {
    $Result->warn('danger', _('Number').' '.$User->get_post_action().' '._('failed'));
} else {
    $Result->warn('success', _('Number').' '.$User->get_post_action().' '._('successful'));
}
