<?php

/**
 * Script to edit / add / delete groups
 *************************************************/

# verify that user is logged in
$User->check_user_session();

# print subnets
include(dirname(__FILE__)."/../../tools/dhcp/subnets.php");
?>