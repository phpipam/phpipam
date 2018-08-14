<?php

/**
 *
 * Menu items
 *
 */

# fetch all objects
$objects = $Tools->fetch_customer_objects ($customer->id);

# print menu
$menu 	= [];
$menu[] = "<ul class='nav nav-tabs' style='margin-top:0px;margin-bottom:20px;'>";
foreach ($Tools->get_customer_object_types () as $href=>$name) {
	$active = $_GET['sPage']==$href ? "active" : "";
	$menu[] = "<li role='presentation' class='$active'>";
	$menu[] = "	 <a href='".create_link($_GET['page'], "customers", $_GET['sPage'], $href)."''>"._($name)."</a>";
	$menu[] = "</li>";
}
$menu[] = "</ul>";

print implode("\n", $menu);