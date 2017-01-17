<?php

/**
 * Script to disaply api edit result
 *************************************/

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();
# check maintaneance mode
$User->check_maintaneance_mode ();

# strip input tags
$_POST = $Admin->strip_input_tags($_POST);

# validate csrf cookie
$User->csrf_cookie ("validate", "apiedit", $_POST['csrf_cookie']) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

/* checks */
$error = array();

if($_POST['action']!="delete") {
	# code must be exactly 32 chars long and alfanumeric if app_security = crypt
	if($_POST['app_security']=="crypt") {
	if(strlen($_POST['app_code'])!=32 || !ctype_alnum($_POST['app_code']))									{ $error[] = "Invalid application code"; }
	}
	# name must be more than 2 and alphanumberic
	if(strlen($_POST['app_id'])<3 || strlen($_POST['app_id'])>12 || !ctype_alnum($_POST['app_id']))			{ $error[] = "Invalid application id"; }
	# permissions must be 0,1,2
	if($_POST['app_security']!="user") {
	if(!($_POST['app_permissions']==0 || $_POST['app_permissions']==1 || $_POST['app_permissions'] ==2 || $_POST['app_permissions'] ==3 ))	{ $error[] = "Invalid permissions"; }
	}
	# locak check
	if($_POST['app_lock']=="1") {
    	if(!is_numeric($_POST['app_lock_wait']))                                                            { $error[] = "Invalid wait value"; }
    	elseif ($_POST['app_lock_wait']<1)                                                                  { $error[] = "Invalid wait value"; }
	}
}

# die if errors
if(sizeof($error) > 0) {
	$Result->show("danger", $error, true);
}
else {
	# create array of values for modification
	$values = array("id"=>@$_POST['id'],
					"app_id"=>$_POST['app_id'],
					"app_code"=>@$_POST['app_code'],
					"app_permissions"=>@$_POST['app_permissions'],
					"app_security"=>@$_POST['app_security'],
					"app_lock"=>@$_POST['app_lock'],
					"app_lock_wait"=>@$_POST['app_lock_wait'],
					"app_comment"=>@$_POST['app_comment']);

	# execute
	if(!$Admin->object_modify("api", $_POST['action'], "id", $values)) 	{ $Result->show("danger",  _("API $_POST[action] error"), true); }
	else 																{ $Result->show("success", _("API $_POST[action] success"), true); }
}

?>