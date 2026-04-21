<?php

/**
 * Script to edit / add / delete groups
 *************************************************/

# verify that user is logged in
$User->check_user_session();
# perm check
$User->check_module_permissions ("pdns", User::ACCESS_R, true, false);

# include tools PowerDNS
include dirname(__FILE__) . "/../../tools/powerDNS/domains-print.php";