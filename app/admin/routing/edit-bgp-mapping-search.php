<?php

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Tools	 	= new Tools ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# permissions
$User->check_module_permissions ("routing", User::ACCESS_RW, true, false);

# validate
$bgp_id     = isset($POST->bgp_id) ? $POST->bgp_id : '';
$search_txt = isset($POST->subnet) ? $POST->subnet : '';

if(!is_numeric($bgp_id))  	{ $Result->show("danger", _("Invalid ID"), true); }
if(strlen($search_txt) < 2)	{ $Result->show("danger", _("Please enter at least 2 characters."), true); }

$query = 'SELECT INET_NTOA(`subnet`) AS `subnet`,`id`,`mask`,`description` FROM `subnets` WHERE (INET_NTOA(`subnet`) LIKE ? OR `description` LIKE ?) AND `subnet` > 1 AND `isFolder` = 0;';

try { $subnets = $Database->getObjectsQuery('subnets', $query, ["$search_txt%", "%$search_txt%"]); }
catch (Exception $e) {
	print $Result->show("danger", $e->getMessage(), true);
}

# printout
if (is_array($subnets) && sizeof($subnets) > 0) {
	// remove existing
	$bgp_mapped_subnets = $Tools->fetch_routing_subnets ("bgp", $bgp_id, false) ?: [];
	$map_arr = [];
	foreach ($bgp_mapped_subnets as $m) {
		$map_arr[] = $m->subnet_id;
	}

	// print remaining
	$cnt=0;
	print "<table class='table table-condensed table-auto'>";
	foreach ($subnets as $s) {
		if (!in_array($s->id, $map_arr)) {
			print "<tr>";
			print "<td><btn class='btn btn-xs btn-success add_bgp_mapping' data-subnetId='$s->id' data-bgp_id='$bgp_id' data-curr_id='$cnt'><i class='fa fa-plus'></i></btn></td>";
			print "<td>";
			print "<select name='select-$cnt' class='select-$cnt form-control input-w-auto input-sm'>";
			print "	<option value='advertised'>"._("Advertised")."</option>";
			print "	<option value='received'>"._("Received")."</option>";
			print "</select>";
			print "</td>";
			print "<td> $s->subnet/$s->mask ($s->description)</td>";
			print "<td class='result-$cnt'></td>";
			print "</tr>";
			$cnt++;
		}
	}
	print "</table>";
	// none
	if($cnt==0) {
		print $Result->show("info", "No subnets found", true);
	}
}
else {
	print $Result->show("info", "No subnets found", true);
}