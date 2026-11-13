<?php

/**
 * Script to print locations
 ***************************/

// verify that user is logged in
$User->check_user_session();

// set admin
$admin = $User->is_admin(false);

// fetch custom fields
$custom = $Tools->fetch_custom_fields('locations');

// get hidden fields
$hidden_custom_fields = db_json_decode($User->settings->hiddenCustomFields, true);
$hidden_custom_fields = is_array(@$hidden_custom_fields['locations']) ? $hidden_custom_fields['locations'] : [];

// perm check
if ($User->get_module_permissions('locations') == User::ACCESS_NONE) {
    $Result->warn('danger', _('You do not have permissions to access this module'));
}
// check that location support isenabled
elseif ($User->settings->enableLocations != '1') {
    $Result->warn('danger', _('Locations module disabled.'));
} else {
    // all locations
    if (! isset($GET->subnetId)) {
        include __DIR__.'/all-locations-list.php';
    }
    // map
    elseif ($GET->subnetId == 'map') {
        include __DIR__.'/all-locations-map.php';
    }
    // single location
    else {
        include __DIR__.'/single-location.php';
    }
}
