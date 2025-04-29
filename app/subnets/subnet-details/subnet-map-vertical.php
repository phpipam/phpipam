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
				$parsed = explode("/", $f);
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

	// get maximum number of subnets that will be calculated
	$max_all_subnets = pow(2,array_keys($free_subnets)[count($free_subnets)-1]-$subnet['mask']);
	$levels = sizeof($free_subnets);

	// content
	print "<div id='showFreeSubnets'>";

	// table
	print "<table>";

	// headers
	print "<tr>";
	foreach ($free_subnets as $free_mask=>$items) {
	print "	<td>/".$free_mask."</td>";
	}
	print "</tr>";


	$all_keys = array_keys($free_subnets);


	for($m=0; $m<=$max_all_subnets;$m++) {

		// save start
		$subnet_start = $subnet['subnet'];

		print "<tr>";
		foreach ($all_keys as $array_key) {
			// max subnets
			$max_subnets = pow(2,$array_key-$subnet['mask']);


				if(in_array($Subnets->transform_address($subnet_start, "dotted")."/".$array_key, $free_subnets[$array_key])) {
					print "<td>".$free_subnets[$array_key][$m]."/".$array_key."</td>";
				}
				else {
					print "<td>/</td>";
				}

				// next subnet
				$subnet_start = gmp_strval(gmp_add($subnet_start, gmp_pow2(($pow-$array_key))));


				// rowspan
				// $rowspan = $max_all_subnets/$max_subnets;

		}
		print "</tr>";
	}



	// items
	foreach ($free_subnets as $free_mask=>$items) {
		break;

		// max
		$max_subnets = pow(2,$free_mask-$subnet['mask']);

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
	}

	print "</table>";
	print "</div>";
}