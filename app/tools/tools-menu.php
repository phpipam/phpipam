<?php

/**
 * Script to display menu
 *
 */

# verify that user is logged in
$User->check_user_session();

# print
foreach($tools_menu as $k=>$tool) {
	print "<div class='panel panel-default toolsMenu'>";
	# header
	print "<div class='panel-heading'>";
	print "<h3 class='panel-title'><i class='fa $tools_menu_icons[$k]'></i> "._($k)."</h3>";
	print "</div>";

	# items
	print "<ul class='list-group'>";
	foreach($tool as $t) {
		# active?
		$active = $_GET['section']==$t['href'] ? "active" : "";
		# print
		print "<li class='list-group-item $active'>";
		print "<a href='".create_link("tools", $t['href'])."'><i class='fa fa-angle-right pull-right icon-gray'></i>"._($t['name'])."</a>";
		print "</li>";
	}
	print "</ul>";

	print "</div>";
}
?>