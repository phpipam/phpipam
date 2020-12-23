<?php

# array
$free_subnets = [];

$maxmask = $subnet['mask']+10>32 ? 32 : $subnet['mask']+10;

# create free objects
for($searchmask=$subnet['mask']+1; $searchmask<$maxmask; $searchmask++) {
	$found = $Subnets->search_available_subnets ($subnet['id'], $searchmask, $count = Subnets::SEARCH_FIND_ALL, $direction = Subnets::SEARCH_FIND_FIRST);
	if($found!==false) {
		$free_subnets[$searchmask] = $found;
	}
	else {
		$free_subnets[$searchmask] = [];
	}
}

# if some found print
if (sizeof($free_subnets)>0) {
	// content
	print "<div id='showFreeSubnets'>";
	foreach ($free_subnets as $free_mask=>$items) {
		// max
		$max_subnets = pow(2,$free_mask-$subnet['mask']);
		//
		//
		print "<div style='margin-top:20px;'>";
		print "<strong>".sizeof($items)."/$max_subnets "._("free")." /$free_mask "._("subnets")."</strong>";
		print "</div>";

		// save start
		$subnet_start = $subnet['subnet'];

		// print
		print "<div class='ip_vis_subnet'>";
		for($m=1; $m<=$max_subnets;$m++) {
			if(in_array($Subnets->transform_address($subnet_start, "dotted")."/".$free_mask, $items)) {
				print "<span class='subnet_map subnet_map_found'><a href='' data-sectionid='{$section[id]}' data-mastersubnetid='{$subnet[id]}' class='createfromfree' data-cidr='".$Subnets->transform_address($subnet_start, "dotted")."/".$free_mask."' rel='tooltip' title='"._("Create subnet")."'>".$Subnets->transform_address($subnet_start, "dotted")."/".$free_mask."</a></span>";
			}
			else {
				print "<span class='subnet_map subnet_map_notfound'>".$Subnets->transform_address($subnet_start, "dotted")."/".$free_mask."</span>";
			}

			// next subnet
			$subnet_start = $subnet_start+pow(2,(32-$free_mask));
		}
		print "</div>";
		print "<div class='clearfix clearfix1'></div>";
	}

	print "</div>";
}