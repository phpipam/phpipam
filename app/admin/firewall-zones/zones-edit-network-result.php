<?php
/**
 *	firewall zone zones-result.php
 *	verify and update zone informations
 *****************************************/

# functions
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize classes
$Database = new Database_PDO;
$User 	  = new User ($Database);
$Result   = new Result ();
$Zones 	  = new FirewallZones($Database);

# validate session parameters
$User->check_user_session();

# validate the action type
if($POST->action != 'add' && $POST->action != 'delete'){
	$Result->show("danger", _("Invalid action."), true);
}
# check the mastersubnet  ID. valid value: integer
if($POST->masterSubnetId && !preg_match('/^[0-9]+$/i',$POST->masterSubnetId)) {
	$Result->show("danger", _("Invalid subnet ID."), true);
} elseif (!$POST->masterSubnetId) {
	$Result->show("danger", _("Please choose a appropriate network to bind to the firewall zone."), true);
}

# validate network ID informations
if($POST->network) {
	foreach ($POST->network as $network) {
		if(!preg_match('/^[0-9]+$/i',$network)) {
			$Result->show("danger", _("Invalid network ID."), true);
		}
	}
}

# check if the network information should be delivered as form data
if($POST->noZone == 1) {
	# update
	if(!$Zones->check_zone_network($POST->masterSubnetId))							{ $Result->show("danger",  _("Cannot add the network to the zone."), true); }
	else 																				{ $Result->show("success", _("Network successfully added."), true);  }
}

# check the zone ID. valid value: integer
if($POST->netZoneId && !preg_match('/^[0-9]+$/i',$POST->netZoneId)) {
	$Result->show("danger", _("Invalid zone ID."), true);
} else {
	# update
	if ($POST->action == 'add') {
		if(!$Zones->add_zone_network($POST->netZoneId,$POST->masterSubnetId))		{ $Result->show("danger",  _("Cannot add the network to the zone."), true); }
		else 																			{ $Result->show("success", _("Network successfully added."), true);  }
	} elseif ($POST->action == 'delete') {
		if(!$Zones->delete_zone_network($POST->netZoneId,$POST->masterSubnetId))	{ $Result->show("danger",  _("Cannot delete the network mapping."), true); }
		else 																			{ $Result->show("success", _("Successfully deleted the network mapping."), true);  }
	}
}
