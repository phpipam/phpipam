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

// validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "logs", $GET->csrf) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# truncate logs table
if(!$Admin->truncate_table("logs")) 	{ $Result->show("danger",  _('Error clearing logs')."!", true); }
else 									{ $Result->show("success", _('Logs cleared successfully')."!", true); }