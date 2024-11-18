<?php

/**
 *	firewall zone mapping-edit-result.php
 *	verify and update mapping informations
 **********************************************/

# functions
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize classes
$Database = new Database_PDO;
$User = new User ($Database);
$Result = new Result ();
$Zones = new FirewallZones($Database);

# validate session parameters
$User->check_user_session();

# validate the action type
if($POST->action != 'add' && $POST->action != 'delete' && $POST->action != 'edit'){
	$Result->show("danger", _("Invalid action."), true);
}
# check the zone alias. valid values are alphanumeric characters and special characters like ".-_: "
if($POST->alias && !preg_match('/^[0-9a-z.\/\-_ :]+$/i',$POST->alias)) {
	$Result->show("danger", _("Invalid zone alias value."), true);
}
# check the interface name. valid values are alphanumeric characters and special characters like ".-_/: "
if($POST->interface && !preg_match('/^[0-9a-z.\/\-_ :]+$/i',$POST->interface)) {
	$Result->show("danger", _("Invalid interface."), true);
}
if ($POST->action != 'delete') {
	# check the zone  ID. valid value: integer
	if(!preg_match('/^[0-9]+$/i',$POST->zoneId)) {
		$Result->show("danger", _("Invalid zone ID."), true);
	} elseif (preg_match('/^0$/i',$POST->zoneId)) {
		$Result->show("danger", _("Invalid zone ID."), true);
	}
	# check the device ID. valid value: integer
	if(!preg_match('/^[0-9]+$/i',$POST->deviceId)) {
		$Result->show("danger", _("Invalid device ID."), true);
	} elseif (preg_match('/^0$/i',$POST->deviceId)) {
		$Result->show("danger", _("Please select a device."), true);
	}
	# check the mapping ID. valid value: integer
	if($POST->id && !preg_match('/^[0-9]+$/i',$POST->id)) {
		$Result->show("danger", _("Invalid mapping ID."), true);
	}
}


# build the query parameter arrary
$values = array('id' => $POST->id,
				'zoneId' => $POST->zoneId,
				'alias' => $POST->alias,
				'deviceId' => $POST->deviceId,
				'interface' => $POST->interface
				);
# modify
if(!$Zones->modify_mapping($POST->action,$values)) 	{ $Result->show("danger",  _("Cannot add mapping"), true); }
else 													{ $Result->show("success", _("Mapping modified successfully"), true);  }
