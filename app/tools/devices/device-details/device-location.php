<?php

# location
if ($User->settings->enableLocations=="1" && $User->get_module_permissions ("locations")>=User::ACCESS_R && $User->get_module_permissions ("devices")>=User::ACCESS_R) {

    print "<h4>"._('Location')."</h4><hr>";

    // set?
    if ($device->location!=0 && !is_blank($device->location)) {
        // array
        $device = (array) $device;
        // fake data
        $loc_old = $location;
        unset($location);
        $location_index = $device['location'];

        $sid_orig = $_GET['subnetId'];
        $_GET['subnetId'] = $device['location'];

        $hide_title = true;
        include(dirname(__FILE__).'/../../locations/single-location.php');

        $_GET['subnetId'] = $sid_orig;
        $location = $loc_old;
    }
    else {
        $Result->show("info", _("Location is not set for this device"), false);
    }
}