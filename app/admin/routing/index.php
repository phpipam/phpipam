<?php

/**
 * Script to manage routing
 *****************************/

# verify that user is logged in
$User->check_user_session();
# verify module permissions
$User->check_module_permissions ("routing", User::ACCESS_RW, true);

# include table
include(dirname(__FILE__)."/../../tools/routing/index.php");