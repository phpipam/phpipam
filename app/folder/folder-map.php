<?php



//
// Print map
//
if($slaves) {

	# diff
	if(@$from_search==true) 	{ $max_mask_diff = 1000;}
	elseif(sizeof($slaves)>8)	{ $max_mask_diff = 6; }
	elseif(sizeof($slaves)>6)	{ $max_mask_diff = 8; }
	else 						{ $max_mask_diff = 10; }


	# result array
	$free_subnets = [];
	# max mask possible
	$biggest_subnet_mask = 32;

	# set type
	$type = "";

	foreach($subnets as $id=>$subnet) {

		if($subnet->isFolder!=1) {
			// identify address
			if($Tools->identify_address($subnet->subnet)=="IPv4") 					{ $type = "IPv4"; }
			elseif ($Tools->identify_address($subnet->subnet)=="IPv6" && $type=="")	{ $type = "IPv6"; $biggest_subnet_mask = 128; }

			// save biggest/smallest mask
			if($type=="IPv4") {
				if($subnet->mask<$biggest_subnet_mask)  { $biggest_subnet_mask = $subnet->mask; }
			}
			else {
				if($subnet->mask<$biggest_subnet_mask)  { $biggest_subnet_mask = $subnet->mask; }
			}
		}
	}

	# set pow
	$pow = $type=="IPv4" ? 32 : 128;

	# set smallest mask
	$smallest_subnet_mask = $biggest_subnet_mask+$max_mask_diff>$pow ? $pow : $biggest_subnet_mask+$max_mask_diff;

	// loop
	foreach($slaves as $id=>$subnet) {
		// not folders
		if($subnet->isFolder!=1) {
			// to array
			$subnet = (array) $subnet;

			// not same type ignore
			if($Tools->identify_address($subnet['subnet'])==$type) {
				// subnet limits
				$subnet_limits = $Subnets->max_hosts($subnet);

				# create free objects
				for($searchmask=$subnet['mask']+1; $searchmask<$smallest_subnet_mask+1; $searchmask++) {
					// search ?
					if((@$from_search==true && $searchmask==$from_search_mask) ||  @$from_search==false) {
						// search
						$found = $Subnets->search_available_subnets ($subnet['id'], $searchmask, $count = Subnets::SEARCH_FIND_ALL, $direction = Subnets::SEARCH_FIND_FIRST);

						if($found!==false) {
							// check if subnet has addresses
							if($Addresses->count_subnet_addresses ($subnet['id'])>0) {
								// existing subnet addresses
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
								$free_subnets[$searchmask][$id] = $found;
							}
							else {
								$free_subnets[$searchmask][$id] = $found;
							}
						}
						else {
							$free_subnets[$searchmask][$id] = [];
						}
					}
				}
			}
		}
	}

	// sort
	ksort($free_subnets);


	# if some found print
	if (sizeof($free_subnets)>0) {
		// content
		print "<div id='showFreeSubnets'>";

		// go through masks
		foreach ($free_subnets as $free_mask=>$all_subnets) {

			// title
			print "<div style='margin-top:20px;'>";
			print "<strong>"._("Free")." /$free_mask "._("subnets").":</strong>";
			print "</div>";

			// go through items
			foreach ($all_subnets as $subnet_id=>$items) {
				// arr
				$subnet = (array) $slaves[$subnet_id];
				// max
				$max_subnets = floor(gmp_strval(gmp_pow2(($free_mask-$subnet['mask']))));

				// if possible
				if($max_subnets>0) {

					// save start
					$subnet_start = $subnet['subnet'];

					// print
					print "<span class='subnet_map subnet_map_subnet'>"._("In Subnet")." ".$Subnets->transform_address($subnet_start, "dotted")."/".$subnet['mask'].":</span>";
					print "<div class='ip_vis_subnet'>";
					for($m=1; $m<=$max_subnets;$m++) {
						if(in_array($Subnets->transform_address($subnet_start, "dotted")."/".$free_mask, $items)) {
							print "<span class='subnet_map subnet_map_$pow subnet_map_found'><a href='' data-sectionid='{$section->id}' data-mastersubnetid='".$subnet['id']."' class='createfromfree' data-cidr='".$Subnets->transform_address($subnet_start, "dotted")."/".$free_mask."' rel='tooltip' title='"._("Create subnet")."'>".$Subnets->transform_address($subnet_start, "dotted")."/".$free_mask."</a></span>";
						}
						else {
							print "<span class='subnet_map subnet_map_$pow subnet_map_notfound'>".$Subnets->transform_address($subnet_start, "dotted")."/".$free_mask."</span>";
						}

						// next subnet
						$subnet_start = gmp_strval(gmp_add($subnet_start, gmp_pow2(($pow-$free_mask-1))));

					}
					print "</div>";
					print "<div class='clearfix clearfix1' style='margin-bottom:10px;'></div>";
				}
			}
		}

		print "</div>";
	}
}