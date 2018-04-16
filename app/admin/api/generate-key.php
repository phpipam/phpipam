<?php
/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);

# verify that user is logged in
$User->check_user_session();

print $User->Crypto->generate_token();
?>