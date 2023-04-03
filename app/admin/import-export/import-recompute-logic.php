<?php

/**
 *	Recompute Subnets master/nested logic
 ******************************************/

# include required scripts
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object, if not already set
if (!isset($Database)) { $Database 	= new Database_PDO; }
if (!isset($User)) { $User = new User ($Database); }
if (!isset($Admin)) { $Admin = new Admin ($Database); }
if (!isset($Tools)) { $Tools = new Tools ($Database); }
if (!isset($Sections)) { $Sections = new Sections ($Database); }
if (!isset($Subnets)) { $Subnets = new Subnets ($Database); }

# Load colors and icons
include 'import-constants.php';

$rlist = array();
$pass_inputs = ""; # Pass fields from one page to another

# Read selected fields and pass them to the save form
foreach($_GET as $key => $value) {
	if (preg_match("/recomputeSection_(\d+)$/",$key,$matches) && ($value == "on")) {
		# Grab provided values
		$rlist[$matches[1]]["IPv4"] = ($_GET['recomputeSectionIPv4_'.$matches[1]] == "on" ? true : false);
		$rlist[$matches[1]]["IPv6"] = ($_GET['recomputeSectionIPv6_'.$matches[1]] == "on" ? true : false);
		$rlist[$matches[1]]["CVRF"] = ($_GET['recomputeSectionCVRF_'.$matches[1]] == "on" ? true : false);
		# Build hidden form inputs
		$pass_inputs.="<input name='".$key."' type='hidden' value='".$value."' style='display:none;'>";
		$pass_inputs.="<input name='recomputeSectionIPv4_".$matches[1]."' type='hidden' value='".$_GET['recomputeSectionIPv4_'.$matches[1]]."' style='display:none;'>";
		$pass_inputs.="<input name='recomputeSectionIPv6_".$matches[1]."' type='hidden' value='".$_GET['recomputeSectionIPv6_'.$matches[1]]."' style='display:none;'>";
		$pass_inputs.="<input name='recomputeSectionCVRF_".$matches[1]."' type='hidden' value='".$_GET['recomputeSectionCVRF_'.$matches[1]]."' style='display:none;'>";
	}
}

#print "<pre>";print_r($rlist);print "</pre>";

# fetch all sections and store their names
$all_sections = $Sections->fetch_all_sections(); $sect_names = array();
foreach($all_sections as $section) { $section = (array) $section; $sect_names[$section['id']] = $section['name']; }

# fetch all VRFs
$all_vrfs = $Admin->fetch_all_objects("vrf", "vrfId"); $vrf_name = array();
if (!$all_vrfs) { $all_vrfs = array(); }
# insert default VRF in the list
array_splice($all_vrfs,0,0,(object) array(array('vrfId' => '0', 'name' => 'default', 'rd' => '0:0')));
foreach ($all_vrfs as $vrf) { $vrf = (array) $vrf; $vrf_name[(int)$vrf['vrfId']] = $vrf['name']; }

$rows = ""; $counters = ['edit' => 0]; $edata = [];
$recomputeHideUnchanged = ($_GET['recomputeHideUnchanged'] == "on");

foreach ($rlist as $sect_id => $sect_check) {
	$compute_results = $Subnets->recompute_masterIds($sect_id, $sect_check);

	foreach($compute_results as $r) {
		if ( $recomputeHideUnchanged && $r['subnet']->masterSubnetId == $r['newMasterSubnetId'] )
			continue;

		$subnet    = $r['subnet'];
		$newMaster = $Subnets->fetch_object("subnets", "id", $r['newMasterSubnetId']); // Will be cached

		# At the end, save the new master
		$subnet->new_masterSubnetId = $r['newMasterSubnetId'];
		$subnet->new_master = !is_object($newMaster) ? _("Root") : $newMaster->ip."/".$newMaster->mask;
		$subnet->action = ($subnet->masterSubnetId == $subnet->new_masterSubnetId) ? "skip" : "edit";
		$subnet->msg = ($subnet->action == "skip") ? _("No change, skip") : _("New master, update");

		$rows.="<tr class='".$colors[$subnet->action]."'>";
		$rows.="<td><i class='fa ".$icons[$subnet->action]."' rel='tooltip' data-placement='bottom' title='".$subnet->msg."'></i></td>";
		$rows.="<td>".$sect_names[$sect_id]."</td><td>".$subnet->ip."/".$subnet->mask."</td>";
		$rows.="<td>".$subnet->description."</td><td>".$vrf_name[(int)$subnet->vrfId]."</td>";
		$rows.="<td>".$subnet->new_master."</td><td>".$subnet->msg."</td>";
		$rows.="</tr>";

		$edata[$sect_id][] = (array) $subnet;
		$counters[$subnet->action]++;
	}
}