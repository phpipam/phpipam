<?php

/**
 *	Print all available VRFs and configurations
 ************************************************/

# verify that user is logged in
$User->check_user_session();
# perm check
$User->check_module_permissions ("vrf", User::ACCESS_R, true, false);

# include vrf
include (dirname(__FILE__)."/../../tools/vrf/index.php");