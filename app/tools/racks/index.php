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

# verify module permissions
if($User->check_module_permissions ("racks", 1, false)===false) {
	print "<h4>"._("Racks")."</h4><hr>";
    $Result->show("danger", _("You do not have permissions to access this module"), false);
}
# all racks or one ?
elseif (isset($_GET['subnetId'])) {
	# map
	if($_GET['subnetId']=="map") { include("print-racks.php"); }
	else 						 { include("print-single-rack.php"); }
}
else                             { include("print-racks.php"); }