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
if (!isset($Devtype)) { $Devtype = new Devtype ($Database); }

# verify that user is logged in, to guard against direct access of page and possible exploits
$User->check_user_session();

$deviceTypes = $Devtype->fetch_all_objects("deviceTypes", "tid");

# Load existing data
$edata = array();
# process for easier later check
foreach ($deviceTypes as $dt) {
	//cast
	$dt = (array) $dt;
	$edata[$dt['tname']] = $dt;
}

$rows = "";
$counters = array();
$unique = array();

# check the fields
foreach ($data as &$cdata) {
	$msg = ""; $action = ""; $cfieldtds = "";

	# check if required fields are present and not empty
	foreach($reqfields as $creq) {
		if ((!isset($cdata[$creq])) or ($cdata[$creq] == "")) { $msg.= "Required field ".$creq." missing or empty."; $action = "error"; }
	}


	# check if existing
	if ($action != "error") {
		if (isset($edata[$cdata['tname']])) {
			$cdata['tid'] = $edata[$cdata['tname']]['tid'];
			$action = "skip"; # skip duplicate fields if identical, update if different
			if ($cdata['tdescription'] != $edata[$cdata['tname']]['tdescription']) { $msg.= " description will be updated."; $action = "edit"; }

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
	if (!isset($unique[$cdata['tname']])) { $unique[$cdata['tname']] = $cdata['tname']; }

	$rows.="<tr class='".$colors[$action]."'><td><i class='fa ".$icons[$action]."' rel='tooltip' data-placement='bottom' title='"._($msg)."'></i></td>
		<td>".$cdata['tname']."</td>
		<td>".$cdata['tdescription']."</td>
		<td>"._($cdata['msg'])."</td></tr>";

}

?>