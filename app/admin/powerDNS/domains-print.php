<?php

/**
 * Script to edit / add / delete groups
 *************************************************/

# verify that user is logged in
$User->check_user_session();

# include tools PowerDNS
include dirname(__FILE__) . "/../../tools/powerDNS/domains-print.php";