<?php

/**
 * Script tomanage custom IP fields
 ****************************************/


/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# some verifications
if( (empty($POST->current)) || (empty($POST->next)) ) 							{ $Result->show("danger", _('Fields cannot be empty')."!", true); }


/* reorder */
if(!$Admin->reorder_custom_fields($POST->table, $POST->next, $POST->current)) 	{ $Result->show("danger", _('Reordering failed')."!", true); }
else 																					{ $Result->show("success", _('Fields reordered successfully')."!");}

?>
