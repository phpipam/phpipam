<?php

# verify that user is logged in
$User->check_user_session();
# perm check
$User->check_module_permissions ("pstn", 1, true, false);
# show all prefix objects
include(dirname(__FILE__)."/../../tools/pstn-prefixes/index.php");