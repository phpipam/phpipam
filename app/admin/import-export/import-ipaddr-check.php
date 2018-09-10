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
if (!isset($Addresses)) { $Addresses = new Addresses ($Database); }
if (!isset($Subnets)) { $Subnets = new Subnets ($Database); }

# verify that user is logged in, to guard against direct access of page and possible exploits
$User->check_user_session();

# read again the custom fields, if any
if (!isset($custom_fields)) { $custom_fields = $Tools->fetch_custom_fields("ipaddresses"); }

# check which sections we need to care about
$used_section = array();
foreach ($data as &$cdata) { $used_section[strtolower($cdata['section'])]=$cdata['section']; }

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

# get all addresses in all subnets in all sections 
$edata = array(); 
$section_names = array(); 
$subnet_data = array();

foreach ($all_sections as $section) {
	$section = (array) $section;
	$section_names[strtolower($section['name'])] = $section;

	# skip sections we're not importing for, so we save cpu time and memory
	if (!isset($used_section[strtolower($section['name'])])) { continue; }

	$section_subnets = $Subnets->fetch_section_subnets($section['id']);

	# skip empty sections
	if (sizeof($section_subnets)==0) { continue; }

	foreach ($section_subnets as $subnet) {
		$subnet = (array) $subnet;

		# ignore folders
		if($subnet['isFolder']) { continue; }

		# store needed subnet information
		$subnet_data[$section['id']][$subnet['vrfId']][$subnet['ip']][$subnet['mask']] = $subnet;
		$subnet_data[$section['id']][$subnet['vrfId']][$subnet['ip']][$subnet['mask']]['type'] = $Subnets->identify_address($subnet['ip']);

		# grab IP addresses
		$ipaddresses = $Addresses->fetch_subnet_addresses ($subnet['id']);

		if (sizeof($ipaddresses)==0) { continue; }

		foreach ($ipaddresses as $ip) {

			//cast
			$ip = (array) $ip;

			$edata[$section['id']][$subnet['vrfId']][$subnet['ip']][$subnet['mask']][$Subnets->transform_address($ip['ip_addr'], "dotted")] = $ip;

		}
	}
}

# Load available tags
$tag_data = array(); $ip_tags = (array) $Addresses->addresses_types_fetch();
foreach ($ip_tags as $c_tag) { $tag_data[$c_tag['type']] = $c_tag; }

# Load available devices
$device_data = array();
$devices = $Tools->fetch_all_objects("devices", "hostname");
if ($devices!==false) {
	foreach($devices as $c_dev) {
		$c_dev = (array) $c_dev;
		$c_dev_sections=explode(";", $c_dev['sections']);
		# Populate each section with the devices it has
		foreach($c_dev_sections as $c_dev_sect) { $device_data[$c_dev_sect][$c_dev['hostname']] = $c_dev;}
	}
}

$rows = "";
$counters = array();
$ndata = array(); # store new addresses in a similar format with edata for easier processing

# check the fields
foreach ($data as &$cdata) {
	$msg = ""; $action = ""; $cfieldtds = "";

	# check if required fields are present and not empty
	foreach($reqfields as $creq) {
		if ((!isset($cdata[$creq]) or ($cdata[$creq] == ""))) { $msg.= "Required field ".$creq." missing or empty."; $action = "error"; }
	}

	# if the subnet contains "/", split it in network and mask
	if ( ($action != "error") && (!empty($cdata['subnet'])) ) {
		if (preg_match("/\//", $cdata['subnet'])) {
			list($caddr,$cmask) = explode("/",$cdata['subnet'],2);
			$cdata['mask'] = $cmask;
			$cdata['subnet'] = $caddr;
		}
		else { $msg.= "The subnet needs to have the mask defined as /BM (Bit Mask)"; $action = "error"; }
		if ((!empty($cdata['mask'])) && (!preg_match("/^([0-9]+|[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+)$/", $cdata['mask']))) {
			$msg.="Invalid network mask format."; $action = "error";
		} else {
			$cdata['type'] = $Subnets->identify_address($cdata['subnet']);
			if (($cdata['type'] == "IPv6") && (($cdata['mask']<0) || ($cdata['mask']>128))) { $msg.="Invalid IPv6 network mask."; $action = "error"; }
		}
	}

	# Check if section is provided and valid and link it if it is
	if (!isset($section_names[strtolower($cdata['section'])])) {
		$msg.= "Invalid section."; $action = "error";
	} else {
		$cdata['sectionId'] = $section_names[strtolower($cdata['section'])]['id'];
	}

	# Check if VRF is provided and valid and link it if it is
	if (!empty($cdata['vrf'])) {
		if (!isset($vrf_data[$cdata['vrf']])) {
			$msg.= "Invalid VRF."; $action = "error";
		} else {
			$cdata['vrfId'] = $vrf_data[$cdata['vrf']]['vrfId'];
		}
	} else {
		# no VRF provided, using default
		$cdata['vrfId'] = 0;
	}

	# Check if Subnet is provided and valid and link it if it is
	if ((!empty($cdata['subnet'])) and (!empty($cdata['mask']))) {
		if (!isset($subnet_data[$cdata['sectionId']][$cdata['vrfId']][$cdata['subnet']][$cdata['mask']])) {
			$msg.= "Invalid Subnet. Confirm that the subnet exists before importing into it."; $action = "error";
		} else {
			$cdata['subnetId'] = $subnet_data[$cdata['sectionId']][$cdata['vrfId']][$cdata['subnet']][$cdata['mask']]['id'];
		}
	} else {
		$iphost = $cdata['ip_addr'];
		$binIp= sprintf( "%032b", ip2long($cdata['ip_addr']));
		foreach ($subnet_data[$cdata['sectionId']][$cdata['vrfId']] as $key => $subObject )
		{       
			$longestMask = 0;
			$binSub = sprintf("%032b",ip2long($key));
			reset($subObject);
			$mask = intval(key($subObject));
			if( (substr($binIp,0,$mask) == substr($binSub,0,$mask)) && ($mask > $longestMask) )
			{
				$msg.= "Masque Trouve: " . $mask ;
				$longestMask = $mask;
				$cdata['subnetId'] = $subnet_data[$cdata['sectionId']][$cdata['vrfId']][$key][$mask]['id'];
				$cdata['subnet'] = $key;
				$cdata['mask'] = $mask; 
			}		
		}
		# no subnet provided, search not implemented yet, giving out error.
		if(empty($cdata['subnetId']))
		{
			$msg.= "No corresponding subnet found."; $action = "error";
		}
	}

	# Match device name against device IDs
	if (!empty($cdata['device'])) {
		if (!isset($device_data[$cdata['sectionId']][$cdata['device']])) {
			$msg.= "Invalid device hostname."; $action = "error";
		} else {
			$cdata['switch'] = $device_data[$cdata['sectionId']][$cdata['device']]['id'];
		}
	} else {
		$cdata['switch'] = 0;
	}

	# Check if a tag is provided and valid and link it if it is
	if (!empty($cdata['tag'])) {
		if (!isset($tag_data[$cdata['tag']])) {
			$msg.= "Invalid tag."; $action = "error";
		} else {
			$cdata['state'] = $tag_data[$cdata['tag']]['id'];
		}
	} else {
		# no tag provided, using default
		$cdata['state'] = 2;
	}


	# Verify gateway
	if (in_array(strtolower($cdata['is_gateway']),array("yes","true","1"))) { $cdata['is_gateway'] = 1; } else { $cdata['is_gateway'] = 0; }

	if ($action != "error") {
    	if(!$Addresses->validate_ip($cdata['ip_addr'])) { $msg.="Invalid IP address."; $action = "error"; }
		if ((!empty($cdata['dns_name'])) and (!preg_match("/^(?=.{1,255}$)[0-9A-Za-z](?:(?:[0-9A-Za-z]|-){0,61}[0-9A-Za-z])?(?:\.[0-9A-Za-z](?:(?:[0-9A-Za-z]|-){0,61}[0-9A-Za-z])?)*\.?$/", $cdata['dns_name']))) { $msg.="Invalid DNS name."; $action = "error"; }
		if (preg_match("/[;'\"]/", $cdata['description'])) { $msg.="Invalid characters in description."; $action = "error"; }
		if ($cdata['mac']) {
			if (!preg_match("/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/", $cdata['mac'])) { $msg.="Invalid MAC address."; $action = "error"; }
		}
		if (preg_match("/[;'\"]/", $cdata['owner'])) { $msg.="Invalid characters in owner name."; $action = "error"; }
		if (preg_match("/[;'\"]/", $cdata['note'])) { $msg.="Invalid characters in note."; $action = "error"; }
	}

	# check if duplicate in the import data
	if ($action != "error") {
		if (isset($ndata[$cdata['sectionId']][$cdata['vrfId']][$cdata['subnet']][$cdata['mask']][$cdata['ip_addr']])) { $msg.="Duplicate entry in imported data."; $action = "error"; }
	}

	# check if existing in database
	if ($action != "error") {
		if (isset($edata[$cdata['sectionId']][$cdata['vrfId']][$cdata['subnet']][$cdata['mask']][$cdata['ip_addr']])) {
			# copy content to a variable for easier checks
			$cedata = $edata[$cdata['sectionId']][$cdata['vrfId']][$cdata['subnet']][$cdata['mask']][$cdata['ip_addr']];

			# Check if we need to change any fields
			$action = "skip"; # skip duplicate fields if identical, update if different
			if ($cdata['dns_name'] != $cedata['dns_name']) { $msg.= "Address DNS name will be updated."; $action = "edit"; }
			if ($cdata['description'] != $cedata['description']) { $msg.= "Address description will be updated."; $action = "edit"; }
			if ($cdata['mac'] != $cedata['mac']) { $msg.= "Address MAC address will be updated."; $action = "edit"; }
			if ($cdata['owner'] != $cedata['owner']) { $msg.= "Address owner will be updated."; $action = "edit"; }
			if ($cdata['switch'] != $cedata['switch']) { $msg.= "Device will be updated."; $action = "edit"; }
			if ($cdata['note'] != $cedata['note']) { $msg.= "Address note will be updated."; $action = "edit"; }
			if ($cdata['state'] != $cedata['state']) { $msg.= "Address tag (state) will be updated."; $action = "edit"; }

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
// 				$cdata['port'] = $cedata['port'];		$cdata['lastseen'] = $cedata['lastseen'];
// 				$cdata['excludePing'] = $cedata['excludePing'];
// 				$cdata['PTRignore'] = $cedata['PTRignore']; $cdata['PTR'] = $cedata['PTR']; $cdata['NAT'] = $cedata['NAT'];
// 				$cdata['firewallAddressObject'] = $cedata['firewallAddressObject'];
			}
		} else {
			$msg.="New entry, will be added."; $action = "add";

			# Add it to ndata for duplicate check
			$ndata[$cdata['sectionId']][$cdata['vrfId']][$cdata['subnet']][$cdata['mask']][$cdata['ip_addr']] = $cdata;
		}
	}
	$cdata['msg'].= $msg;
	$cdata['action'] = $action;
	$counters[$action]++;

	$cdata['subnet'] = $cdata['subnet']."/".$cdata['mask'];

	$rows.="<tr class='".$colors[$action]."'><td><i class='fa ".$icons[$action]."' rel='tooltip' data-placement='bottom' title='"._($msg)."'></i></td>";
	foreach ($expfields as $cfield) { $rows.= "<td>".$cdata[$cfield]."</td>"; }
	$rows.= "<td>"._($cdata['msg'])."</td></tr>";


}

?>
