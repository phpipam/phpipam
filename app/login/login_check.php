<?php

/**
 *
 * Script to verify userentered input and verify it against database
 *
 * If successfull write values to session and go to main page!
 *
 */


/* functions */
require_once( dirname(__FILE__) . '/../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Result 	= new Result ();
$Log 		= new Logging ($Database);

# strip input tags form username only - password stip later because od LDAP
$_POST['ipamusername'] = $User->strip_input_tags ($_POST['ipamusername']);

# Authenticate
if( !empty($_POST['ipamusername']) && !empty($_POST['ipampassword']) )  {

	# initialize array
	$ipampassword = array();

	# check failed table
	$cnt = $User->block_check_ip ();

	# check for failed logins and captcha
	if($User->blocklimit > $cnt) {
		// all good
	}
	# count set, captcha required
	elseif(!isset($_POST['captcha'])) {
		$Log->write( "Login IP blocked", "Login from IP address ".$_SERVER['REMOTE_ADDR']." was blocked because of 5 minute block after 5 failed attempts", 1);
		$Result->show("danger", _('You have been blocked for 5 minutes due to authentication failures'), true);
	}
	# captcha check
	else {
		# check captcha
		if(strtolower($_POST['captcha'])!=strtolower($_SESSION['securimage_code_value']['default'])) {
			$Result->show("danger", _("Invalid security code"), true);
		}
	}

	# all good, try to authentucate user
	$User->authenticate ($_POST['ipamusername'], $_POST['ipampassword']);
}
# Username / pass not provided
else {
	$Result->show("danger", _('Please enter your username and password'), true);
}