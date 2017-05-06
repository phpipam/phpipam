<?php

/**
 *	Recompute Subnets master/nested logic
 ******************************************/

# include required scripts
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );
require_once( dirname(__FILE__) . '/../../../functions/PEAR/Net/IPv4.php' );
require_once( dirname(__FILE__) . '/../../../functions/PEAR/Net/IPv6.php' );

# initialize user object, if not already set
if (!isset($Database)) { $Database 	= new Database_PDO; }
if (!isset($User)) { $User = new User ($Database); }
if (!isset($Admin)) { $Admin = new Admin ($Database); }
if (!isset($Tools)) { $Tools = new Tools ($Database); }
if (!isset($Sections)) { $Sections	= new Sections ($Database); }
if (!isset($Subnets)) { $Subnets	= new Subnets ($Database); }

# Load colors and icons
include 'import-constants.php';

$p=array();
$pi4 = new Net_IPv4();	# Pear IPv4
$pi6 = new Net_IPv6();	# Pear IPv6

$rlist = array();
$pass_inputs = ""; # Pass fields from one page to another

# Pear IPv6 ip2Bin, local copy
function my_ip2Bin($pi6,$ip)
{
	$binstr = '';

	$ip = $pi6->removeNetmaskSpec($ip);
	$ip = $pi6->Uncompress($ip);

	$parts = explode(':', $ip);

	foreach ( $parts as $v ) {

		$str     = base_convert($v, 16, 2);
		$binstr .= str_pad($str, 16, '0', STR_PAD_LEFT);

	}

	return $binstr;
}

# Sort each edata subnet bucket by subnet lo->hi then by Id lo->hi.
function fn_sort_cmp_subnets($a, $b)
{
	if ($a['subnet'] == $b['subnet']) {
		if ($a['id'] == $b['id'] ) { return 0; }
		return $a['id'] > $b['id'] ? 1 : -1;
	}
	return $a['subnet'] > $b['subnet'] ? 1 : -1;
}

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
foreach ($all_vrfs as $vrf) { $vrf = (array) $vrf; $vrf_name[$vrf['vrfId']] = $vrf['name']; }

# Precompute masks values, to avoid too much CPU load
$masks = array();
for ($i = 0; $i <= 32; $i++) { $masks["IPv4"][$i] = 0xffffffff >> (32 - $i) << (32 - $i); }				# IPv4 masks, long
for ($i = 0; $i <= 128; $i++) { $masks["IPv6"][$i] = str_repeat('1', $i).str_repeat('0', 128 - $i); }	# IPv6 masks, bin str

$rows = ""; $counters = array(); $subnetbyid = array();

/**
 * Fetch the section subnets and save references into multi-dimentional array edata (subnet buckets)
 * edata[subnet_section][subnet_type][subnet_mask] = &subnet
 *
 * Each bucket contains subnets with identical section, type and mask values.
 * Each bucket is sorted by subnet address lo->hi, then by id lo->hi.
 * [section 1 IPv4 /24's], [section 1 IPv4 /16's], [section 1 IPv4 /8's], [section 2 IPv6 /64's] ....
 *
 * When selecting a master subnet for a child we can minimise the search tree:
 *  - Only search buckets belonging to the same section as the child.
 *  - Only search buckets of the same type as the child (IPv4/IPv6).
 *  - Only search buckets with a smaller mask than the child (Master > Child)
 *
 * We can further optimise the search:
 *  - Find the most specific master candidates by decrementing search_mask to search the buckets with
 *    the largest masks first (23,22,21...0). If we find one or more master candidates stop searching
 *    as the remaining buckets contain less specific subnets.
 *  - Each bucket is sorted by subnet lo->hi. If we encounter an subnet > than the child the remaining
 *    items are guaranteed to also not match. Decrement the search_mask and skip to the next bucket.
 *
 * If cross-vrf searching is enabled or strict mode is disabled; multiple master candidates may exist.
 * Select the master from the available candidates based on the selection rules below.
 * First matching rule wins.
 *  - Prefer master subnets in the same VRF as the child.
 *  - Prefer the currently set master subnet.
 *  - Prefer the master subnet with the lowest id value.
 *
 **/

$rows = ""; $counters = array();

# Read IPs for the sections we need to order
foreach ($rlist as $sect_id => $sect_check) {
	$section_subnets = $Subnets->fetch_section_subnets($sect_id);
	# skip empty sections
	if (sizeof($section_subnets)==0) { continue; }

	foreach ($section_subnets as &$subnet) {
		$subnet = (array) $subnet;
		$subnet['ip'] = $Subnets->transform_to_dotted($subnet['subnet']);
		$subnet['type'] = $Subnets->identify_address($subnet['ip']);
		# Precompute subnet in AND format (long for IPv4 and bin str for IPv6)
		$subnet['andip'] = ($subnet['type'] == "IPv4") ? $subnet['subnet'] : my_ip2Bin($pi6,$subnet['ip']);
		# Add to array
		$type = $subnet['type'];
		$mask = $subnet['mask'];
		$edata[$sect_id][$type][$mask][] = &$subnet;
		$subnetbyid[$subnet['id']] = &$subnet;
	}
	unset($subnet);

	# Sort edata subnet buckets
	foreach($edata[$sect_id] as $e_type => $a) {
		foreach($edata[$sect_id][$e_type] as $e_mask => $b) {
			usort($edata[$sect_id][$e_type][$e_mask], "fn_sort_cmp_subnets");
		}
	}

	# Recompute master/nested relations for the selected sections and address families
	# Grab a subnet and find its closest master
	foreach ($section_subnets as &$c_subnet) {
		if (!$sect_check[$c_subnet['type']]) { continue; }	# Skip the IP version we don't want to reorder
		if ($c_subnet['isFolder']) { continue; } # Skip folders
		if ($subnetbyid[$c_subnet['masterSubnetId']]['isFolder']) { continue; } # Skip changing subnet with folder masters

		# Search subnets in the same section, of the same type and with smaller masks.
		$m_candidate = array();
		$search_mask = $c_subnet['mask'];
		$search_type  = $c_subnet['type'];

		while (--$search_mask >= 0) {
			if (sizeof($edata[$sect_id][$search_type][$search_mask]) == 0) { continue; }
			# We found candidates in the previous round.
			if (sizeof($m_candidate) > 0) { break; }

			foreach ($edata[$sect_id][$search_type][$search_mask] as $m_subnet) {
				# buckets are sorted lo->hi. If current m_subnet is > c_subnet then remaining enries will be too.
				if ($m_subnet['subnet'] > $c_subnet['subnet']) { break; }

				# Skip subnets from other VRFs if cross VRF reordering is not wanted (default is on)
				if ((!$sect_check["CVRF"]) && ($c_subnet['vrfId'] != $m_subnet['vrfId'])) { continue; }

				# Main logic here - check if subnet within subnet
				if (($c_subnet['andip'] & $masks[$c_subnet['type']][$m_subnet['mask']]) == $m_subnet['andip']) { $m_candidate[] = $m_subnet; }
			}
		}

		$c_master_id = "0"; $c_master_ip = ""; $c_master_mask = ""; $search_child_vrf_only = 0;

		# Choose from the availale master candidates
		foreach($m_candidate as $m_subnet) {
			# Candidate is in same VRF as child, select it and only consider candidates from this VRF from now on.
			if ($m_subnet['vrfId'] == $c_subnet['vrfId']) {
				$c_master_id = $m_subnet['id']; $c_master_mask = $m_subnet['mask']; $c_master_ip = $m_subnet['ip'];
				$search_child_vrf_only = 1;
			}

			# Previous candidate found in child VRF. Ignore candidates from other VRFs
			if ($search_child_vrf_only == 1 && $m_subnet['vrfId'] != $c_subnet['vrfId']) { continue; }

			# Candidate is our existing master subnet, keep it
			if ($m_subnet['id'] == $c_subnet['masterSubnetId']) {
				$c_master_id = $m_subnet['id']; $c_master_mask = $m_subnet['mask']; $c_master_ip = $m_subnet['ip'];
			}

			# Candidate is more specific than current selection.
			if ($m_subnet['mask'] > $c_master_mask) {
				$c_master_id = $m_subnet['id']; $c_master_mask = $m_subnet['mask']; $c_master_ip = $m_subnet['ip'];
			}
		}

		# At the end, save the new master
		$c_subnet['new_masterSubnetId'] = $c_master_id;
		$c_subnet['new_master'] = (($c_master_id === "0") ? _("Root") : $c_master_ip."/".$c_master_mask);
		$c_subnet['action'] = ($c_subnet['masterSubnetId'] == $c_subnet['new_masterSubnetId'] ? "skip" : "edit");
		$c_subnet['msg'] = ($c_subnet['masterSubnetId'] == $c_subnet['new_masterSubnetId'] ? _("No change, skip") : _("New master, update"));

		$counters[$c_subnet['action']]++;

		if ( $_GET['recomputeHideUnchanged'] == "on" && $c_subnet['masterSubnetId'] == $c_master_id ) { continue; }

		$rows.="<tr class='".$colors[$c_subnet['action']]."'><td><i class='fa ".$icons[$c_subnet['action']]."' rel='tooltip' data-placement='bottom' title='"._($c_subnet['msg'])."'></i></td>";
		$rows.="<td>".$sect_names[$sect_id]."</td><td>".$c_subnet['ip']."/".$c_subnet['mask']."</td>";
		$rows.="<td>".$c_subnet['description']."</td><td>".$vrf_name[$c_subnet['vrfId']]."</td><td>";
		$rows.=$c_subnet['new_master']."</td><td>".$c_subnet['msg']."</td></tr>\n";
	}
	unset($c_subnet);
}

?>
