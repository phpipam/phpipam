<?php
/* functions */
require_once( __DIR__ . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);

# verify that user is logged in
$User->check_user_session();
# admin check
$User->is_admin();

print $User->Crypto->generate_html_safe_token(32);
