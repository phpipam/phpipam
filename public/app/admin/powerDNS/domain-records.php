<?php

/**
 * Script to edit / add / delete records for domain
 *************************************************/

# verify that user is logged in
$User->check_user_session();
# perm check
$User->check_module_permissions ("pdns", User::ACCESS_R, true, false);

# include tools PowerDNS
include __DIR__ . "/../../tools/powerDNS/domain-records.php";
exit();