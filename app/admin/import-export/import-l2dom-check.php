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

# fetch all l2 domains
$vlan_domains = $Admin->fetch_all_objects("vlanDomains", "id");

# Load existing data
$edata = array();
# process for easier later check
foreach ($vlan_domains as $vlan_domain) {
	//cast
	$vlan_domain = (array) $vlan_domain;
	$edata[$vlan_domain['name']] = $vlan_domain;
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

	# check data format
	if ($action != "error") {
		if (!preg_match("/^[a-zA-Z0-9-_ ]+$/", $cdata['name'])) { $msg.="Invalid name format."; $action = "error"; }
		if (preg_match("/[;'\"]/", $cdata['description'])) { $msg.="Invalid characters in description."; $action = "error"; }
	}

	# check if duplicate L2 domain
	if (isset($unique[$cdata['name']])) { $msg.= "Duplicate VLAN domain found. Please check import file."; $action = "error"; }

	# check if existing
	if ($action != "error") {
		if (isset($edata[$cdata['name']])) {
			$cdata['id'] = $edata[$cdata['name']]['id'];
			$action = "skip"; # skip duplicate fields if identical, update if different
			if ($cdata['description'] != $edata[$cdata['name']]['description']) { $msg.= "L2 Domain description will be updated."; $action = "edit"; }

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
	if (!isset($unique[$cdata['name']])) { $unique[$cdata['name']] = $cdata['name']; }

	$rows.="<tr class='".$colors[$action]."'><td><i class='fa ".$icons[$action]."' rel='tooltip' data-placement='bottom' title='"._($msg)."'></i></td>
		<td>".$cdata['name']."</td>
		<td>".$cdata['description']."</td>
		<td>"._($cdata['msg'])."</td></tr>";

}

?>