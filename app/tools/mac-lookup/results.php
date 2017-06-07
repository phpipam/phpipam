<?php
# verify that user is logged in
$User->check_user_session();

// trim
$_GET['mac'] = trim($_GET['mac']);

// validate
if($User->validate_mac ($_GET['mac'])===false) {
	$Result->show("warning", _("Invalid MAC address provided")." - ".$_GET['mac'], false);
}
else {
	// check
	$mac_vendor = $User->get_mac_address_vendor_details (trim($_GET['mac']));

	// print
	if($mac_vendor=="") {
		$Result->show("info", _("No matches found for prefix")." ".$_GET['mac'], false);
	}
	else {
		$mac = strtoupper($User->reformat_mac_address ($_GET['mac'], $format = 1));
		$mac_partial = explode(":", $mac);
		// print
		print "<div style='font-size:16px;'>Vendor: <strong>".$mac_vendor."</strong></div><hr>";
		print "Prefix: ".$mac_partial[0].":".$mac_partial[1].":".$mac_partial[2]."<br>";
		print "MAC: ".$_GET['mac'];
	}
}