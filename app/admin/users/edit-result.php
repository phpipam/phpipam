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
# check if site is demo
$User->is_demo();
# check maintaneance mode
$User->check_maintaneance_mode ();

# Trim input tags
foreach($POST as $k => $v) {
	if (is_string($v)) {
		$POST->{$k} = trim($v);
	}
}

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "user", $POST->csrf_cookie) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

/* checks */

# ID must be numeric
if($POST->action=="edit"||$POST->action=="delete") {
	if(!is_numeric($POST->userId))									{ $Result->show("danger", _("Invalid ID"), true); }
}


# Add / edit actions
if($POST->action!="delete") {
	// validate authMethod
	$auth_method = $Admin->fetch_object ("usersAuthMethod", "id", $POST->authMethod);
	$auth_method!==false ? : $Result->show("danger", _("Invalid authentication method"), true);

	# if password changes check and hash passwords
	if($auth_method->type != "local") { $POST->password1 = ""; $POST->password2 = ""; }
	if(!is_blank($POST->password1) || ($POST->action=="add") && $auth_method->type=="local") {
		//checks
		if($POST->password1!=$POST->password2)						{ $Result->show("danger", _("Passwords do not match"), true); }

		//enforce password policy
		$policy = (db_json_decode($User->settings->passwordPolicy, true));
		$Password_check->set_requirements  ($policy, pf_explode(",",$policy['allowedSymbols']));
		if (!$Password_check->validate ($POST->password1)) 				{ $Result->show("danger alert-danger ", _('Password validation errors').":<br> - ".implode("<br> - ", $Password_check->get_errors ()), true); }

		//hash passowrd
		$POST->password1 = $User->crypt_user_pass ($POST->password1);
	}

	# general checks
	if(is_blank($POST->real_name))										{ $Result->show("danger", _("Real name field is mandatory!"), true); }
	# email format must be valid
	if (!$Tools->validate_email($POST->email)) 						{ $Result->show("danger", _("Invalid email address!"), true); }

	# username must not already exist (if action is add)
	if ($POST->action=="add") {
		//username > 8 chars
		if ($auth_method->type=="local") {
			if(strlen($POST->username)<3)								{ $Result->show("danger", _("Username must be at least 3 characters long!"), true); }
		} else {
			if(is_blank($POST->username))								{ $Result->show("danger", _("Username must be at least 1 character long!"), true); }
		}
		//check duplicate
		if($Admin->fetch_object("users", "username", $POST->username)!==false) {
																			{ $Result->show("danger", _("User")." ".escape_input($POST->username)." "._("already exists!"), true); }
		}
	}
}

# admin user cannot be deleted
if($POST->action=="delete" && $POST->userId==1) 			{ $Result->show("danger", _("Admin user cannot be deleted"), true); }
# admin user cannot be disabled
if($POST->disabled=="Yes" && $POST->userId==1) 			{ $Result->show("danger", _("Admin user cannot be disabled"), true); }

# formulate update values
# nothing to do here for l10n, the content of the array goes into the database
$values = array(
				"id"             =>$POST->userId,
				"real_name"      =>$POST->real_name,
				"email"          =>$POST->email,
				"role"           =>$POST->role,
				"authMethod"     =>$POST->authMethod,
				"lang"           =>$POST->lang,
				"mailNotify"     =>$POST->mailNotify,
				"mailChangelog"  =>$POST->mailChangelog,
				"theme"          =>$POST->theme=="default" ? "" : $POST->theme,
				"disabled"       =>$POST->disabled=="Yes" ? "Yes" : "No"
				);


# username only on add
if($POST->action=="add") {
	$values['username'] = $POST->username;
}

# custom fields check
$update = $Tools->update_POST_custom_fields('users', $POST->action, $POST);
$values = array_merge($values, $update);

# update pass ?
if(!is_blank($POST->password1) || ($POST->action=="add" && $auth_method->type=="local")) {
	$values['password'] = $POST->password1;
}
# pass change
if(isset($POST->passChange) && $auth_method->type=="local") {
	$values['passChange'] = "Yes";
}
# set groups user belongs to
if($POST->role=="Administrator") {
	$values['groups'] = null;
} else {
	foreach($POST as $key=>$post) {
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
	if (isset($POST->{'perm_'.$m})) {
		if (is_numeric($POST->{'perm_'.$m})) {
			$permissions[$m] = $POST->{'perm_'.$m};
		}
	}
}
# formulate permissions
$values['module_permissions'] = json_encode($permissions);

# 2fa
if ($User->settings->{'2fa_provider'}!=='none') {
	if(!isset($POST->{'2fa'})) {
		$values['2fa']        = 0;
		$values['2fa_secret'] = null;
	}
}

# passkeys
$passkeys_to_remove = [];
foreach($POST as $key=>$post) {
	if(substr($key, 0,15) == "delete-passkey-") {
		$passkeys_to_remove[] = str_replace("delete-passkey-", "", $key);
	}
}

# passkey only
if ($User->settings->dbversion >= 40 && $User->settings->{'passkeys'}==1) {
	$values['passkey_only'] = !isset($POST->passkey_only) ? 0 : 1;
}

# execute
if(!$Admin->object_modify("users", $POST->action, "id", $values)) {
    $Result->show("danger", _("User")." ".$User->get_post_action()." "._("failed").'!', true);
}
else {
    $Result->show("success", _("User")." ".$User->get_post_action()." "._("successful").'!', false);
}

# remove passkeys if required
if (sizeof($passkeys_to_remove)>0) {
	// lalala
	foreach ($passkeys_to_remove as $pk) {
		$User->delete_passkey ($pk);
	}
}

# mail user
if($Admin->verify_checkbox($POST->notifyUser)!="0") { include("edit-notify.php"); }
