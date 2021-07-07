<?php

/**
 * Script to print Port Maps
 ***************************/

# verify that user is logged in
$User->check_user_session();

# set admin
$admin = $User->is_admin(false);


# all Port Maps
if (!isset($_GET['subnetId'])) {
    include("all-port-maps.php");
}

# single Port Map
else {
    include("single-port-map.php");
}
?>