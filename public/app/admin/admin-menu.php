<?php
/*
 * Print Admin menu pn left if user is admin
 *************************************************/

# verify that user is logged in and admin
$User->is_admin();

# print
foreach($admin_menu as $k=>$tool) {
	print "<div class='panel panel-default adminMenu'>";
	# header
	print "<div class='panel-heading'>";
	print "<h3 class='panel-title'><i class='fa $admin_menu_icons[$k]'></i> ".$k."</h3>";
	print "</div>";

	# items
	print "<ul class='list-group'>";
	foreach($tool as $t) {
		# active?
		$active = $GET->section==$t['href'] ? "active" : "";
		# exception
		if ($t['href']=="devices") {
    		if ($GET->section=="device-types") {
        		$active = "active";
    		}
        }
		# print
		print "<li class='list-group-item $active'>";
		print "<a href='".create_link("administration", $t['href'])."'><i class='fa fa-angle-right pull-right icon-gray'></i>".$t['name']."</a>";
		print "</li>";
	}
	print "</ul>";

	print "</div>";
}