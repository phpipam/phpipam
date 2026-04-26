<?php

/**
 * Script to edit / add / delete groups
 *************************************************/

# verify that user is logged in
$User->check_user_session();
# perm check
$User->check_module_permissions ("dhcp", User::ACCESS_R, true, false);

# print subnets
include(__DIR__."/../../tools/dhcp/subnets.php");