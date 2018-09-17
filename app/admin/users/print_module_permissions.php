<?php
require_once( dirname(__FILE__) . "/get_badge.php" );

// admin fix
if($user['role']=="Administrator") {
    $user['perm_vlan']      = 3;
    $user['perm_vrf']       = 3;
    $user['perm_racks']     = 3;
    $user['perm_pdns']      = 3;
    $user['perm_pstn']      = 3;
    $user['perm_circuits']  = 3;
    $user['perm_customers'] = 3;
    $user['perm_nat']       = 3;
}

print "<table class='table-noborder popover_table'>";


// VLAN
print "<tr><td>"._("VLAN")."</td><td>".get_badge($user['perm_vlan'])."</td></tr>";

// VRF
print "<tr><td>"._("VRF")."</td><td>".get_badge($user['perm_vrf'])."</td></tr>";

// Racks
if ($User->settings->enableRACK==1)
print "<tr><td>"._("Racks")."</td><td>".get_badge($user['perm_racks'])."</td></tr>";

// Circuits
if ($User->settings->enableCircuits==1)
print "<tr><td>"._("Circuits")."</td><td>".get_badge($user['perm_circuits'])."</td></tr>";

// PDNS
if ($User->settings->enablePowerDNS==1)
print "<tr><td>"._("PowerDNS")."</td><td>".get_badge($user['perm_pdns'])."</td></tr>";

// NAT
if ($User->settings->enableNAT==1)
print "<tr><td>"._("NAT")."</td><td>".get_badge($user['perm_nat'])."</td></tr>";

// Customers
if ($User->settings->enableCustomers==1)
print "<tr><td>"._("Customers")."</td><td>".get_badge($user['perm_customers'])."</td></tr>";

// pstn
if ($User->settings->enablePSTN==1)
print "<tr><td>"._("PSTN")."</td><td>".get_badge($user['perm_pstn'])."</td></tr>";

print "</table>";
