<?php

/**
 * Script to print locations
 ***************************/

# verify that user is logged in
$User->check_user_session();

# fetch custom fields
$custom = $Tools->fetch_custom_fields('pstnPrefixes');

# get hidden fields
$hidden_custom_fields = db_json_decode($User->settings->hiddenCustomFields, true);
$hidden_custom_fields = is_array(@$hidden_custom_fields['pstnPrefixes']) ? $hidden_custom_fields['pstnPrefixes'] : array();

# perm check
if ($User->get_module_permissions ("pstn")==User::ACCESS_NONE) {
	$Result->show("danger", _("You do not have permissions to access this module"), false);
}
# check that prefix support isenabled
elseif ($User->settings->enablePSTN != "1") {
    $Result->show("danger", _("PSTN prefixes module disabled."), false);
}
else {
    # all prefixes
    if (!isset($GET->subnetId)) {
        include("all-prefixes.php");
    } else { # single prefixes
        $isMaster = $Tools->count_database_objects("pstnPrefixes", "master", $GET->subnetId) != 0;
        include("single-prefix.php");
    }
}