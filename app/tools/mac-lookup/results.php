<?php
# verify that user is logged in
$User->check_user_session();

// trim and escape
$mac = escape_input(trim($_POST['mac']));

// validate
if($User->validate_mac ($mac)===false) {
	$Result->show("warning", _("Invalid MAC address provided")." - ".$mac, false);
}
else {
	// check
	$mac_vendor = $User->get_mac_address_vendor_details ($mac);

	// print
	if($mac_vendor=="") {
		$Result->show("info", _("No matches found for prefix")." ".$mac, false);
	}
	else {
		$mac = strtoupper($User->reformat_mac_address ($mac, 1));
		$mac_partial = explode(":", $mac);
		// print
		print "<div style='font-size:16px;'>Vendor: <strong>".$mac_vendor."</strong></div><hr>";
		print "Prefix: ".$mac_partial[0].":".$mac_partial[1].":".$mac_partial[2]."<br>";
		print "MAC: ".$mac;
	}
}