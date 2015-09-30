<?php
// firewall zone ajax.php
// deliver content for ajax requests

// functions 
require( dirname(__FILE__) . '/../../../functions/functions.php');

// initialize classes
$Database = new Database_PDO;
$User = new User ($Database);
$Admin = new Admin ($Database);
$Result = new Result ();
$Zones = new FirewallZones($Database);

// DEBUG
print '<pre>';
var_dump($_POST);
print '</pre>';
// !DEBUG


// fetch module settings
$firewallZoneSettings = json_decode($User->settings->firewallZoneSettings,true);

// validations
// validate the action type
if($_POST['action'] != 'add' && $_POST['action'] != 'delete' && $_POST['action'] != 'edit'){
	$Result->show("danger", _("Invalid action."), true);
}
// check the zone name. valid values are alphanumeric characters
if($_POST['zone'] && !preg_match('/^[0-9a-zA-Z]+$/i',$_POST['zone'])) {
	$Result->show("danger", _("Invalid zone."), true);
}
// check the zone indicator ID. valid values are 0 or 1.
if($_POST['indicator'] && !preg_match('/^[0-1]$/i',$_POST['indicator'])) {
	$Result->show("danger", _("Invalid indicator ID."), true);
}
// check the section ID. valid value: integer
if($_POST['sectionId'] && !preg_match('/^[0-9]+$/i',$_POST['sectionId'])) {
	$Result->show("danger", _("Invalid section ID."), true);
}
// check the subnet ID. valid value: integer
if($_POST['masterSubnetId'] && !preg_match('/^[0-9]+$/i',$_POST['masterSubnetId'])) {
	$Result->show("danger", _("Invalid subnet ID."), true);
}
// check the layer2 domain ID. valid value: integer
if($_POST['vlanDomain'] && !preg_match('/^[0-9]+$/i',$_POST['vlanDomain'])) {
	$Result->show("danger", _("Invalid L2 domain ID."), true);
}
// check the vlan ID. valid value: integer
if($_POST['vlanId'] && !preg_match('/^[0-9]+$/i',$_POST['vlanId'])) {
	$Result->show("danger", _("Invalid VLAN ID."), true);
}

// transform description to valid value
$description = trim(htmlspecialchars($_POST['description']));

// generate a unique zone name if the generator is set to decimal or hex
if (!$_POST['zone'] && $_POST['action'] == 'add')  {
	print 'Let\'s generate the zone! :-)';
}
$zoneSettings = array(	'id' => $_POST['id'],
						'generator' => $_POST['generator'],
						'zone' => $_POST['zone'],
						'indicator' => $_POST['indicator'],
						'description' => $description,
						'subnetId' => $_POST['masterSubnetId'],
						'vlanId' => $_POST['vlanId']);


if(!$Zones->modify_zone($_POST['action'],$zoneSettings)) {
	$Result->show("danger",  _("Cannot add zone"), true);
} else { 
	$Result->show("success", _("Zone added successfully"), true); 
}


// stop reloading and closing popup!
$Result->show("danger", _(":: DEBUG :: "), true);
?>