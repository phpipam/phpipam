<?php


# max mask possible
$biggest_subnet_mask = 32;

# set type
$type = "";

// count subnets
$cnt = [];

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

		// first ?
		if(!isset($cnt[$subnet->mask])) { $cnt[$subnet->mask]=0; }

		// size
		$cnt[$subnet->mask]++;
	}
}

# set pow
$pow = $type=="IPv4" ? 32 : 128;

//
// Select mask
//
$masks = [];
print "<h4>"._("Select mask").":</h4><hr>";
for($m=$biggest_subnet_mask+1; $m<=$pow; $m++) {
	// active
	$active = $m==@$_GET['ipaddrid'] ? "btn-success" : "";

	// number of subnets
	$subnet_num = 0;
	foreach ($cnt as $mask=>$repeats) {
		if($mask<$m) {
			$subnet_num = @gmp_strval(gmp_mul($repeats,gmp_add($subnet_num, gmp_pow(2, ($m-$mask)))));
		}
	}

	// print link
	print "<a class='btn btn-sm btn-default $active' href='".create_link("folder",$_GET['section'],$_GET['subnetId'],"mapsearch",$m)."'>/$m ($subnet_num "._("Subnets").")</a><br>";
	// save to masks array
	$masks[] = $m;
}

//
// include
//
if(is_numeric(@$_GET['ipaddrid'])) {
	// validate
	if(!in_array($_GET['ipaddrid'], $masks)) {
		print "<h4 style='margin-top:30px;'>"._("Result").":</h4><hr>";
		$Result->show("danger", _("Invalid mask"), false);
	}
	else {
		$from_search = true;
		$from_search_mask = $_GET['ipaddrid'];

		print "<h4 style='margin-top:30px;'>"._("Result").":</h4><hr>";

		// include
		include ('folder-map.php');
	}
}
