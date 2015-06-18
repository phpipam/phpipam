<?php
# print VRF details
print "<div class='subnetDetails'>";
include_once("vrf-details.php");
print "</div>";

# Subnets in VRF
print '<div class="ipaddresses_overlay">';
include_once('vrf-subnets.php');
print '</div>';			
?>