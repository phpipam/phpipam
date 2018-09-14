<?php

print "<table class='table-noborder popover_table'>";
// pdns
if ($User->settings->enablePowerDNS==1) {
    if(strlen($user['pdns'])==0) $user['pdns'] = "No";
    $user['pdns'] = $user['pdns']=="No" ? "<span class='badge badge1 badge5 alert-danger'>"._($user['pdns'])."</span>" : "<span class='badge badge1 badge5 alert-success'>"._($user['pdns'])."</span>";
    print "<tr><td>"._("PowerDNS")."</td><td>".$user['pdns']."</td></tr>";
}

// vlan / VRF
if(strlen($user['editVlan'])==0) $user['editVlan'] = "No";
$user['editVlan'] = $user['editVlan']=="No" ? "<span class='badge badge1 badge5 alert-danger'>"._($user['editVlan'])."</span>" : "<span class='badge badge1 badge5 alert-success'>"._($user['editVlan'])."</span>";
print "<tr><td>"._("VLANs / VRFs")."</td><td>".$user['editVlan']."</td></tr>";

// pstn
if ($User->settings->enablePSTN==1) {
    $user['perm_pstn'] = $user['perm_pstn']=="0" ? "<span class='badge badge1 badge5 alert-danger'>"._("No")."</span>" : "<span class='badge badge1 badge5 alert-success'>"._($Subnets->parse_permissions ($user['perm_pstn']))."</span>";
    print "<tr><td>"._("PSTN")."</td><td>".$user['perm_pstn']."</td></tr>";
}

// Circuits
if ($User->settings->enableCircuits==1) {
    $user['editCircuits'] = $user['editCircuits']=="No"||is_null($user['editCircuits']) ? "<span class='badge badge1 badge5 alert-danger'>"._("No")."</span>" : "<span class='badge badge1 badge5 alert-success'>"._($user['editCircuits'])."</span>";
    print "<tr><td>"._("Circuits")."</td><td>".$user['editCircuits']."</td></tr>";
}

// Customers
if ($User->settings->enableCustomers==1) {
    $user['perm_customers'] = $user['perm_customers']=="0" ? "<span class='badge badge1 badge5 alert-danger'>"._($user['perm_customers'])."</span>" : "<span class='badge badge1 badge5 alert-success'>"._($Subnets->parse_permissions ($user['perm_customers']))."</span>";
    print "<tr><td>"._("Customers")."</td><td>".$user['perm_customers']."</td></tr>";
}

// Racks
if ($User->settings->enableRACK==1) {
    $user['perm_racks'] = $user['perm_racks']=="0" ? "<span class='badge badge1 badge5 alert-danger'>"._($user['perm_racks'])."</span>" : "<span class='badge badge1 badge5 alert-success'>"._($Subnets->parse_permissions ($user['perm_racks']))."</span>";
    print "<tr><td>"._("Racks")."</td><td>".$user['perm_racks']."</td></tr>";
}

// NAT
if ($User->settings->enableNAT==1) {
    $user['perm_nat'] = $user['perm_nat']=="0" ? "<span class='badge badge1 badge5 alert-danger'>"._($user['perm_nat'])."</span>" : "<span class='badge badge1 badge5 alert-success'>"._($Subnets->parse_permissions ($user['perm_nat']))."</span>";
    print "<tr><td>"._("NAT")."</td><td>".$user['perm_nat']."</td></tr>";
}

print "</table>";