<?php

/**
 * Script to display vault edit result
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

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "vaultitem", $POST->csrf_cookie) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# fetch vault details
$vault = $Admin->fetch_object ("vaults", "id", $POST->vaultId);
# null ?
$vault===false ? $Result->show("danger", _("Invalid ID"), true) : null;

# fetch custom fields
$custom = $Tools->fetch_custom_fields('vaultItems');

/* checks */
$error = array();
// cert check
if($POST->action=="add") {
	// print_r(base64_decode($POST->certificate));
	// if(openssl_x509_parse(base64_decode($POST->certificate))===false) { $Result->show("danger", _("Cannot parse certificate"), true); }
}
// for edits without certificate get old value!
elseif($POST->action=="edit") {
	if(openssl_x509_parse(base64_decode($POST->certificate))===false) {
		$vault_item = $Tools->fetch_object("vaultItems", "id", $POST->id);
		$vault_item_values = db_json_decode($User->Crypto->decrypt($vault_item->values, $_SESSION['vault'.$POST->vaultId]));
		$POST->certificate = $vault_item_values->certificate;
	}
}

# add, edit
if($POST->action!="delete") {
	# name must be more than 2 and alphanumeric
	if(strlen($POST->name)<3 || strlen($POST->name)>64)			{ $error[] = "Invalid name"; }
}

# custom

# die if errors
if(sizeof($error) > 0) {
	$Result->show("danger", $error, true);
}
else {
	# values to encrypt
	$values_array = [
					"name"        => $POST->name,
					"description" => $POST->description,
					"certificate" => $POST->certificate
					];

	# remove certificate if not present
	if($POST->action!="delete" && is_blank($POST->certificate)) {
		$Result->show("danger", _("Invalid certificate"), true);
	}

	// print "<pre>";
	// print_r(base64_decode($POST->certificate));
	// die('alert-danger');

	# create array of values for modification
	$values = array(
					"id"     => $POST->id,
					"values" => $User->Crypto->encrypt(json_encode($values_array), $_SESSION['vault'.$vault->id])
					);

	# append custom
	if(sizeof($custom) > 0) {
		foreach($custom as $myField) {
			# replace possible ___ back to spaces!
			$myField['nameTest']      = str_replace(" ", "___", $myField['name']);
			if(isset($POST->{$myField['nameTest']})) { $values[$myField['name']] = $POST->{$myField['nameTest']};}
		}
	}

	# add
	if($POST->action=="add") {
		$values['type']             = "certificate";
		$values['type_certificate'] = $POST->type;
		$values['vaultId']          = $vault->id;
	}

	# execute
	if(!$Admin->object_modify("vaultItems", $POST->action, "id", $values)) 	{ $Result->show("danger",  _("Vault item")." ".$POST->action." "._("error"), true); }
	else 																		{ $Result->show("success", _("Vault item")." ".$POST->action." "._("success"), true); }
}
