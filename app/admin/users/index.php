<?php

/**
 * Script to edit / add / delete users
 *************************************************/

# verify that user is logged in
$User->check_user_session();

# print all or specific user?
if(isset($_GET['subnetId']))	{ include("print-user.php"); }
else							{ include("print-all.php"); }
?>