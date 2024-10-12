<?php

/**
 * Script to print BGP
 ***************************/

# verify that user is logged in
$User->check_user_session();


# fetch custom fields
$custom_bgp = $Tools->fetch_custom_fields('routing_bgp');
$hidden_custom_fields_bgp = db_json_decode($User->settings->hiddenCustomFields, true);
$hidden_custom_fields_bgp = is_array(@$hidden_custom_fields['routing_bgp']) ? $hidden_custom_fields['routing_bgp'] : array();

// $custom_ospf = $Tools->fetch_custom_fields('routing_ospf');
// $hidden_custom_fields_ospf = db_json_decode($User->settings->hiddenCustomFields, true);
// $hidden_custom_fields_ospf = is_array(@$hidden_custom_fields['routing_ospf']) ? $hidden_custom_fields['routing_ospf'] : array();


# title
print "<h4>"._('Routing information')."</h4><hr>";

# perm check
if ($User->get_module_permissions ("routing")==User::ACCESS_NONE) {
    $Result->show("danger", _("You do not have permissions to access this module"), false);
}
# check that location support isenabled
elseif ($User->settings->enableRouting!="1") {
    $Result->show("danger", _("Routing module disabled."), false);
}
else {
    # specific entry details
    if (isset($GET->sPage)) {
        # menu
        include(dirname(__FILE__)."/menu.php");
        # include
        if($GET->subnetId=="bgp")      { include(dirname(__FILE__)."/bgp/details.php"); }
        elseif($GET->subnetId=="ospf") { include(dirname(__FILE__)."/ospf/details.php"); }
        else                              { $Result->show("danger", _("Invalid routing module."), false); }
    }
    # all entries
    else {
        # default
        if (!isset($GET->subnetId))    { $GET->subnetId = "bgp"; }
        # menu
        include(dirname(__FILE__)."/menu.php");
        # include
        if($GET->subnetId=="bgp")      { include(dirname(__FILE__)."/bgp/all.php"); }
        elseif($GET->subnetId=="ospf") { include(dirname(__FILE__)."/ospf/all.php"); }
        else                              { $Result->show("danger", _("Invalid routing module."), false); }
    }
}