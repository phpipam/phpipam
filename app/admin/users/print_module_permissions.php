<?php

// process permissions
$permissions = json_decode($user['module_permissions'], true);
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

print "<table class='table-noborder popover_table'>";


// VLAN
print "<tr><td>"._("VLAN")."</td><td>".$User->print_permission_badge($user['perm_vlan'])."</td></tr>";

// L2Domains
print "<tr><td>"._("L2Domains")."</td><td>".$User->print_permission_badge($user['perm_l2dom'])."</td></tr>";

// VRF
print "<tr><td>"._("VRF")."</td><td>".$User->print_permission_badge($user['perm_vrf'])."</td></tr>";

// PDNS
if ($User->settings->enablePowerDNS==1)
print "<tr><td>"._("PowerDNS")."</td><td>".$User->print_permission_badge($user['perm_pdns'])."</td></tr>";

// Devices
print "<tr><td>"._("Devices")."</td><td>".$User->print_permission_badge($user['perm_devices'])."</td></tr>";

// Racks
if ($User->settings->enableRACK==1)
print "<tr><td>"._("Racks")."</td><td>".$User->print_permission_badge($user['perm_racks'])."</td></tr>";

// Circuits
if ($User->settings->enableCircuits==1)
print "<tr><td>"._("Circuits")."</td><td>".$User->print_permission_badge($user['perm_circuits'])."</td></tr>";

// NAT
if ($User->settings->enableNAT==1)
print "<tr><td>"._("NAT")."</td><td>".$User->print_permission_badge($user['perm_nat'])."</td></tr>";

// Customers
if ($User->settings->enableCustomers==1)
print "<tr><td>"._("Customers")."</td><td>".$User->print_permission_badge($user['perm_customers'])."</td></tr>";

// Locations
if ($User->settings->enableLocations==1)
print "<tr><td>"._("Locations")."</td><td>".$User->print_permission_badge($user['perm_locations'])."</td></tr>";

// pstn
if ($User->settings->enablePSTN==1)
print "<tr><td>"._("PSTN")."</td><td>".$User->print_permission_badge($user['perm_pstn'])."</td></tr>";

// routing
if ($User->settings->enableRouting==1)
print "<tr><td>"._("Routing")."</td><td>".$User->print_permission_badge($user['perm_routing'])."</td></tr>";

// vaults
if ($User->settings->enableVaults==1)
print "<tr><td>"._("Vaults")."</td><td>".$User->print_permission_badge($user['perm_vaults'])."</td></tr>";

print "</table>";