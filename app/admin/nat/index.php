<?php

# verify that user is logged in
$User->check_user_session();

# validate permissions
$User->check_module_permissions ("nat", User::ACCESS_RW, true, true);

# show all nat objects
include(dirname(__FILE__)."/../../tools/nat/index.php");