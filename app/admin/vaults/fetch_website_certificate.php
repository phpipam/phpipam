<?php

/**
 * Script to fetch website certificate
 *************************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database       = new Database_PDO;
$User           = new User ($Database);
$Admin          = new Admin ($Database, false);
$Result         = new Result ();

# verify that user is logged in
$User->check_user_session();

# make sure user has access
if ($User->get_module_permissions ("vaults")<User::ACCESS_RW) { die("Insufficient privileges."); }

# strip input tags
$_POST = $Admin->strip_input_tags($_POST);


# check perms
if ($User->get_module_permissions ("vaults")>=User::ACCESS_RWA) {

	// check posted url
	if (!filter_var($_POST['website'], FILTER_VALIDATE_URL)) {
		die("Error: Invalid URL");
	}

	// replace https
	$website = str_replace(["https://", "http://"], "ssl://", $_POST['website']);
	$website.= ":443";

	// create context
	$g = stream_context_create (array("ssl" => array("capture_peer_cert" => true)));
	// open socket
	$r = stream_socket_client($website, $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $g);

	// check
	if(strlen($errstr)>0) {
		die("Error: ".$errstr);
	}
	else {
		// get
		$cont = stream_context_get_params($r);
		// export
		if(openssl_x509_export($cont["options"]["ssl"]["peer_certificate"],$certinfo)===false) {
			die("Error fetching certificate.");
		}
		else {
			// print
			print base64_encode($certinfo);
		}
	}
}
else {
	print "Not permitted";
}