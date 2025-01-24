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


function die_with_error($msg) {
	die(_("Error").": ".$msg);
}
function php_error_handler($errno, $errstr){
	die(_("Error").": ".$errstr);
	return true;
}

# make sure user has access
if ($User->get_module_permissions("vaults")<User::ACCESS_RWA) {
	die_with_error(_("Insufficient privileges"));
}

// check posted url
if (!filter_var($POST->website, FILTER_VALIDATE_URL)) {
	die_with_error(_("Invalid URL"));
}

// replace https
$website = str_replace(["https://", "http://"], "ssl://", $POST->website);
if (!preg_match('/:\d+$/',$website)) {
	$website.= ":443";
}

// create context
$options = [
	"ssl"=>['capture_peer_cert_chain' => true,
			'verify_peer'=>filter_var($POST->verify_peer, FILTER_VALIDATE_BOOLEAN)
			]
];
$g = stream_context_create ($options);

// stream_socket_client may create PHP WARNINGS before socket is created and $errstr is set
set_error_handler("php_error_handler");
$r = stream_socket_client($website, $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $g);
restore_error_handler();

// check
if($r===false && is_blank($errstr)) {
	die_with_error(_("Unable to establish socket connection"));
}
elseif (!is_blank($errstr)) {
	die_with_error($errstr);
}
else {
	// get
	$cont = stream_context_get_params($r);

	// export
	$chain = $cont["options"]["ssl"]["peer_certificate_chain"];
	if (!is_array($chain)) {
		die_with_error(_("Could not fetch certificate chain"));
	}

	$certchain = "";
	foreach($chain as $c) {
		if(openssl_x509_export($c,$certinfo)===false) {
			die_with_error(_("Could not fetch certificate chain"));
		}
		$certchain .= $certinfo;
	}

	// print
	print base64_encode($certchain);
}