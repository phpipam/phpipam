<?php

/**
 * Script to print locations
 ***************************/

# verify that user is logged in
$User->check_user_session();

# set admin
$admin = $User->is_admin(false);

# fetch custom fields
$custom = $Tools->fetch_custom_fields('locations');

# get hidden fields
$hidden_custom_fields = json_decode($User->settings->hiddenCustomFields, true);
$hidden_custom_fields = is_array(@$hidden_custom_fields['locations']) ? $hidden_custom_fields['locations'] : array();

# check that location support isenabled
if ($User->settings->enableLocations!="1") {
    $Result->show("danger", _("Locations module disabled."), false);
}
else {
    # all locations
    if(!isset($_GET['subnetId'])) {
        include("all-locations-list.php");
    }
    # map
    elseif ($_GET['subnetId']=="map") {
        include("all-locations-map.php");
    }
    # single location
    else {
        include("single-location.php");

    }
}
?>