<?php

// process permissions
$permissions = db_json_decode($user['module_permissions'], true);
// loop
if (is_array($permissions)) {
    if (sizeof($permissions)>0) {
        foreach ($permissions as $module=>$perm) {
            $user['perm_'.$module] = $perm;
        }
    }
}

// admin fix
foreach ($User->get_modules_with_permissions() as $m) {
    if($user['role']=="Administrator") {
        $user['perm_'.$m] = 3;
    }
    else {
        if(!isset($user['perm_'.$m])) {
        $user['perm_'.$m] = 0;
        }
    }
}


// VLAN
$perm_names['perm_vlan'] = "VLAN";
// L2Domains
$perm_names['perm_l2dom'] = "L2 Domains";
// VRF
$perm_names['perm_vrf'] = "VRF";
// PDNS
if ($User->settings->enablePowerDNS==1)
$perm_names['perm_pdns'] = "PowerDNS";
// Devices
$perm_names['perm_devices'] = "Devices";
// Racks
if ($User->settings->enableRACK==1)
$perm_names['perm_racks'] = "Racks";
// Circuits
if ($User->settings->enableCircuits==1)
$perm_names['perm_circuits'] = "Circuits";
// NAT
if ($User->settings->enableNAT==1)
$perm_names['perm_nat'] = "NAT";
// Customers
if ($User->settings->enableCustomers==1)
$perm_names['perm_customers'] = "Customers";
// Locations
if ($User->settings->enableLocations==1)
$perm_names['perm_locations'] = "Locations";
// pstn
if ($User->settings->enablePSTN==1)
$perm_names['perm_pstn'] = "PSTN";
// routing
if ($User->settings->enableRouting==1)
$perm_names['perm_routing'] = "Routing";
// vaults
if ($User->settings->enableVaults==1)
$perm_names['perm_vaults'] = "Vaults";


// user page
if(($GET->page=="administration" && $GET->section=="users" && $GET->sPage=="modules") || ($GET->section=="user-menu")) {

    print '<div class="panel panel-default" style="max-width:600px;min-width:350px;">';
    print '<div class="panel-heading">'._("User permissions for phpipam modules").'</div>';
    print ' <ul class="list-group">';

    foreach ($user as $key=>$u) {
        if(strpos($key, "perm_")!==false && array_key_exists($key, $perm_names)) {
            print '<li class="list-group-item">';
            // title
            print "<span style='padding-top:8px;' class='pull-l1eft'>";
            print "<strong>"._($perm_names[$key])."</strong>";
            print "</span>";
            // perms
            print ' <strong class="btn-group pull-right">';
            print $User->print_permission_badge($user[$key]);
            print ' </strong>';
            print '</li>';

            print "<div class='clearfix'></div>";
        }
    }
    print ' </ul>';
    print '</div>';
}
else {
    print "<table class='table-noborder popover_table'>";
    foreach ($user as $key=>$u) {
        if(strpos($key, "perm_")!==false && array_key_exists($key, $perm_names)) {
            print "<tr><td>"._($perm_names[$key])."</td><td>".$User->print_permission_badge($user[$key])."</td></tr>";
        }
    }
    print "</table>";
}