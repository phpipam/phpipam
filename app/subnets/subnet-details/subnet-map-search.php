<?php

// max mask possible
$pow = $Tools->identify_address($subnet['subnet'])=="IPv6" ? 128 : 32;

//
// Select mask
//
$masks = [];
print "<h4>"._("Select mask").":</h4><hr>";
for($m=$subnet['mask']+1; $m<=$pow; $m++) {
	// active
	$active = $m==$GET->ipaddrid ? "btn-success" : "";

	// number of subnets
	$subnet_num = @gmp_strval(gmp_pow2(($m-$subnet['mask'])));

	// print link
	print "<a class='btn btn-sm btn-default $active' href='".create_link("subnets",$GET->section,$GET->subnetId,"mapsearch",$m)."'>/$m ($subnet_num "._("Subnets").")</a><br>";
	// save to masks array
	$masks[] = $m;
}

//
// include
//
if(is_numeric($GET->ipaddrid)) {
	$from_search = true;
	$from_search_mask = $GET->ipaddrid+1;

	print "<h4 style='margin-top:30px;'>"._("Result").":</h4><hr>";

	// include
	include ('subnet-map.php');
}
