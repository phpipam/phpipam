<?php

# fetch vlan
$vlan = $Tools->fetch_object("vlans", "vlanId", $GET->subnetId);

# perm check
if ($User->get_module_permissions ("vlan")==User::ACCESS_NONE) {
	$Result->show("danger", _("You do not have permissions to access this module"), false);
}
# size check
elseif($vlan===false) {
	print "<div class='subnetDetails'>";
	print "<h3>"._("Error")."</h3><hr>";
	$Result->show("danger", _("Invalid VLAN id"), false);
	print "</div>";
}
else {
	# print VLAN details
	print "<div class='subnetDetails'>";
	include_once("vlan-details.php");
	print "</div>";

	# Subnets in VLAN
	print '<div class="ipaddresses_overlay">';
	include_once('vlan-subnets.php');
	print '</div>';
}