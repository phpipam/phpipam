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
# check if site is demo
$User->is_demo();

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "settings", $POST->csrf_cookie) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

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
	if (isset($POST->{$k})) {
		if (is_blank($POST->{$k})) {
			$passwordPolicy[$k] = 0;
		}
		elseif (!is_numeric($POST->{$k})) {
			$Result->show ("danger", _("Values must be numeric"), true);
		}
		else {
			$passwordPolicy[$k] = $POST->{$k};
		}
	}
}
# symbols
if (!is_blank($POST->allowedSymbols)) {
	$POST->allowedSymbols = str_replace(" ", "", $POST->allowedSymbols);
	$passwordPolicy['allowedSymbols'] = $POST->allowedSymbols;
}

# set update values
$values = array("id"=>1, "passwordPolicy"=>json_encode($passwordPolicy));

if(!$Admin->object_modify("settings", "edit", "id", $values))	{ $Result->show("danger",  _("Cannot update settings"), true); }
else															{ $Result->show("success", _("Settings updated successfully"), false); }

# if required check all user sertings and force them to update password
if($POST->enforce==1) {
	try { $Database->runQuery("update `users` set `passChange` = 'Yes' where `authMethod` = 1;"); }
	catch (Exception $e) {
		$Result->show("danger", _('Error updating users: ').$e->getMessage(), false);
	}
}
