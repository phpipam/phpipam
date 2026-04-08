<?php

/**
 *
 *set users widgets
 *
 */

# include required scripts
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize required objects
$Database 	= new Database_PDO;
$Result		= new Result;
$User		= new User ($Database);

# verify that user is logged in
$User->check_user_session();
# check if site is demo
$User->is_demo();

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "user-menu", $POST->csrf_cookie) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

/* save widgets */
if (!$User->self_update_widgets ($POST->widgets)) 	{ $Result->show("danger", _('Error updating'),true); }
else 													{ $Result->show("success", _('Widgets updated'),true); }