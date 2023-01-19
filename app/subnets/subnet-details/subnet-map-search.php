<?php


# max mask possible
$biggest_subnet_mask = 32;

# set type
$type = "";

// ipv6
if($Tools->identify_address($subnet['subnet'])=="IPv6") {
	$biggest_subnet_mask = $subnet['mask']+10>128 ? 128 : $subnet['mask'];
	$pow = 128;
}
else {
	$biggest_subnet_mask = $subnet['mask']+10>32 ? 32 : $subnet['mask'];
	$pow = 32;
}


//
// Select mask
//
$masks = [];
print "<h4>"._("Select mask").":</h4><hr>";
for($m=$biggest_subnet_mask+1; $m<=$pow; $m++) {
	// active
	$active = $m==@$_GET['ipaddrid'] ? "btn-success" : "";

	// number of subnets
	$subnet_num = @gmp_strval(gmp_pow(2, ($m-$subnet['mask'])));

	// print link
	print "<a class='btn btn-sm btn-default $active' href='".create_link("subnets",$_GET['section'],$_GET['subnetId'],"mapsearch",$m)."'>/$m ($subnet_num "._("Subnets").")</a><br>";
	// save to masks array
	$masks[] = $m;
}


// validate




//
// include
//
if(is_numeric(@$_GET['ipaddrid'])) {
	$from_search = true;
	$from_search_mask = $_GET['ipaddrid']+1;

	print "<h4 style='margin-top:30px;'>"._("Result").":</h4><hr>";

	// include
	include ('subnet-map.php');
}
