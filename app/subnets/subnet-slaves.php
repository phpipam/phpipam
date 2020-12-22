<?php

# menu
$active_map = @$_GET['sPage']=="map" ? "active" : "";
$active_subnets = $active_map=="active" ? "" : "active";

# print menu
print '<ul class="nav nav-tabs ip-det-switcher" style="margin-bottom:20px;margin-top:20px;">';
print '	<li role="presentation" class="'.$active_subnets.'"><a href="'.create_link("subnets", $subnet['sectionId'], $subnet['id']).'">'._('Subnets').'</a></li>';
print '	<li role="presentation" class="'.$active_map.'"><a href="'.create_link("subnets", $subnet['sectionId'], $subnet['id'], 'map').'">'._("Subnet space map").'</a></li>';
print '</ul>';

# what ?
if($active_map=="active") {
	include("subnet-slaves-free-subnets.php");
}
else {
	include("subnet-slaves-subnets.php");
}