<?php

/**
 *	subnet-to-zone-save.php
 *	save subnet to zone binding
 *********************************/

# functions
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize classes
$Database = new Database_PDO;
$User 	  = new User ($Database);
$Result   = new Result ();
$Zones    = new FirewallZones($Database);

# validate session parameters
$User->check_user_session();

# validate $POST->subnetId values
if (!preg_match('/^[0-9]+$/i', $POST->subnetId))  						{ $Result->show("danger", _("Invalid subnet ID. Do not manipulate the POST values!"), true); }
# validate $POST->zoneId values
if (!preg_match('/^[0-9]+$/i', $POST->zoneId) || $POST->zoneId == 0) 	 { $Result->show("danger", _("Invalid or no zone ID. "), true); }

# validate $POST->deviceId values
if ($POST->deviceId && !preg_match('/^[0-9]+$/i', $POST->deviceId)) 	 { $Result->show("danger", _("Invalid device ID. Do not manipulate the POST values!"), true); }

# check the zone alias. valid values are alphanumeric characters and special characters like ".-_: "
if($POST->alias && !preg_match('/^[0-9a-z.\/\-_ :]+$/i',$POST->alias)) {
	$Result->show("danger", _("Invalid zone alias value."), true);
}
# check the interface name. valid values are alphanumeric characters and special characters like ".-_/: "
if($POST->interface && !preg_match('/^[0-9a-z.\/\-_ :]+$/i',$POST->interface)) {
	$Result->show("danger", _("Invalid interface."), true);
}

# check if there is any device mapping necessary
if ($POST->deviceId) {
	# build the query parameter arrary
	$values = array('id' => '',
					'zoneId' => $POST->zoneId,
					'alias' => $POST->alias,
					'deviceId' => $POST->deviceId,
					'interface' => $POST->interface
					);
	# modify
	if(!$Zones->modify_mapping('add',$values)) 	{ $Result->show("danger",  _("Cannot add mapping"), true); }
}
# check and then add the network to the zone if possible
if($Zones->check_zone_network($POST->subnetId))	{
	if (!$Zones->add_zone_network($POST->zoneId,$POST->subnetId))	{ 	$Result->show("danger",  _("Cannot add this network to a zone."), true); }
	else 																{	$Result->show("success", _("Zone modified successfully"), true); }
}
