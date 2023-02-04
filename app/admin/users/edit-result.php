<?php

/**
 * Script to display usermod result
 *************************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database       = new Database_PDO;
$User           = new User ($Database);
$Admin          = new Admin ($Database);
$Tools          = new Tools ($Database);
$Result         = new Result ();
$Password_check = new Password_check ();

# verify that user is logged in
$User->check_user_session();
# check maintaneance mode
$User->check_maintaneance_mode ();

# strip input tags
$_POST = $Admin->strip_input_tags ($_POST);
$_POST = $Admin->trim_array_objects ($_POST);

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "user", $_POST['csrf_cookie']) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# fetch auth method
$auth_method = $Admin->fetch_object ("usersAuthMethod", "id", $_POST['authMethod']);
$auth_method!==false ? : $Result->show("danger", _("Invalid authentication method"), true);


/* checks */

# ID must be numeric
if($_POST['action']=="edit"||$_POST['action']=="delete") {
	if(!is_numeric($_POST['userId']))									{ $Result->show("danger", _("Invalid ID"), true); }
}

# if password changes check and hash passwords
if($auth_method->type != "local") { $_POST['password1'] = ""; $_POST['password2'] = ""; }
if((!is_blank(@$_POST['password1']) || (@$_POST['action']=="add") && $auth_method->type=="local")) {
	//checks
	if($_POST['password1']!=$_POST['password2'])						{ $Result->show("danger", _("Passwords do not match"), true); }
	if(strlen($_POST['password1'])<8)									{ $Result->show("danger", _("Password must be at least 8 characters long!"), true); }

	//enforce password policy
	$policy = (pf_json_decode($User->settings->passwordPolicy, true));
	$Password_check->set_requirements  ($policy, pf_explode(",",$policy['allowedSymbols']));
	if (!$Password_check->validate ($_POST['password1'])) 				{ $Result->show("danger alert-danger ", _('Password validation errors').":<br> - ".implode("<br> - ", $Password_check->get_errors ()), true); }

	//hash passowrd
	$_POST['password1'] = $User->crypt_user_pass ($_POST['password1']);
}

# general checks
if(is_blank(@$_POST['real_name']))										{ $Result->show("danger", _("Real name field is mandatory!"), true); }
# email format must be valid
if (!$Tools->validate_email(@$_POST['email'])) 						{ $Result->show("danger", _("Invalid email address!"), true); }

# username must not already exist (if action is add)
if ($_POST['action']=="add") {
	//username > 8 chars
	if ($auth_method->type=="local") {
		if(strlen($_POST['username'])<3)								{ $Result->show("danger", _("Username must be at least 3 characters long!"), true); }
	} else {
		if(is_blank($_POST['username']))								{ $Result->show("danger", _("Username must be at least 1 character long!"), true); }
	}
	//check duplicate
	if($Admin->fetch_object("users", "username", $_POST['username'])!==false) {
																		{ $Result->show("danger", _("User")." ".$_POST['username']." "._("already exists!"), true); }
	}
}
# admin user cannot be deleted
if($_POST['action']=="delete" && $_POST['userId']==1) 			{ $Result->show("danger", _("Admin user cannot be deleted"), true); }
# admin user cannot be disabled
if($_POST['disabled']=="Yes" && $_POST['userId']==1) 			{ $Result->show("danger", _("Admin user cannot be disabled"), true); }

# custom fields check
$myFields = $Tools->fetch_custom_fields('users');
if(sizeof($myFields) > 0) {
	foreach($myFields as $myField) {
		# replace possible ___ back to spaces!
		$myField['nameTest']      = str_replace(" ", "___", $myField['name']);

		if(isset($_POST[$myField['nameTest']])) { $_POST[$myField['name']] = $_POST[$myField['nameTest']];}

		//booleans can be only 0 and 1!
		if($myField['type']=="tinyint(1)") {
			if($_POST[$myField['name']]>1) {
				$_POST[$myField['name']] = "";
			}
		}
		//not null!
		if($myField['Null']=="NO" && is_blank($_POST[$myField['name']])) { $Result->show("danger", $myField['name']." "._("can not be empty!"), true); }
	}
}


/* update */

# formulate update values
# nothing to do here for l10n, the content of the array goes into the database
$values = array(
				"id"             =>@$_POST['userId'],
				"real_name"      =>$_POST['real_name'],
				"username"       =>$_POST['username'],
				"email"          =>$_POST['email'],
				"role"           =>$_POST['role'],
				"authMethod"     =>$_POST['authMethod'],
				"lang"           =>$_POST['lang'],
				"mailNotify"     =>$_POST['mailNotify'],
				"mailChangelog"  =>$_POST['mailChangelog'],
				"theme"          =>$_POST['theme']=="default" ? "" : $_POST['theme'],
				"disabled"       =>$_POST['disabled']=="Yes" ? "Yes" : "No"
				);



# custom fields
if (sizeof($myFields)>0) {
    foreach($myFields as $myField) {
		# replace possible ___ back to spaces!
		$myField['nameTest']      = str_replace(" ", "___", $myField['name']);

		if(isset($_POST[$myField['nameTest']])) { $values[$myField['name']] = $_POST[$myField['nameTest']];}
    }
}
# update pass ?
if(!is_blank(@$_POST['password1']) || (@$_POST['action']=="add" && $auth_method->type=="local")) {
	$values['password'] = $_POST['password1'];
}
# pass change
if(isset($_POST['passChange']) && $auth_method->type=="local") {
	$values['passChange'] = "Yes";
}
# set groups user belongs to
if($_POST['role']=="Administrator") {
	$values['groups'] = null;
} else {
	foreach($_POST as $key=>$post) {
		if(substr($key, 0,5) == "group") {
			$group[substr($key, 5)] = substr($key, 5);
		}
	}
	$values['groups'] = json_encode(@$group);
}

# permissions
$permissions = [];
# check
foreach ($User->get_modules_with_permissions() as $m) {
	if (isset($_POST['perm_'.$m])) {
		if (is_numeric($_POST['perm_'.$m])) {
			$permissions[$m] = $_POST['perm_'.$m];
		}
	}
}
# formulate permissions
$values['module_permissions'] = json_encode($permissions);

# execute
if(!$Admin->object_modify("users", $_POST['action'], "id", $values)) {
    $Result->show("danger", _("User")." ".$_POST["action"]." "._("failed").'!', true);
}
else {
    $Result->show("success", _("User")." ".$_POST["action"]." "._("successful").'!', false);
}

# mail user
if($Admin->verify_checkbox(@$_POST['notifyUser'])!="0") { include("edit-notify.php"); }
