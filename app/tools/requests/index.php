<?php

/**
 * Script to get all active IP requests
 ****************************************/

# verify that user is logged in
$User->check_user_session();

# set tools
$tools = true;

# use admin
include(dirname(__FILE__)."/../../admin/requests/index.php");