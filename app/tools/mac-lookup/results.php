<?php
if (!isset($User)) {
	require_once(dirname(__FILE__) . '/../../../functions/functions.php');
	# classes
	$Database	= new Database_PDO;
	$User 		= new User ($Database);
	$Result     = new Result();
}

# verify that user is logged in
$User->check_user_session();

// trim and escape
$mac = isset($POST->mac) ? escape_input(trim($POST->mac)) : "";

// validate
if ($User->validate_mac($mac) === false) {
	$Result->show("warning", _("Invalid MAC address provided") . " - " . $mac, false);
} else {
	// check
	$mac_vendor = $User->get_mac_address_vendor_details($mac, $prefix);

	// print
	if ($mac_vendor == "") {
		$Result->show("info", _("No matches found for prefix") . " " . $mac, false);
	} else {
		$mac = strtoupper($User->reformat_mac_address($mac, 1));

		// print
		print "<div style='font-size:16px;'>Vendor: <strong class='clipboard'>" . escape_input($mac_vendor) . "</strong></div><hr>";
		print "Prefix: " . escape_input($prefix) . "<br>";
		print "MAC: " . $mac;
	}
}
