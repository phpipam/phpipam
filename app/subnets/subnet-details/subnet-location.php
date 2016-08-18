<?php
# verify that user is logged in
$User->check_user_session();

# only if set
if (is_numeric($subnet['location'])) {
    if($subnet['location']>0) {
        // fake data
        $loc_old = $location;
        unset($location);
        $location_index = $subnet['location'];
        $resize = false;
        $height = "500px;";

        include(dirname(__FILE__).'/../../tools/locations/single-location-map.php');

        // back
        $location = $loc_old;
    }
    else {
        $Result->show('info', _('Location not set !'), false);
    }
}
else {
    $Result->show('info', _('Location not set !'), false);
}
?>