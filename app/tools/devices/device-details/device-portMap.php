<?php

# location
if ($User->settings->enableLocations=="1") {

    // set?
    if ($device->port_map!=0 && strlen($device->port_map)>0) {
        $mapId = $device->port_map;
        include(dirname(__FILE__).'/../../portMaps/index.php'); //Load map
    }
    else {
        $Result->show("info", _("No port map configured for this device"), false);
    }
}