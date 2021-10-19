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
if (!isset($Customers)) { $Customers = new Customers ($Database); }

# verify that user is logged in, to guard against direct access of page and possible exploits
$User->check_user_session();

$customers = $Customers->fetch_all_objects("customers", "id");

# Load existing data
$edata = array();
# process for easier later check
foreach ($customers as $c) {
	//cast
	$c = (array) $c;
	$edata[$c['id']] = $c;
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
		if (isset($edata[$cdata['title']])) {
			$cdata['id'] = $edata[$cdata['title']]['id'];
			$action = "skip"; # skip duplicate fields if identical, update if different
			if ($cdata['address'] != $edata[$cdata['title']]['address']) { $msg.= " address will be updated."; $action = "edit"; }
			if ($cdata['postcode'] != $edata[$cdata['title']]['postcode']) { $msg.= " postcode will be updated."; $action = "edit"; }
			if ($cdata['city'] != $edata[$cdata['title']]['city']) { $msg.= " city will be updated."; $action = "edit"; }
			if ($cdata['state'] != $edata[$cdata['title']]['state']) { $msg.= " state will be updated."; $action = "edit"; }
			if ($cdata['contact_person'] != $edata[$cdata['title']]['contact_person']) { $msg.= " contact person will be updated."; $action = "edit"; }
			if ($cdata['contact_phone'] != $edata[$cdata['title']]['contact_phone']) { $msg.= " contact phone will be updated."; $action = "edit"; }
			if ($cdata['contact_mail'] != $edata[$cdata['title']]['contact_mail']) { $msg.= " contact email will be updated."; $action = "edit"; }
			if ($cdata['note'] != $edata[$cdata['title']]['note']) { $msg.= " note will be updated."; $action = "edit"; }

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
	if (!isset($unique[$cdata['title']])) { $unique[$cdata['title']] = $cdata['title']; }

	$rows.="<tr class='".$colors[$action]."'><td><i class='fa ".$icons[$action]."' rel='tooltip' data-placement='bottom' title='"._($msg)."'></i></td>
		<td>".$cdata['title']."</td>
		<td>".$cdata['address']."</td>
		<td>".$cdata['postcode']."</td>
		<td>".$cdata['city']."</td>
		<td>".$cdata['state']."</td>
		<td>".$cdata['contact_person']."</td>
		<td>".$cdata['contact_phone']."</td>
		<td>".$cdata['contact_mail']."</td>
		<td>".$cdata['note']."</td>
		<td>"._($cdata['msg'])."</td></tr>";

}

?>