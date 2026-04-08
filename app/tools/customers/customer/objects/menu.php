<?php

/**
 *
 * Menu items
 *
 */

# print menu
$menu 	= [];
$menu[] = "<ul class='nav nav-tabs' style='margin-top:0px;margin-bottom:20px;'>";
foreach ($Tools->get_customer_object_types () as $href=>$name) {
	// add badge
	$cnt = isset($objects[$href]) ? sizeof($objects[$href]) : 0;

	// print
	$active = $GET->sPage==$href ? "active" : "";
	$menu[] = "<li role='presentation' class='$active'>";
	$menu[] = "	 <a href='".create_link($GET->page, "customers", $GET->subnetId, $href)."''>".$name." <span class='badge ' style='margin-left:5px;'>$cnt<span></a>";
	$menu[] = "</li>";
}
$menu[] = "</ul>";

print implode("\n", $menu);