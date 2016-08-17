<?php

/**
 * Script to edit / add / delete records for domain
 *************************************************/

# verify that user is logged in
$User->check_user_session();

# include tools PowerDNS
include dirname(__FILE__) . "/../../tools/powerDNS/domain-records.php";
exit();
