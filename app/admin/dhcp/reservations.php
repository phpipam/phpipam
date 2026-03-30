<?php

# verify that user is logged in
$User->check_user_session();
# perm check
$User->check_module_permissions ("dhcp", User::ACCESS_R, true, false);

# print reservations
include(dirname(__FILE__)."/../../tools/dhcp/reservations.php");