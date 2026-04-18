<?php

/**
 * Script to display device groups
 *
 */

# verify that user is logged in
$User->check_user_session();
# verify that user has permission to module
$User->check_module_permissions ("devices", User::ACCESS_R, true, true);

require('all-device-groups.php');