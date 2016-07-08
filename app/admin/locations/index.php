<?php

# verify that user is logged in
$User->check_user_session();

# Make sure user is admin
$User->is_admin(true);

# show all nat objects
include(dirname(__FILE__)."/../../tools/locations/index.php");
?>