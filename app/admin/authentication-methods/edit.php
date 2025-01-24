<?php

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin 		= new Admin ($Database);
$Result 	= new Result ();
$Log 		= new Logging ($Database, $User->settings);

# verify that user is logged in
$User->check_user_session();

# create csrf token
$csrf = $User->Crypto->csrf_cookie ("create", "authmethods");

# if edit check if protected?
if($POST->action!="add") {
	$auth_method = $Admin->fetch_object("usersAuthMethod", "id", $POST->id);
	if($auth_method->protected=="Yes")								{ $Result->show("danger", _("Method cannot be change as it is protected"), true, true); }
}

# check for permitted auth methods
$permitted_methods = $User->fetch_available_auth_method_types();

# route to proper auth method editing
if(!file_exists(dirname(__FILE__)."/edit-".$POST->type.".php"))	{ $Result->show("danger", _("Invalid method type"), true, true); }
elseif (!in_array($POST->type, $permitted_methods))			{ $Result->show("danger", _("Invalid method type"), true, true); }
else															{ include("edit-".$POST->type.".php"); }
