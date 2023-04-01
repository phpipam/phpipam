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

$hardware = $Tools->fetch_all_objects("hardware", "id");
$devices = $Tools->fetch_all_objects("devices", "id");
$models = $Tools->fetch_all_objects("hwmodels", "id");
$status = $Tools->fetch_all_objects("hwstatus", "id");
$owned = $Tools->fetch_all_objects("hwowners", "id");
$racks = $Tools->fetch_all_objects("racks", "id");

foreach ($hardware as $d) {
    $d = (array) $d;
    $edata['hardware'][strtolower($d['serialNumber'])] = $d;
}

foreach ($devices as $d) {
    $d = (array) $d;
    $edata['devices'][strtolower($d['hostname'])] = $d;
}

foreach ($models as $d) {
    $d = (array) $d;
    $edata['models'][strtolower($d['modelNumber'])] = $d;
}

foreach ($status as $d) {
    $d = (array) $d;
    $edata['status'][strtolower($d['hwStatus'])] = $d;
}

foreach ($owned as $d) {
    $d = (array) $d;
    $edata['owned'][strtolower($d['name'])] = $d;
}

foreach ($racks as $d) {
    $d = (array) $d;
    $edata['racks'][strtolower($d['name'])] = $d;
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

	# Check if device is provided and valid and link it if it is
	if (!isset($edata['devices'][strtolower($cdata['device'])])
	    ) {
		$msg.= "Invalid device."; $action = "error";
	} else {
		$cdata['device'] = $edata['devices'][strtolower($cdata['device'])]['id'];
	}
	
	# Check if model is provided and valid and link it if it is
	if (!isset($edata['models'][strtolower($cdata['model'])])
	    ) {
		$msg.= "Invalid Model."; $action = "error";
	} else {
		$cdata['model'] = $edata['models'][strtolower($cdata['model'])]['id'];
	}
	
	# Check if status is provided and valid and link it if it is
	if (!isset($edata['status'][strtolower($cdata['status'])])
	    ) {
		$msg.= "Invalid Status."; $action = "error";
	} else {
		$cdata['status'] = $edata['status'][strtolower($cdata['status'])]['id'];
	}
	
	# Check if ownedBy is provided and valid and link it if it is
	if (!isset($edata['owned'][strtolower($cdata['ownedBy'])])
	    ) {
		$msg.= "Invalid OwnedBy."; $action = "error";
	} else {
		$cdata['ownedBy'] = $edata['owned'][strtolower($cdata['ownedBy'])]['id'];
	}
	
	# Check if managedBy is provided and valid and link it if it is
	if (!isset($edata['owned'][strtolower($cdata['managedBy'])])
	    ) {
		$msg.= "Invalid ManagedBy."; $action = "error";
	} else {
		$cdata['managedBy'] = $edata['owned'][strtolower($cdata['managedBy'])]['id'];
	}
	
	# Check if rack is provided and valid and link it if it is
	if (!isset($edata['racks'][strtolower($cdata['rack'])])
	    ) {
		$cdata['rack'] =$cdata['rack'];
	} else {
		$cdata['rack'] = $edata['racks'][strtolower($cdata['rack'])]['id'];
	}
	
	# Check if halfunit is provided and valid and link it if it is
	if (strtolower($cdata['halfUnit'])== "left"){$cdata['halfUnit']=1;}
	elseif (strtolower($cdata['halfUnit'])== "right"){$cdata['halfUnit']=2;}
	else {$cdata['halfUnit'] =0;}


	# check if existing in database
	if ($action != "error") {
		if (isset($edata['hardware'][strtolower($cdata['serialNumber'])]) ) {
    		$cdata['id'] = $edata['hardware'][strtolower($cdata['serialNumber'])]['id'];
			# copy content to a variable for easier checks
			$cedata = $edata['hardware'][strtolower($cdata['serialNumber'])];
			# Check if we need to change any fields
			$action = "skip"; # skip duplicate fields if identical, update if different
			# Should we just let the database decided to update or not?  Nice for UI, but alot of 
			# code maintaince here.
			if ($cdata['model'] != $cedata['model']) { $msg.= " HW Model will be updated."; $action = "edit"; }
			if ($cdata['status'] != $cedata['status']) { $msg.= " HW Status will be updated."; $action = "edit"; }
			if ($cdata['dateReceived'] != $cedata['dateReceived']) { $msg.= " Date Received will be updated."; $action = "edit"; }
			if ($cdata['ownedBy'] != $cedata['ownedBy']) { $msg.= " OwnedBy will be updated."; $action = "edit"; }
			if ($cdata['managedBy'] != $cedata['managedBy']) { $msg.= " ManagedBy will be updated."; $action = "edit"; }
			if ($cdata['rack'] != $cedata['rack']) { $msg.= " rack will be updated."; $action = "edit"; }
			if ($cdata['rack_start'] != $cedata['rack_start']) { $msg.= " rack_start will be updated."; $action = "edit"; }
			if ($cdata['halfUnit'] != $cedata['halfUnit']) { $msg.= " halfUnit will be updated."; $action = "edit"; }
			if ($cdata['device'] != $cedata['device']) { $msg.= " Device will be updated."; $action = "edit"; }
			if ($cdata['deviceMember'] != $cedata['deviceMember']) { $msg.= " Device Member will be updated."; $action = "edit"; }


			if ($action == "skip") { $msg.= "Duplicate, will skip."; }
		} else {
			$msg.="New entry, will be added."; $action = "add";
		}
	}

	$cdata['msg'].= $msg;
	$cdata['action'] = $action;
	$counters[$action]++;


	$rows.="<tr class='".$colors[$action]."'><td><i class='fa ".$icons[$action]."' rel='tooltip' data-placement='bottom' title='"._($msg)."'></i></td>";
	foreach ($expfields as $cfield) { $rows.= "<td>".$cdata[$cfield]."</td>"; }
	$rows.= "<td>"._($cdata['msg'])."</td></tr>";

}
