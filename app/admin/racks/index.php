<?php

/**
 * Script to print racks
 ***************************/

# verify that user is logged in
$User->check_user_session();

# fetch custom fields
$custom = $Tools->fetch_custom_fields('racks');

# get hidden fields
$hidden_custom_fields = db_json_decode($User->settings->hiddenCustomFields, true);
$hidden_custom_fields = is_array(@$hidden_custom_fields['racks']) ? $hidden_custom_fields['racks'] : array();

# perm check
if ($User->get_module_permissions ("racks")==User::ACCESS_NONE) {
	$Result->show("danger", _("You do not have permissions to access this module"), false);
}
elseif (isset($GET->subnetId))   { include("print-single-rack.php"); }
else                            { include("print-racks.php"); }