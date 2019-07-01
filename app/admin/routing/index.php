<?php

/**
 * Script to manage routing
 *****************************/

# verify that user is logged in
$User->check_user_session();

# include table
include(dirname(__FILE__)."/../../tools/routing/index.php");