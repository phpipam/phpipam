<?php

/**
 * Script to print single location
 ***************************/

# verify that user is logged in
$User->check_user_session();

# fetch location
if(!isset($location)) {
    $location = $Tools->fetch_object("locations", "id", $location_index);
}

# perm check
if ($User->get_module_permissions ("locations")==User::ACCESS_NONE) {
    $Result->show("danger", _("You do not have permissions to access this module"), false);
}
# if none than print
elseif(!is_object($location)) {
    $Result->show("info","Invalid location", false);
} else {
    $OSM = new OpenStreetMap($Database);

    // recode
    if (is_blank($location->long) && is_blank($location->lat) && !is_blank($location->address)) {
        $latlng = $OSM->get_latlng_from_address ($location->address);
        if(isset($latlng['lat']) && isset($latlng['lng'])) {
            // save
            $Tools->update_latlng ($location->id, $latlng['lat'], $latlng['lng']);
            $location->lat = $latlng['lat'];
            $location->long = $latlng['lng'];
        }
    }

    # resize ?
    $resize = @$resize === false ? false : true;

    # no long/lat
    if( (!is_blank($location->long) && !is_blank($location->lat))) {
        $OSM->add_location($location);
        $OSM->map($height);
    }
}