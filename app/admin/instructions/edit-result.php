<?php

/**
 *	Format and submit instructions to database
 **********************************************/


/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# execute
#
#	we do it directly because we permit html tags for instructions
#
try { $Database->updateObject("instructions", array("id"=>1, "instructions"=>$_POST['instructions']), "id"); }
catch (Exception $e) {
	$Result->show("danger", _("Error: ").$e->getMessage(), false);
	write_log( "Instructions updated", "Failed to update instructions<hr>".$e->getMessage(), 2, $User->username);
}
# ok
write_log( "Instructions updated", "Instructions updated succesfully", 0, $User->username);
$Result->show("success", _("Instructions updated successfully"), true);

?>