<?php

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session(true, true);

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "2fa_validation", $_POST['csrf_cookie']) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# if 2fa is not needed redirect to /
if ($User->twofa_required()===false) {
	header("Location:".$url.create_link (null));
}
# length check
elseif (strlen($_POST['code'])!==6) {
	$Result->show ("danger", _("Invalid code length"));
}
# generate and print code
else {
	# init class
	require_once (dirname(__FILE__)."/../../../functions/GoogleAuthenticator/PHPGangsta/GoogleAuthenticator.php");
	$ga = new PHPGangsta_GoogleAuthenticator();
	# validate
	if ($ga->verifyCode($User->user->{'2fa_secret'}, $_POST['code'], 2)) {
		$Result->show ("success", _("Code validated. Redirecting..."));
		// remove 2fa flag from session
		unset ($_SESSION['2fa_required']);
	}
	else {
		$Result->show ("danger", _("Invalid code"));
	}
}