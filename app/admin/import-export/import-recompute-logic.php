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

# Read IPs for the sections we need to order
foreach ($rlist as $sect_id => $sect_check) {
	$section_subnets = $Subnets->fetch_section_subnets($sect_id);
	# skip empty sections
	if (sizeof($section_subnets)==0) { continue; }

	$isFolder[$sect_id] = array();
	
	foreach ($section_subnets as &$subnet) {
		$subnet = (array) $subnet;
		$subnet['ip'] = $Subnets->transform_to_dotted($subnet['subnet']);
		$subnet['type'] = $Subnets->identify_address($subnet['ip']);
		# Precompute subnet in AND format (long for IPv4 and bin str for IPv6)
		$subnet['andip'] = ($subnet['type'] == "IPv4") ? $subnet['subnet'] : my_ip2Bin($pi6,$subnet['ip']);
		# Add to array
		$edata[$sect_id][] = $subnet;
		$isFolder[$sect_id][$subnet['id']] = $subnet['isFolder'];
	}
}

$rows = ""; $counters = array();

# Recompute master/nested relations for the selected sections and address families
foreach ($rlist as $sect_id => $sect_check) {
	# Skip empty sections
	if (!$edata[$sect_id]) { continue; }

	# Grab a subnet and find its closest master
	foreach ($edata[$sect_id] as &$c_subnet) {
		if (!$sect_check[$c_subnet['type']]) { continue; }	# Skip the IP version we don't want to reorder
		if ($c_subnet['isFolder']) { continue; } # Skip folders
		if ($isFolder[$sect_id][$c_subnet['masterSubnetId']]) { continue; } # Skip changing subne with folder masters

		$c_master_id = "0"; $c_master_ip = ""; $c_master_mask = "";

		# Check against all other subnets in section
		foreach ($edata[$sect_id] as $m_subnet) {
			if ($c_subnet['type'] != $m_subnet['type']) { continue; }	# Skip if current IP version doesn't match master IP version
			if ((!$sect_check["CVRF"]) && ($c_subnet['vrfId'] != $m_subnet['vrfId'])) { continue; }	# Skip IPs from other VRFs if cross VRF reordering is not wanted (default is on)
			# Main logic here - check if subnet within subnet
			if ((($c_subnet['andip'] & $masks[$c_subnet['type']][$m_subnet['mask']]) == $m_subnet['andip']) && ($c_subnet['mask'] > $m_subnet['mask'])) {	# We have a match
				if ($m_subnet['mask'] > $c_master_mask) {	# If new master is more specific than old master, record the data
					$c_master_id = $m_subnet['id']; $c_master_mask = $m_subnet['mask']; $c_master_ip = $m_subnet['ip'];
				}
			}
		}

		# At the end, save the new master
		$c_subnet['new_masterSubnetId'] = $c_master_id;
		$c_subnet['new_master'] = (($c_master_id === "0") ? _("Root") : $c_master_ip."/".$c_master_mask);
		$c_subnet['action'] = ($c_subnet['masterSubnetId'] == $c_subnet['new_masterSubnetId'] ? "skip" : "edit");
		$c_subnet['msg'] = ($c_subnet['masterSubnetId'] == $c_subnet['new_masterSubnetId'] ? _("No change, skip") : _("New master, update"));

		$rows.="<tr class='".$colors[$c_subnet['action']]."'><td><i class='fa ".$icons[$c_subnet['action']]."' rel='tooltip' data-placement='bottom' title='"._($c_subnet['msg'])."'></i></td>";
		$rows.="<td>".$sect_names[$sect_id]."</td><td>".$c_subnet['ip']."/".$c_subnet['mask']."</td>";
		$rows.="<td>".$c_subnet['description']."</td><td>".$vrf_name[$c_subnet['vrfId']]."</td><td>";
		$rows.=$c_subnet['new_master']."</td><td>".$c_subnet['msg']."</td></tr>\n";

		$counters[$c_subnet['action']]++;
	}
}

?>
