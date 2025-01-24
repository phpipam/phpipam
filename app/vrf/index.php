<?php

die();

# get VRF details
$vrf = $Tools->fetch_object ("vrf", "vrfId", $GET->section);

# perm check
if ($User->get_module_permissions ("vrf")==User::ACCESS_NONE) {
	$Result->show("danger", _("You do not have permissions to access this module"), false);
}
elseif ($vrf===false) {
	print "<div class='subnetDetails'>";
	print "<h3>"._("Error")."</h3><hr>";
	$Result->show("danger", _("Invalid VRF id"), false);
	print "</div>";
}
else {
	# print VRF details
	print "<div class='subnetDetails'>";
	include_once("vrf-details.php");
	print "</div>";

	# Subnets in VRF
	print '<div class="ipaddresses_overlay">';
	include_once('vrf-subnets.php');
	print '</div>';
}