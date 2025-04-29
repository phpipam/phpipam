<?php

# array
$free_subnets = [];


// ipv6
if($Tools->identify_address($subnet['subnet'])=="IPv6") {
	$maxmask = $subnet['mask']+10>128 ? 128 : $subnet['mask']+10;
	$pow = 128;
}
else {
	$maxmask = $subnet['mask']+10>32 ? 32 : $subnet['mask']+10;
	$pow = 32;
}


# reset if search
if(@$from_search===true) {
	$maxmask = $GET->ipaddrid+1;
	$subnetmask = $GET->ipaddrid-1;
}
else {
	$subnetmask = $subnet['mask'];
}

// print $subnet['mask'];
// print $maxmask;

# create free objects
for($searchmask=$subnetmask+1; $searchmask<$maxmask; $searchmask++) {
	$found = $Subnets->search_available_subnets ($subnet['id'], $searchmask, $count = Subnets::SEARCH_FIND_ALL, $direction = Subnets::SEARCH_FIND_FIRST);
	if($found!==false) {
		// check if subnet has addresses
		if($Addresses->count_subnet_addresses ($subnet['id'])>0) {
			// subnet aqddresses
			$subnet_addresses = $Addresses->fetch_subnet_addresses ($subnet['id'], null, null, $fields = ['ip_addr']);

			// remove found subnets with hosts !
			foreach($found as $k=>$f) {
				// parse
				$parsed = $Subnets->cidr_network_and_mask($f);
				// boundaries
				$boundaries = $Subnets->get_network_boundaries ($parsed[0], $searchmask);
				// broadcast to int
				$maxint = isset($boundaries['broadcast']) ? $Subnets->transform_address ($boundaries['broadcast'],"decimal") : 0;

				if(sizeof($subnet_addresses)>0) {
					foreach ($subnet_addresses as $a) {
						if ($a->ip_addr>=$Subnets->transform_address($parsed[0],"decimal") && $a->ip_addr<=$maxint ) {
							unset($found[$k]);
						}
					}
				}
			}

			// save remaining
			$free_subnets[$searchmask] = $found;
		}
		else {
			$free_subnets[$searchmask] = $found;
		}
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
				print "<span class='subnet_map subnet_map_$pow subnet_map_found'><a href='' data-sectionid='".$section['id']."' data-mastersubnetid='".$subnet['id']."' class='createfromfree' data-cidr='".$Subnets->transform_address($subnet_start, "dotted")."/".$free_mask."' rel='tooltip' title='"._("Create subnet")."'>".$Subnets->transform_address($subnet_start, "dotted")."/".$free_mask."</a></span>";
			}
			else {
				print "<span class='subnet_map subnet_map_$pow subnet_map_notfound'>".$Subnets->transform_address($subnet_start, "dotted")."/".$free_mask."</span>";
			}

			// next subnet
			// $subnet_start = $subnet_start + pow(2,($pow-$free_mask));
			$subnet_start = gmp_strval(gmp_add($subnet_start, gmp_pow2(($pow-$free_mask))));

		}
		print "</div>";
		print "<div class='clearfix clearfix1'></div>";
	}

	print "</div>";
}