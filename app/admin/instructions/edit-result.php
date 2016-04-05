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
$Log 		= new Logging ($Database, $User->settings);

# verify that user is logged in
$User->check_user_session();

# validate csrf cookie
$User->csrf_cookie ("validate", "instructions", $_POST['csrf_cookie']) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# execute
#
#	we do it directly because we permit html tags for instructions
#
try { $Database->updateObject("instructions", array("id"=>1, "instructions"=>$_POST['instructions']), "id"); }
catch (Exception $e) {
	$Result->show("danger", _("Error: ").$e->getMessage(), false);
	$Log->write( "Instructions updated", "Failed to update instructions<hr>".$e->getMessage(), 1);
}
# ok
$Log->write( "Instructions updated", "Instructions updated succesfully", 0);
$Result->show("success", _("Instructions updated successfully"), true);

?>