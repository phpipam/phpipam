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

# validate ID
if ($_POST['id']=="1" || $_POST['id']=="2") {
    // update
    if($Database->objectExists("instructions", $_POST['id'])) {
        print "update";
        try { $Database->updateObject("instructions", array("id"=>$_POST['id'], "instructions"=>$_POST['instructions']), "id"); }
        catch (Exception $e) {
        	$Result->show("danger", _("Error: ").$e->getMessage(), false);
        	$Log->write( "Instructions updated", "Failed to update instructions<hr>".$e->getMessage(), 1);
        }
     }
    // create new
    else {
        try { $Database->insertObject("instructions", array("id"=>$_POST['id'], "instructions"=>$_POST['instructions']), false, true, false); }
        catch (Exception $e) {
        	$Result->show("danger", _("Error: ").$e->getMessage(), false);
        	$Log->write( "Instructions updated", "Failed to update instructions<hr>".$e->getMessage(), 1);
        }
    }
    // success
    if (!isset($e)) {
        # ok
        $Log->write( "Instructions updated", "Instructions updated succesfully", 0);
        $Result->show("success", _("Instructions updated successfully"), true);
    }
}
else {
    $Result->show("danger", _("Invalid ID"), false);
}

?>