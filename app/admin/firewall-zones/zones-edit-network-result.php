<?php
/**
 *	firewall zone zones-result.php
 *	verify and update zone informations
 *****************************************/

# functions
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize classes
$Database = new Database_PDO;
$User 	  = new User ($Database);
$Result   = new Result ();
$Zones 	  = new FirewallZones($Database);

# validate session parameters
$User->check_user_session();

# validate the action type
if($_POST['action'] != 'add' && $_POST['action'] != 'delete'){
	$Result->show("danger", _("Invalid action."), true);
}
# check the mastersubnet  ID. valid value: integer
if($_POST['masterSubnetId'] && !preg_match('/^[0-9]+$/i',$_POST['masterSubnetId'])) {
	$Result->show("danger", _("Invalid subnet ID."), true);
} elseif (!$_POST['masterSubnetId']) {
	$Result->show("danger", _("Please choose a appropriate network to bind to the firewall zone."), true);
}

# validate network ID informations
if($_POST['network']) {
	foreach ($_POST['network'] as $network) {
		if(!preg_match('/^[0-9]+$/i',$network)) {
			$Result->show("danger", _("Invalid network ID."), true);			
		}
	}
}

# check if the network information should be delivered as form data
if($_POST['noZone'] == 1) {
	# update
	if(!$Zones->check_zone_network($_POST['masterSubnetId']))							{ $Result->show("danger",  _("Cannot add the network to the zone."), true); }
	else 																				{ $Result->show("success", _("Network successfully added."), true);  }
}

# check the zone ID. valid value: integer
if($_POST['netZoneId'] && !preg_match('/^[0-9]+$/i',$_POST['netZoneId'])) {
	$Result->show("danger", _("Invalid zone ID."), true);
} else {
	# update
	if ($_POST['action'] == 'add') {
		if(!$Zones->add_zone_network($_POST['netZoneId'],$_POST['masterSubnetId']))		{ $Result->show("danger",  _("Cannot add the network to the zone."), true); }
		else 																			{ $Result->show("success", _("Network successfully added."), true);  }
	} elseif ($_POST['action'] == 'delete') {
		if(!$Zones->delete_zone_network($_POST['netZoneId'],$_POST['masterSubnetId']))	{ $Result->show("danger",  _("Cannot delete the network mapping."), true); }
		else 																			{ $Result->show("success", _("Successfully deleted the networt mapping."), true);  }
	}
}
?>