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
if (!isset($Devtype)) { $Devtype = new Devtype ($Database); }
if (!isset($Devices)) { $Devices = new Devtype ($Database); }

# verify that user is logged in, to guard against direct access of page and possible exploits
$User->check_user_session();

# read again the custom fields, if any
if (!isset($custom_fields)) { $custom_fields = $Tools->fetch_custom_fields("devices"); }

# check which sections we need to care about
$used_section = array();
foreach ($data as &$cdata) { $used_section[strtolower($cdata['section'])]=$cdata['section']; }

# fetch all sections and load all subnets
$all_sections = $Sections->fetch_all_sections();
#$devtypes = $Sections->fetch_all_sections();

# get all addresses in all subnets in all sections
$edata = array();
$section_names = array();
$subnet_data = array();
$subnet_search = array();

$devices = $Devices->fetch_all_objects("devices", "id");
$deviceTypes = $Devtype->fetch_all_objects("deviceTypes", "tid");

foreach ($all_sections as $section) {
	$section = (array) $section;
	$section_names[strtolower($section['name'])] = $section;
}

foreach ($devices as $d) {
    $d = (array) $d;
    $edata['devices'][strtolower($d['hostname'])] = $d;
}

foreach ($deviceTypes as $d) {
    $d = (array) $d;
    $edata['deviceTypes'][strtolower($d['tname'])] = $d;
}

#error_log ( "devicestypes : " . json_encode ($deviceTypes) ) ;


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

	# Check if section is provided and valid and link it if it is
	if (!isset($section_names[strtolower($cdata['section'])])) {
		$msg.= "Invalid section."; $action = "error";
	} else {
		$cdata['sections'] = $section_names[strtolower($cdata['section'])]['id'];
	}

	# Check if deviceType is provided and valid and link it if it is
	if (!isset($edata['deviceTypes'][strtolower($cdata['deviceType'])])
	    ) {
		$msg.= "Invalid deviceType."; $action = "error";
	} else {
		$cdata['type'] = $edata['deviceTypes'][strtolower($cdata['deviceType'])]['tid'];
	}

	if ($action != "error") {
    	if(
    	    isset($cdata['ip_addr']) &&
    	    !is_blank($cdata['ip_addr']) &&
    	    !$Addresses->validate_ip($cdata['ip_addr'])
    	)
    	    {
    	        $msg.="Invalid IP address.";
    	        $action = "error";
    	    }
		if ((!empty($cdata['hostname'])) and (!preg_match("/^(?=.{1,255}$)[0-9A-Za-z](?:(?:[0-9A-Za-z]|-){0,61}[0-9A-Za-z])?(?:\.[0-9A-Za-z](?:(?:[0-9A-Za-z]|-){0,61}[0-9A-Za-z])?)*\.?$/", $cdata['hostname']))) { $msg.="Invalid DNS name."; $action = "error"; }
#       Allow all chars in description ... Why Limit it ?
#		if (preg_match("/[;'\"]/", $cdata['description'])) { $msg.="Invalid characters in description."; $action = "error"; }
		if ($cdata['mac']) {
			if (!preg_match("/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/", $cdata['mac'])) { $msg.="Invalid MAC address."; $action = "error"; }
		}
	}


	# check if existing in database
	if ($action != "error") {
		if (isset($edata['devices'][strtolower($cdata['hostname'])]) ) {
    		$cdata['id'] = $edata['devices'][strtolower($cdata['hostname'])]['id'];
			# copy content to a variable for easier checks
			$cedata = $edata[strtolower($cdata['hostname'])];
			# Check if we need to change any fields
			$action = "skip"; # skip duplicate fields if identical, update if different
			# Should we just let the database decided to update or not?  Nice for UI, but alot of
			# code maintaince here.
			if ($cdata['description'] != $cedata['description']) { $msg.= "Device description will be updated."; $action = "edit"; }
			if ($cdata['ip_addr'] != $cedata['ip_addr']) { $msg.= "Device ip_addr will be updated."; $action = "edit"; }
			if ($cdata['type'] != $cedata['type']) { $msg.= "DeviceType will be updated."; $action = "edit"; }
			if ($cdata['sectionId'] != $cedata['sectionId']) { $msg.= "sectionId will be updated."; $action = "edit"; }
			if ($cdata['rack'] != $cedata['rack']) { $msg.= "rack will be updated."; $action = "edit"; }
			if ($cdata['rack_start'] != $cedata['rack_start']) { $msg.= "rack_start will be updated."; $action = "edit"; }
			if ($cdata['rack_size'] != $cedata['rack_size']) { $msg.= "rack_size will be updated."; $action = "edit"; }
			if ($cdata['location'] != $cedata['location']) { $msg.= "location will be updated."; $action = "edit"; }

			# Check if the values of the custom fields have changed
			if(sizeof($custom_fields) > 0) {
				foreach($custom_fields as $myField) {
					if ($cdata[$myField['name']] != $cedata[$myField['name']]) {
						$msg.= $myField['name']." will be updated."; $action = "edit";
					}
				}
			}

			if ($action == "skip") { $msg.= "Duplicate, will skip."; }
		} else {
			$msg.="New entry, will be added."; $action = "add";
			# Add it to ndata for duplicate check
			# $ndata[$cdata['sectionId']][$cdata['vrfId']][$cdata['subnet']][$cdata['mask']][$cdata['ip_addr']] = $cdata;
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
