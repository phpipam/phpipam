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

# verify that user is logged in, to guard against direct access of page and possible exploits
$User->check_user_session();

# read again the custom fields, if any
if (!isset($custom_fields)) { $custom_fields = $Tools->fetch_custom_fields("vrf"); }

# Load existing data
$all_vrfs = $Admin->fetch_all_objects("vrf", "vrfId");
if (!$all_vrfs) { $all_vrfs = array(); }

$edata = array();
# process for easier later check
foreach ($all_vrfs as $vrf) {
	//cast
	$vrf = (array) $vrf;
	$edata[$vrf['rd']] = $vrf;
}

$rows = "";
$counters = array();

# check the fields
foreach ($data as &$cdata) {
	$msg = ""; $action = "";

	# check if required fields are present and not empty
	foreach($reqfields as $creq) {
		if ((!isset($cdata[$creq])) or ($cdata[$creq] == "")) { $msg.= "Required field ".$creq." missing or empty."; $action = "error"; }
	}

	# check data format
	if ($action != "error") {
		if (!preg_match("/^[a-zA-Z0-9-_]+$/", $cdata['name'])) { $msg.="Invalid name format."; $action = "error"; }
		if (!preg_match("/^[0-9:]+$/", $cdata['rd'])) { $msg.="Invalid RD format."; $action = "error"; }
		if (preg_match("/[;'\"]/", $cdata['description'])) { $msg.="Invalid characters in description."; $action = "error"; }
	}

	# check if existing
	if ($action != "error") {
		if (isset($edata[$cdata['rd']])) {
			$cdata['vrfId'] = $edata[$cdata['rd']]['vrfId'];
			$action = "skip"; # skip duplicate fields if identical, update if different
			if ($cdata['name'] != $edata[$cdata['rd']]['name']) { $msg.= "VRF name will be updated."; $action = "edit"; }
			if ($cdata['description'] != $edata[$cdata['rd']]['description']) { $msg.= "VRF description will be updated."; $action = "edit"; }

			# Check if the values of the custom fields have changed
			if(sizeof($custom_fields) > 0) {
				foreach($custom_fields as $myField) {
					if ($cdata[$myField['name']] != $edata[$cdata['rd']][$myField['name']]) {
						$msg.= $myField['name']." will be updated."; $action = "edit";
					}
				}
			}
			
			if ($action == "skip") {
				$msg.= "Duplicate, will skip.";
			}
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

?>
