<?php
# print Folder details
print "<div class='subnetDetails'>";
include_once("folder-details.php");
print "</div>";

# Subnets in Folder
print '<div class="ipaddresses_overlay">';
include_once('folder-subnets.php');
print '</div>';	
?>