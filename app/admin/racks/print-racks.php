<?php

/**
 * Script to print racks
 ***************************/

# verify that user is logged in
$User->check_user_session();

# include tools rack
include(dirname(__FILE__) . "/../../tools/racks/index.php");
?>