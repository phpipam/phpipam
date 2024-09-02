<?php

/**
 *	Format and submit instructions to database
 **********************************************/


/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Result 	= new Result ();
$Log 		= new Logging ($Database, $User->settings);
$Params     = new Params ($_POST);

# verify that user is logged in
$User->check_user_session();
# check maintaneance mode
$User->check_maintaneance_mode ();

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "instructions", $Params->csrf_cookie) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# strip script
$Params->instructions = $User->noxss_html($Params->instructions);

# validate ID
if ($Params->id=="1" || $Params->id=="2") {
    // update
    if($Database->objectExists("instructions", $Params->id)) {
        print "update";
        try { $Database->updateObject("instructions", array("id"=>$Params->id, "instructions"=>$Params->instructions), "id"); }
        catch (Exception $e) {
        	$Result->show("danger", _("Error: ").$e->getMessage(), false);
            $Log->write( _("Instructions updated"), _("Failed to update instructions")."<hr>".$e->getMessage(), 1);
        }
     }
    // create new
    else {
        try { $Database->insertObject("instructions", array("id"=>$Params->id, "instructions"=>$Params->instructions), false, true, false); }
        catch (Exception $e) {
        	$Result->show("danger", _("Error: ").$e->getMessage(), false);
            $Log->write( _("Instructions updated"), _("Failed to update instructions")."<hr>".$e->getMessage(), 1);
        }
    }
    // success
    if (!isset($e)) {
        # ok
        $Log->write( _("Instructions updated"), _("Instructions updated succesfully"), 0);
        $Result->show("success", _("Instructions updated successfully"), true);
    }
}
else {
    $Result->show("danger", _("Invalid ID"), false);
}
