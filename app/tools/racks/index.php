<?php

/**
 * Script to print racks
 ***************************/

# verify that user is logged in
$User->check_user_session();

# fetch custom fields
$custom = $Tools->fetch_custom_fields('racks');

# get hidden fields
$hidden_custom_fields = json_decode($User->settings->hiddenCustomFields, true);
$hidden_custom_fields = is_array(@$hidden_custom_fields['racks']) ? $hidden_custom_fields['racks'] : array();
# create csrf token
$csrf = $User->Crypto->csrf_cookie ("create", "rack_devices");

# all racks or one ?
if (isset($_GET['subnetId'])) {
	# map
	if($_GET['subnetId']=="map") { include("print-racks.php"); }
	else 						 { include("print-single-rack.php"); }
}
else                             { include("print-racks.php"); }