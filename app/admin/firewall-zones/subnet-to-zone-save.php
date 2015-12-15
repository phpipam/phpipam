<?php

/**
 *	subnet-to-zone-save.php
 *	save subnet to zone binding
 *********************************/

# functions
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize classes
$Database = new Database_PDO;
$User 	  = new User ($Database);
$Result   = new Result ();
$Zones    = new FirewallZones($Database);

# validate session parameters
$User->check_user_session();

# validate $_POST['subnetId'] values
if (!preg_match('/^[0-9]+$/i', $_POST['subnetId']))  						{ $Result->show("danger", _("Invalid subnet ID. Do not manipulate the POST values!"), true); }
# validate $_POST['zoneId'] values
if (!preg_match('/^[0-9]+$/i', $_POST['zoneId']) || $_POST['zoneId'] == 0) 	 { $Result->show("danger", _("Invalid or no zone ID. "), true); }

# validate $_POST['deviceId'] values
if ($_POST['deviceId'] && !preg_match('/^[0-9]+$/i', $_POST['deviceId'])) 	 { $Result->show("danger", _("Invalid device ID. Do not manipulate the POST values!"), true); }

# check the zone alias. valid values are alphanumeric characters and special characters like ".-_ "
if($_POST['alias'] && !preg_match('/^[0-9a-zA-Z.\/\-_ ]+$/i',$_POST['alias'])) {
	$Result->show("danger", _("Invalid zone alias value."), true);
}
# check the interface name. valid values are alphanumeric characters and special characters like ".-_/ "
if($_POST['interface'] && !preg_match('/^[0-9a-zA-Z.\/\-_ ]+$/i',$_POST['interface'])) {
	$Result->show("danger", _("Invalid interface."), true);
}

# check if there is any device mapping necessary
if ($_POST['deviceId']) {
	# build the query parameter arrary
	$values = array('id' => '',
					'zoneId' => $_POST['zoneId'],
					'alias' => $_POST['alias'],
					'deviceId' => $_POST['deviceId'],
					'interface' => $_POST['interface']
					);
	# modify
	if(!$Zones->modify_mapping('add',$values)) 	{ $Result->show("danger",  _("Cannot add mapping"), true); }
}
# check and then add the network to the zone if possible
if($Zones->check_zone_network($_POST['subnetId']))	{ 
	if (!$Zones->add_zone_network($_POST['zoneId'],$_POST['subnetId']))	{ 	$Result->show("danger",  _("Cannot add this network to a zone."), true); }
	else 																{	$Result->show("success", _("Zone modified successfully"), true); }
}
?>