<?php

/**
 * Script to print locations
 ***************************/

# verify that user is logged in
$User->check_user_session();

# set admin
$admin = $User->is_admin(false);

# fetch custom fields
$custom = $Tools->fetch_custom_fields('pstnPrefixes');

# get hidden fields
$hidden_custom_fields = json_decode($User->settings->hiddenCustomFields, true);
$hidden_custom_fields = is_array(@$hidden_custom_fields['pstnPrefixes']) ? $hidden_custom_fields['pstnPrefixes'] : array();

# check that prefix support isenabled
if ($User->settings->enablePSTN!="1") {
    $Result->show("danger", _("PSTN prefixes module disabled."), false);
}
else {
    # all prefixes
    if(!isset($_GET['subnetId'])) {
        include("all-prefixes.php");
    }
    # single prefixes
    else {
        # slaves ?
        $cnt = $Tools->count_database_objects("pstnPrefixes", "master",$_GET['subnetId']);
        if ($cnt == 0) {
            include("single-prefix.php");
        }
        else {
            include("single-prefix-slaves.php");
        }
    }
}
?>