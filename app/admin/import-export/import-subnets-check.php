<?php

/*
 * Data import load
 *************************************************/

# include required scripts
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object, if not already set
if (!isset($Database)) { $Database 	= new Database_PDO; }
if (!isset($User)) { $User = new User ($Database); }
if (!isset($Admin)) { $Admin = new Admin ($Database); }
if (!isset($Tools)) { $Tools = new Tools ($Database); }
if (!isset($Sections)) { $Sections	= new Sections ($Database); }
if (!isset($Subnets)) { $Subnets	= new Subnets ($Database); }

# verify that user is logged in, to guard against direct access of page and possible exploits
$User->check_user_session();

# Get mask check
#automated $cidrformat = isset($_GET['cidrformat']) ? $_GET['cidrformat'] : "off";
#separate option $rebuildmnr = isset($_GET['rebuildmnr']) ? $_GET['rebuildmnr'] : "off";

# read again the custom fields, if any
if (!isset($custom_fields)) { $custom_fields = $Tools->fetch_custom_fields("subnets"); }

# fetch all l2 domains
$vlan_domains = $Admin->fetch_all_objects("vlanDomains", "id");
# load VLANs and process for easier later check
$vlan_data = array();
foreach ($vlan_domains as $vlan_domain) {
	//cast
	$vlan_domain = (array) $vlan_domain;
	// read vlans
	$all_vlans = $Admin->fetch_multiple_objects("vlans", "domainId", $vlan_domain['id'], "number");
	$all_vlans = (array) $all_vlans;
	// skip empty domains
	if (sizeof($all_vlans)==0) {
		# create entry for domain check
		$vlan_data[$vlan_domain['name']] = array();
		continue;
	}
	//write all VLAN entries
	foreach ($all_vlans as $vlan) {
		//cast
		$vlan = (array) $vlan;
		$vlan_data[$vlan_domain['name']][$vlan['number']] = $vlan;
	}
}

# fetch all VRFs
$all_vrfs = $Admin->fetch_all_objects("vrf", "vrfId");
if (!$all_vrfs) { $all_vrfs = array(); }
# insert default VRF in the list
array_splice($all_vrfs,0,0,(object) array(array('vrfId' => '0', 'name' => 'default', 'rd' => '0:0')));
# process for easier later check
$vrf_data = array();
foreach ($all_vrfs as $vrf) {
	//cast
	$vrf = (array) $vrf;
	$vrf_data[$vrf['name']] = $vrf;
	$vrf_data[$vrf['rd']] = $vrf;	# add also RD as VRF name, will allow matches against both name and RD
}

# fetch all sections and load all subnets
$all_sections = $Sections->fetch_all_sections();

# get all subnets in all sections
$edata = array(); $section_names = array();

foreach ($all_sections as $section) {
	$section = (array) $section;
	$section_names[$section['name']] = $section;
	$section_subnets = $Subnets->fetch_section_subnets($section['id']);
	# skip empty sections
	if (sizeof($section_subnets)==0) { continue; }

	foreach ($section_subnets as $subnet) {
		$subnet = (array) $subnet;
		# load whole record in array
		$edata[$section['id']][$subnet['vrfId']][$subnet['ip']][$subnet['mask']] = $subnet;
		$edata[$section['id']][$subnet['vrfId']][$subnet['ip']][$subnet['mask']]['type'] = $Subnets->identify_address($subnet['ip']);
	}
}

#print_r($vlan_data);

$rows = "";
$counters = array();
$ndata = array(); # store new networks in a similar format with edata for easier processing

# check the fields
foreach ($data as &$cdata) {
	$msg = ""; $action = ""; $cfieldtds = "";

	# check if required fields are present and not empty
	foreach($reqfields as $creq) {
		if ((!isset($cdata[$creq])) or ($cdata[$creq] == "")) { $msg.= "Required field ".$creq." missing or empty."; $action = "error"; }
	}

	# if the subnet contains "/", split it in network and mask
	if ($action != "error") {
		if (preg_match("/\//", $cdata['subnet'])) {
			list($caddr,$cmask) = explode("/",$cdata['subnet'],2);
			$cdata['mask'] = $cmask;
			$cdata['subnet'] = $caddr;
		} else { # check that mask is provided
			if ((!isset($cdata['mask'])) or ($cdata['mask'] == "")) { $msg.= "Required field mask missing or empty."; $action = "error"; }
		}
		if ((!empty($cdata['mask'])) && (!preg_match("/^([0-9]+|[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+)$/", $cdata['mask']))) {
			$msg.="Invalid network mask format."; $action = "error";
		} else {
			$cdata['type'] = $Subnets->identify_address($cdata['subnet']);
			if (($cdata['type'] == "IPv6") && (($cdata['mask']<0) || ($cdata['mask']>128))) { $msg.="Invalid IPv6 network mask."; $action = "error"; }
		}
	}

	# Check if section is provided and valid and link it if it is
	if (!isset($section_names[$cdata['section']])) {
		$msg.= "Invalid section."; $action = "error";
	} else {
		$cdata['sectionId'] = $section_names[$cdata['section']]['id'];
	}

	# Check if VRF is provided and valid and link it if it is
	if (!empty($cdata['vrf'])) {
		if (!isset($vrf_data[$cdata['vrf']])) {
			$msg.= "Invalid VRF."; $action = "error";
		} else {
			$cdata['vrfId'] = $vrf_data[$cdata['vrf']]['vrfId'];
		}
	} else {
		# no VRF provided
		$cdata['vrfId'] = 0;
	}

	# Check if VLAN Domain and VLAN are valid, and link them if they are
	if (!empty($cdata['domain'])) { $cdom = $cdata['domain']; } else { $cdom = "default"; }
	if (!isset($vlan_data[$cdom])) {
		# the default domain is always there, so if anything is missing we return an error
		$msg.= "Invalid VLAN domain."; $action = "error";
	} else {
		if (!empty($cdata['vlan'])) {
			if (in_array(strtolower($cdata['vlan']),["na","n/a","nan"])) { $cdata['vlan'] = ""; }
			if ((!empty($cdata['vlan'])) && (strtolower($cdata['vlan']) != "na")) {
				if (!isset($vlan_data[$cdom][$cdata['vlan']])) {
					$msg.= "VLAN not found in provided domain."; $action = "error";
				} else {
					$cdata['vlanId'] = $vlan_data[$cdom][$cdata['vlan']]['vlanId'];
				}
			} else {
				# no VLAN provided
				$cdata['vlanId'] = 0;
			}
		}
	}

	# check data format
	if ($action != "error") {
		if ($net = $Subnets->get_network_boundaries($cdata['subnet'],$cdata['mask'])) {
			$cdata['mask'] = $net['bitmask'];
			$cidr_check = $Subnets->verify_cidr_address($cdata['subnet']."/".$cdata['mask']);
			if (strlen($cidr_check)>5) { $msg.=$cidr_check; $action = "error"; }
		} else { $msg.=$net['message']; $action = "error"; }
		if (preg_match("/[;'\"]/", $cdata['description'])) { $msg.="Invalid characters in description."; $action = "error"; }
		if ((!empty($cdata['vrf'])) && (!preg_match("/^[a-zA-Z0-9-:]+$/", $cdata['vrf']))) { $msg.="Invalid VRF name format."; $action = "error"; }
		if ((!empty($cdata['vlan'])) && (!preg_match("/^[0-9]+$/", $cdata['vlan']))) { $msg.="Invalid VLAN number format."; $action = "error"; }
		if ((!empty($cdata['domain'])) && (!preg_match("/^[a-zA-Z0-9-_ ]+$/", $cdata['domain']))) { $msg.="Invalid VLAN domain format."; $action = "error"; }
	}

	# check if duplicate in the import data
	if ($action != "error") {
		if (isset($ndata[$cdata['sectionId']][$cdata['vrfId']][$cdata['subnet']][$cdata['mask']])) { $msg.="Duplicate entry in imported data."; $action = "error"; }
	}

	# check if existing in database
	if ($action != "error") {
		if (isset($edata[$cdata['sectionId']][$cdata['vrfId']][$cdata['subnet']][$cdata['mask']])) {
			# copy content to a variable for easier checks
			$cedata = $edata[$cdata['sectionId']][$cdata['vrfId']][$cdata['subnet']][$cdata['mask']];

			# Check if we need to change any fields
			$action = "skip"; # skip duplicate fields if identical, update if different
			if ($cdata['description'] != $cedata['description']) { $msg.= "Subnet description will be updated."; $action = "edit"; }
			if ($cdata['vlanId'] != $cedata['vlanId']) { $msg.= "VLAN ID will be updated."; $action = "edit"; }
			# Check if the values of the custom fields have changed
			if(sizeof($custom_fields) > 0) {
				foreach($custom_fields as $myField) {
					if ($cdata[$myField['name']] != $cedata[$myField['name']]) {
						$msg.= $myField['name']." will be updated."; $action = "edit";
					}
				}
			}

			if ($action == "skip") {
				$msg.= "Duplicate, will skip.";
			} else {
				# set id of matched subnet
				$cdata['id'] = $cedata['id'];
				# copy some fields which we don't import, but need to set
				$cdata['masterSubnetId'] = $cedata['masterSubnetId'];
				$cdata['permissions'] = $cedata['permissions'];
				// $cdata['allowRequests'] = $cedata['allowRequests'];			$cdata['showName'] = $cedata['showName'];
				// $cdata['pingSubnet'] = $cedata['pingSubnet'];				$cdata['discoverSubnet'] = $cedata['discoverSubnet'];
			}
		} else {
			$msg.="New entry, will be added."; $action = "add";
			# Set master to 0 for now, will figure that after we add it, with the recompute function
			$cdata['masterSubnetId'] = "0";
			# Inherit section permissions for new subnets
			$cdata['permissions'] = $section_names[$cdata['section']]['permissions'];

			# No overlap checking, smaller subnets will be considered nested, larger ones will be masters
			# Master ID is set later, with the recompute functions

			# Add it to ndata for duplicate check
			$ndata[$cdata['sectionId']][$cdata['vrfId']][$cdata['subnet']][$cdata['mask']] = $cdata;
		}
	}

	$cdata['msg'].= $msg;
	$cdata['action'] = $action;
	$counters[$action]++;

	$rows.="<tr class='".$colors[$action]."'><td><i class='fa ".$icons[$action]."' rel='tooltip' data-placement='bottom' title='"._($msg)."'></i></td>";
	foreach ($expfields as $cfield) { $rows.= "<td>".$cdata[$cfield]."</td>"; }
	$rows.= "<td>"._($cdata['msg'])."</td></tr>";

}

?>