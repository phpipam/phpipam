<?php
# verify that user is logged in
$User->check_user_session();

if($User->get_module_permissions ("locations")<1) {
    $Result->show ("danger", _("You do not have permissions to access this module"), true);
}
# only if set
elseif (is_numeric($address['location'])) {
    if($address['location']>0) {
        // fake data
        $loc_old = $location;
        unset($location);
        $location_index = $address['location'];
        $resize = false;
        $height = "500px;";

        $sid_orig = $_GET['subnetId'];
        $_GET['subnetId'] = $address['location'];

        $hide_title = true;

        include(dirname(__FILE__).'/../../../tools/locations/single-location.php');

        // back
        $_GET['subnetId'] = $sid_orig;
        $location = $loc_old;
    }
    else {
        $Result->show('info', _('Location not set !'), false);
    }
}
else {
    $Result->show('info', _('Location not set !'), false);
}