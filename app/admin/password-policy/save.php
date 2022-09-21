<?php

/**
 *	Site settings
 **************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "settings", $_POST['csrf_cookie']) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# valid params
$passwordPolicy = [
	'minLength'    => 0,
	'maxLength'    => 0,
	'minNumbers'   => 0,
	'minLetters'   => 0,
	'minLowerCase' => 0,
	'minUpperCase' => 0,
	'minSymbols'   => 0,
	'maxSymbols'   => 0
];

# check for numbers and set parameters
foreach ($passwordPolicy as $k=>$f) {
	if (isset($_POST[$k])) {
		if (strlen($_POST[$k])==0) {
			$passwordPolicy[$k] = 0;
		}
		elseif (!is_numeric($_POST[$k])) {
			$Result->show ("danger", _("Values must be numeric"), true);
		}
		else {
			$passwordPolicy[$k] = $_POST[$k];
		}
	}
}
# symbols
if (strlen($_POST['allowedSymbols'])>0) {
	$_POST['passwordPolicy'] = str_replace(" ", "", $_POST['passwordPolicy']);
	$passwordPolicy['allowedSymbols'] = $_POST['allowedSymbols'];
}

# set update values
$values = array("id"=>1, "passwordPolicy"=>json_encode($passwordPolicy));

if(!$Admin->object_modify("settings", "edit", "id", $values))	{ $Result->show("danger",  _("Cannot update settings"), true); }
else															{ $Result->show("success", _("Settings updated successfully"), false); }

# if required check all user sertings and force them to update password
if(@$_POST['enforce']==1) {
	try { $Database->runQuery("update `users` set `passChange` = 'Yes' where `authMethod` = 1;"); }
	catch (Exception $e) {
		$Result->show("danger", _('Error updating users: ').$e->getMessage(), false);
	}
}
