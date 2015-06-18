<?php
# print VLAN details
print "<div class='subnetDetails'>";
include_once("vlan-details.php");
print "</div>";

# Subnets in VLAN
print '<div class="ipaddresses_overlay">';
include_once('vlan-subnets.php');
print '</div>';		
?>