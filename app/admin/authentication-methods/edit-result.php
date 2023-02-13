<?php

/**
 *	Edit authentication method
 *
 */

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin 		= new Admin ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();
# check maintaneance mode
$User->check_maintaneance_mode ();

# strip input tags
$_POST = $Admin->strip_input_tags($_POST);

# validate & remove csrf cookie
$User->Crypto->csrf_cookie ("validate", "authmethods", $_POST['csrf_cookie']) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";
unset($_POST['csrf_cookie']);

# get action
$action = $_POST['action'];

//for adding remove id
if($action=="add") {
	unset($_POST['id']);
}
else {
	//check id
	if(!is_numeric($_POST['id']))	{ $Result->show("danger", _("Invalid ID"), true); }
}

# set update query
$values = array(
				"id"          =>@$_POST['id'],
				"type"        =>$_POST['type'],
				"description" =>@$_POST['description'],
				);

# Validate input
if (isset($_POST['type']) && $_POST['type']=="SAML2") {
	# The JIT & SAML mapped user options are mutually exclusive.
	if (filter_var($_POST['jit'], FILTER_VALIDATE_BOOLEAN) && !empty($_POST['MappedUser'])) {
		$Result->show("danger",  _("The JIT and mapped user options are mutually exclusive"), true);
	}

	# Validate Prettify links is enabled for strict mode.
	if (filter_var($_POST['strict'], FILTER_VALIDATE_BOOLEAN) && !filter_var($User->settings->prettyLinks, FILTER_VALIDATE_BOOLEAN)) {
		$Result->show("danger",  _("Strict mode requires global setting \"Prettify links\"=yes"), true);
	}

	# Verify that the private certificate and key are provided if Signing Authn Requests is set
	if($action!="delete" && filter_var(['spsignauthn'], FILTER_VALIDATE_BOOLEAN) && (empty($_POST['spx509cert']) || empty($_POST['spx509key']))) {
		$Result->show("danger",  _("SP (Client) certificate and key are required to sign Authn requests"), true);
	}
}

# remove processed params
unset($_POST['id'], $_POST['type'], $_POST['description'], $_POST['action']);
$values["params"]=json_encode($_POST);

$secure_keys=[
	'adminUsername',
	'adminPassword',
	'secret',
	'idpx509cert',
	'spx509cert',
	'spx509key'
	];
# log values
$values_log = $values;
# mask secure keys
foreach($_POST as $key => $value) {
	if(in_array($key, $secure_keys)){ $_POST[$key] = "********"; }
}
$values_log["params"]=json_encode($_POST);

# add - set protected
if($action=="add") {
	$values['protected'] = "No";
}

# update
if(!$Admin->object_modify("usersAuthMethod", $action, "id", $values, $values_log))	{ $Result->show("danger",  _("Failed to edit authentication method"), false); }
else																				{ $Result->show("success", _("Authentication method updated"), false); }

# if delete also reset all users that have thos auth method
if($action=="delete") {
	$Database->runQuery("update `users` set `authMethod`=1 where `authMethod`= ?;", array($values['id']));
}