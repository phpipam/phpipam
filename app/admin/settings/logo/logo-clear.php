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

// define file
$file = dirname(__FILE__)."/../../../../css/1.2/images/logo/logo.png";

# try to remove logo
try {
    if(!is_writable($file)) {
        throw new Exception("File $file not writable");
    }
    // remove
    unlink($file);
    // ok
    $Result->show("success", "Logo removed");
}
catch(Exception $e) {
    $Result->show("danger", "Cannot remove logo file ".$file." - error ".$e->getMessage());
}

?>