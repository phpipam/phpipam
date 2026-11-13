<?php

// fetch vlan
$vlan = $Tools->fetch_object('vlans', 'vlanId', $GET->subnetId);

// perm check
if ($User->get_module_permissions('vlan') == User::ACCESS_NONE) {
    $Result->warn('danger', _('You do not have permissions to access this module'));
}
// size check
elseif ($vlan === false) {
    print "<div class='subnetDetails'>";
    print '<h3>'._('Error').'</h3><hr>';
    $Result->warn('danger', _('Invalid VLAN id'));
    print '</div>';
} else {
    // print VLAN details
    print "<div class='subnetDetails'>";
    include_once __DIR__.'/vlan-details.php';
    print '</div>';

    // Subnets in VLAN
    print '<div class="ipaddresses_overlay">';
    include_once __DIR__.'/vlan-subnets.php';
    print '</div>';
}
