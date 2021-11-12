<?php

# verify that user is logged in
$User->check_user_session();

# show all nat objects
include(dirname(__FILE__)."/../../tools/nat/index.php");