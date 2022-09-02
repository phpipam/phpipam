<?php

/**
 * Script to disaply vault edit result
 *************************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database       = new Database_PDO;
$User           = new User ($Database);
$Admin          = new Admin ($Database, false);
$Tools          = new Tools ($Database);
$Result         = new Result ();
$Password_check = new Password_check ();

# verify that user is logged in
$User->check_user_session();
# check maintaneance mode
$User->check_maintaneance_mode ();

# make sure user has access
if ($User->get_module_permissions ("vaults")<User::ACCESS_RW) { $Result->show("danger", _("Insufficient privileges").".", true); }

# strip input tags
$_POST = $Admin->strip_input_tags($_POST);

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "vaultitem", $_POST['csrf_cookie']) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# fetch vault details
$vault = $Admin->fetch_object ("vaults", "id", $_POST['vaultId']);
# null ?
$vault===false ? $Result->show("danger", _("Invalid ID"), true) : null;

# fetch custom fields
$custom = $Tools->fetch_custom_fields('vaultItems');

/* checks */
$error = array();

# add, edit
if($_POST['action']!="delete") {
	# name must be more than 2 and alphanumberic
	if(strlen($_POST['name'])<3 || strlen($_POST['name'])>64)			{ $error[] = "Invalid name"; }
	if(strlen($_POST['username'])<3 || strlen($_POST['username'])>64)	{ $error[] = "Invalid username"; }
	if(strlen($_POST['password'])<3 || strlen($_POST['password'])>64)	{ $error[] = "Invalid password"; }
}


# die if errors
if(sizeof($error) > 0) {
	$Result->show("danger", $error, true);
}
else {

	# compose values to be encrypted
	$values_array = [
					"name"        => $_POST['name'],
					"username"    => $_POST['username'],
					"password"    => $_POST['password'],
					"description" => $_POST['description']
					];


	# create array of values for modification
	$values = array(
					"id"     => @$_POST['id'],
					"values" => $User->Crypto->encrypt(json_encode($values_array), $_SESSION['vault'.$vault->id])
					);

	# add test
	if($_POST['action']=="add") {
		$values['type'] 	= "password";
		$values['vaultId']  = $vault->id;
	}

	# append custom
	if(sizeof($custom) > 0) {
		foreach($custom as $myField) {
			# replace possible ___ back to spaces!
			$myField['nameTest']      = str_replace(" ", "___", $myField['name']);
			if(isset($_POST[$myField['nameTest']])) { $values[$myField['name']] = @$_POST[$myField['nameTest']];}
		}
	}

	# execute
	if(!$Admin->object_modify("vaultItems", $_POST['action'], "id", $values)) 	{ $Result->show("danger",  _("Vault item")." $_POST[action] "._("error"), true); }
	else 																		{ $Result->show("success", _("Vault item")." $_POST[action] "._("success"), true); }
}
