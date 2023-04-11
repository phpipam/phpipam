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


# verify that user is logged in, to guard against direct access of page and possible exploits
$User->check_user_session();

# check which sections we need to care about
$used_section = array();


# get all addresses in all subnets in all sections
$edata = array();

$schema = $Tools->fetch_all_objects("schemamgmtips", "id");
$locsizes = $Tools->fetch_all_objects("locationsizes", "id");
$devtypes = $Tools->fetch_all_objects("deviceTypes", "tid");

foreach ($schema as $d) {
    $d = (array) $d;
    $edata['schema'][$d['locationSize']][$d['deviceType']][$d['deviceNumber']] = $d;
}

foreach ($locsizes as $d) {
    $d = (array) $d;
    $edata['locsizes'][strtolower($d['locationSize'])] = $d;
}

foreach ($devtypes as $d) {
    $d = (array) $d;
    $edata['devtypes'][strtolower($d['tname'])] = $d;
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

	# Check if location size is provided and valid and link it if it is
	if (!isset($edata['locsizes'][strtolower($cdata['locationSize'])])
	    ) {
		$msg.= "Invalid location size."; $action = "error";
	} else {
		$cdata['locationSize'] = $edata['locsizes'][strtolower($cdata['locationSize'])]['id'];
	}
	
	# Check if device type is provided and valid and link it if it is
	if (!isset($edata['devtypes'][strtolower($cdata['deviceType'])])
	    ) {
		$msg.= "Invalid device type."; $action = "error";
	} else {
		$cdata['deviceType'] = $edata['devtypes'][strtolower($cdata['deviceType'])]['tid'];
	}
	
//print "loc=". $cdata['locationSize'] ." dev=". $cdata['deviceType'] ." num=". $cdata['deviceNumber'] ." msg=". $msg ."<br>";

	# check if existing in database
	if ($action != "error") {
		if (isset($edata['schema'][$cdata['locationSize']][$cdata['deviceType']][$cdata['deviceNumber']]) ) {
    		$cdata['id'] = $edata['schema'][$cdata['locationSize']][$cdata['deviceType']][$cdata['deviceNumber']]['id'];
			# copy content to a variable for easier checks
			$cedata = $edata['schema'][$cdata['locationSize']][$cdata['deviceType']][$cdata['deviceNumber']];
			# Check if we need to change any fields
			$action = "skip"; # skip duplicate fields if identical, update if different
			# Should we just let the database decided to update or not?  Nice for UI, but alot of 
			# code maintaince here.
			if ($cdata['offset'] != $cedata['offset']) { $msg.= "Offset will be updated."; $action = "edit"; }

			if ($action == "skip") { $msg.= "Duplicate, will skip."; }
		} 
		else {
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
