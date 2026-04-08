<?php

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Result 	= new Result ();
$Log 		= new Logging ($Database);

# verify that user is logged in
$User->check_user_session(true, true);

# validate csrf cookie
if ($User->Crypto->csrf_cookie("validate", "2fa_validation", $POST->csrf_cookie) === false) {
	if ($POST->show_error == "true") {
		$Result->show("danger", _("Invalid CSRF cookie"), true);
	}
}

# if 2fa is not needed redirect to /
if ($User->twofa_required()===false) {
	header("Location:".$url.create_link (null));
}
# length check
elseif (strlen($POST->code)!==6) {
	$Result->show ("danger", _("Invalid code length"));
}
# generate and print code
else {

	# check failed table
	$cnt = $User->block_check_ip ();


	# check for failed logins and captcha
	if($User->blocklimit > $cnt) {
		# init class
		require_once (dirname(__FILE__)."/../../../functions/GoogleAuthenticator/PHPGangsta/GoogleAuthenticator.php");
		$ga = new PHPGangsta_GoogleAuthenticator();
		# validate
		if ($ga->verifyCode($User->user->{'2fa_secret'}, $POST->code, 2)) {
			$Result->show ("success", _("Code validated. Redirecting..."));
			// remove 2fa flag from session
			unset ($_SESSION['2fa_required']);
		}
		else {
			if ($POST->show_error=="true") {
				$Result->show ("danger", _("Invalid code"));
			}
			// update block count
			$User->block_ip ();
		}
	}
	else {
		$Log->write( _("Login IP blocked"), _("Login from IP address")." ".$_SERVER['REMOTE_ADDR']." "._("was blocked because of 5 minute block after 5 failed 2fa attempts"), 1);
		$Result->show("danger", _('You have been blocked for 5 minutes due to 2fa authentication failures'), true);
	}
}