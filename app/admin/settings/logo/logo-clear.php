<?php
/*
 * CSV import verify + parse data
 *************************************************/

 /* functions */
require( dirname(__FILE__) . '/../../../../functions/functions.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# try to remove logo
@unlink(dirname(__FILE__)."/../../../../css/1.2/images/logo/logo.png");

$err = error_get_last();

# check
if ($err === NULL) {
    $Result->show("success", "Logo removed");
}
else {
    $Result->show("danger", "Cannot remove logo file ".$err['file']." - error ".$err['message']);
}

?>