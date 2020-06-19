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

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "authmethods", $_POST['csrf_cookie']) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# get action
$action = $_POST['action'];

//for adding remove id
if($action=="add") {
	unset($_POST['id']);
}
else {
	//check id
	if(!is_numeric($_POST['id']))	{ $Result->show("danger", _("Invalid ID"), true); }
	//Get original values
	$method_settings = $Admin->fetch_object ("usersAuthMethod", "id", $_POST['id']);
	$original_params = json_decode($method_settings->params);
}

# set update query
$values = array(
				"id"          =>@$_POST['id'],
				"type"        =>$_POST['type'],
				"description" =>@$_POST['description'],
				);
# remove processed params
unset($_POST['id'], $_POST['type'], $_POST['description'], $_POST['action']);
$values["params"] = $_POST;

$secure_keys=array(
	'adminUsername',
	'adminUsername',
	'adminUsername',
	'idpx509privcert',
	'idpx509privkey',
	'idpx509pubcert'
);
# log values
$values_log = $values;
# mask secure keys
foreach($values_log["params"] as $key => &$value)
{
	if(in_array($key, $secure_keys))
	{
		$value = "********";
	}
}
$values_log["params"]=json_encode($values_log["params"]);

# replace masked values with original values
foreach ($values["params"] as $key => &$value)
{
	if($value == "********")
	{
		$value = $original_params->$key;
	}
}

# add params
$values["params"]=json_encode($values["params"]);

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