<?php

/**
 *	clear log files
 **********************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# truncate logs table
if(!$Admin->truncate_table("changelog")) 	{ $Result->show("danger",  _('Error clearing logs')."!", true); }
else 										{ $Result->show("success", _('Logs cleared successfully')."!", true); }