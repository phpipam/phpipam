<?php

# verify that user is logged in
$User->check_user_session();

$User->check_module_permissions ("locations", User::ACCESS_RW, true, false);

# show all nat objects
include(__DIR__."/../../tools/locations/index.php");