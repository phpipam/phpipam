<?php

/**
 * Script to edit / add / delete groups
 *************************************************/

# verify that user is logged in
$User->check_user_session();

# print leases
include(dirname(__FILE__)."/../../tools/dhcp/leases.php");
?>