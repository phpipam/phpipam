<?php

/**
 * Script to display customer details
 *
 */

# verify that user is logged in
$User->check_user_session();
# verify module permissions
$User->check_module_permissions ("customers", User::ACCESS_R, true);

// get lat long
$OSM = new OpenStreetMap($Database);
if (is_blank($customer->long) && is_blank($customer->lat) && !is_blank($customer->address)) {

    $latlng = $OSM->get_latlng_from_address ($customer->address);
    if($latlng['lat']!=NULL && $latlng['lng']!=NULL) {
        // save
        $Tools->update_latlng ($customer->id, $latlng['lat'], $latlng['lng']);
        $customer->lat = $latlng['lat'];
        $customer->long = $latlng['lng'];
    }
}

$OSM->add_customer($customer);
$OSM->map($height);
