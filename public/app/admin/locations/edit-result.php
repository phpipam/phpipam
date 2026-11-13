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
    $User->check_module_permissions('locations', User::ACCESS_RW, true, false);
} else {
    $User->check_module_permissions('locations', User::ACCESS_RWA, true, false);
}

// check maintaneance mode
$User->check_maintaneance_mode();

if ($User->Crypto->csrf_validate($POST->csrf_cookie) === false) {
    $Result->die('danger', _('Invalid CSRF cookie'));
}

// validations
if ($POST->action == 'delete' || $POST->action == 'edit') {
    if ($Admin->fetch_object('locations', 'id', $POST->id) === false) {
        $Result->warn('danger', _('Invalid Location object identifier'));
    }
}

if ($POST->action == 'add' || $POST->action == 'edit') {
    // name
    if (is_blank($POST->name)) {
        $Result->die('danger', _('Name must have at least 1 character'));
    }

    // lat, long
    if ($POST->action !== 'delete') {
        // lat
        if (! is_blank($POST->lat)) {
            if (! preg_match('/^(\-?\d+(\.\d+)?).\s*(\-?\d+(\.\d+)?)$/', (string) $POST->lat)) {
                $Result->die('danger', _('Invalid Latitude'));
            }
        }

        // long
        if (! is_blank($POST->long)) {
            if (! preg_match('/^(\-?\d+(\.\d+)?).\s*(\-?\d+(\.\d+)?)$/', (string) $POST->long)) {
                $Result->die('danger', _('Invalid Longitude'));
            }
        }

        // fetch latlng
        if (is_blank($POST->lat) && is_blank($POST->long) && ! is_blank($POST->address)) {
            $OSM = new OpenStreetMap($Database);
            $latlng = $OSM->get_latlng_from_address($POST->address);
            if (isset($latlng['lat']) && isset($latlng['lng'])) {
                $POST->lat = $latlng['lat'];
                $POST->long = $latlng['lng'];
            } else {
                if (! Config::ValueOf('offline_mode')) {
                    $Result->warn('warning', _('Failed to update location lat/lng from Nominatim').'.<br>'.$latlng['error']);
                }
            }
        }
    }
}

// set values
$values = [
    'id' => $POST->id,
    'name' => $POST->name,
    'address' => $POST->address,
    'lat' => $POST->lat,
    'long' => $POST->long,
    'description' => $POST->description,
];

// fetch custom fields
$update = $Tools->update_POST_custom_fields('locations', $POST->action, $POST);
$values = array_merge($values, $update);

// execute update
if (! $Admin->object_modify('locations', $POST->action, 'id', $values)) {
    $Result->warn('danger', _('Location').' '.$User->get_post_action().' '._('failed'));
} else {
    $Result->warn('success', _('Location').' '.$User->get_post_action().' '._('successful'));
}

// remove all references
if ($POST->action == 'delete') {
    $Admin->remove_object_references('circuits', 'location1', $values['id']);
    $Admin->remove_object_references('circuits', 'location2', $values['id']);
    $Admin->remove_object_references('subnets', 'location', $values['id']);
    $Admin->remove_object_references('devices', 'location', $values['id']);
    $Admin->remove_object_references('racks', 'location', $values['id']);
}
