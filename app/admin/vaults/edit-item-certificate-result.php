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
// cert check
if($_POST['action']=="add") {
	// print_r(base64_decode($_POST['certificate']));
	// if(openssl_x509_parse(base64_decode($_POST['certificate']))===false) { $Result->show("danger", _("Cannot parse certificate"), true); }
}
// for edits without certificate get old value!
elseif($_POST['action']=="edit") {
	if(openssl_x509_parse(base64_decode($_POST['certificate']))===false) {
		$vault_item = $Tools->fetch_object("vaultItems", "id", $_POST['id']);
		$vault_item_values = pf_json_decode($User->Crypto->decrypt($vault_item->values, $_SESSION['vault'.$_POST['vaultId']]));
		$_POST['certificate'] = $vault_item_values->certificate;
	}
}

# add, edit
if($_POST['action']!="delete") {
	# name must be more than 2 and alphanumberic
	if(strlen($_POST['name'])<3 || strlen($_POST['name'])>64)			{ $error[] = "Invalid name"; }
}

# custom

# die if errors
if(sizeof($error) > 0) {
	$Result->show("danger", $error, true);
}
else {
	# values to encrypt
	$values_array = [
					"name"        => $_POST['name'],
					"description" => $_POST['description'],
					"certificate" => $_POST['certificate']
					];

	# remove certificate if not present
	if($_POST['action']!="delete" && is_blank($_POST['certificate'])) {
		$Result->show("danger", _("Invalid certificate"), true);
	}

	// print "<pre>";
	// print_r(base64_decode($_POST['certificate']));
	// die('alert-danger');

	# create array of values for modification
	$values = array(
					"id"     => @$_POST['id'],
					"values" => $User->Crypto->encrypt(json_encode($values_array), $_SESSION['vault'.$vault->id])
					);

	# append custom
	if(sizeof($custom) > 0) {
		foreach($custom as $myField) {
			# replace possible ___ back to spaces!
			$myField['nameTest']      = str_replace(" ", "___", $myField['name']);
			if(isset($_POST[$myField['nameTest']])) { $values[$myField['name']] = @$_POST[$myField['nameTest']];}
		}
	}

	# add
	if($_POST['action']=="add") {
		$values['type']             = "certificate";
		$values['type_certificate'] = $_POST['type'];
		$values['vaultId']          = $vault->id;
	}

	# execute
	if(!$Admin->object_modify("vaultItems", $_POST['action'], "id", $values)) 	{ $Result->show("danger",  _("Vault item")." $_POST[action] "._("error"), true); }
	else 																		{ $Result->show("success", _("Vault item")." $_POST[action] "._("success"), true); }
}
