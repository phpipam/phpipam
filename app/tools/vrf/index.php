<?php

/**
 * Script to display all VRFs
 *
 */

# verify that user is logged in
$User->check_user_session();

# perm check
if ($User->get_module_permissions ('vrf')==User::ACCESS_NONE) {
	$Result->show("danger", _("You do not have permissions to access this module"), false);
}
# display single VRF or all ?
elseif(is_numeric($GET->subnetId)) {
	# get VRF details
	$vrf = $Tools->fetch_object ("vrf", "vrfId", $GET->subnetId);

	if ($vrf===false) {
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
}
else {
	include ("all_vrf.php");
}