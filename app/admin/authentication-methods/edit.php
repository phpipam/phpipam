<?php

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin 		= new Admin ($Database);
$Result 	= new Result ();
$Log 		= new Logging ($Database, $User->settings);

# verify that user is logged in
$User->check_user_session();

# create csrf token
$csrf = $User->create_csrf_cookie ();

# if edit check if protected?
if($_POST['action']!="add") {
	$auth_method = $Admin->fetch_object("usersAuthMethod", "id", $_POST['id']);
	if($auth_method->protected=="Yes")								{ $Result->show("danger", _("Method cannot be change as it is protected"), true, true); }
}

# route to proper auth method editing
if(!file_exists(dirname(__FILE__)."/edit-$_POST[type].php"))	{ $Result->show("danger", _("Invalid method type"), true, true); }
else															{ include("edit-$_POST[type].php"); }
?>