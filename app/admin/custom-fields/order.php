<?php

/**
 * Script tomanage custom IP fields
 ****************************************/


/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# some verifications
if( (empty($_POST['current'])) || (empty($_POST['next'])) ) 							{ $Result->show("danger", _('Fileds cannot be empty')."!", true); }


/* reorder */
if(!$Admin->reorder_custom_fields($_POST['table'], $_POST['next'], $_POST['current'])) 	{ $Result->show("danger", _('Reordering failed')."!", true); }
else 																					{ $Result->show("success", _('Fields reordered successfully')."!");}

?>