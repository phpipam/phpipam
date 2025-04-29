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
	print "<h3 class='panel-title'><i class='fa $tools_menu_icons[$k]'></i> ".$k."</h3>";
	print "</div>";

	# items
	print "<ul class='list-group'>";
	foreach($tool as $t) {
		# active?
		$active = $GET->section==$t['href'] ? "active" : "";
		# print
		print "<li class='list-group-item $active'>";
		# multiple hrefs ?
		$href = pf_explode("/", $t['href']);
		if(sizeof($href)>0) {
			if(isset($href[1]))
			print "<a href='".create_link("tools", $href[0], $href[1])."'><i class='fa fa-angle-right pull-right icon-gray'></i>".$t['name']."</a>";
			else
			print "<a href='".create_link("tools", $href[0])."'><i class='fa fa-angle-right pull-right icon-gray'></i>".$t['name']."</a>";
		}
		else {
			print "<a href='".create_link("tools", $t['href'])."'><i class='fa fa-angle-right pull-right icon-gray'></i>".$t['name']."</a>";
		}
		print "</li>";
	}
	print "</ul>";

	print "</div>";
}