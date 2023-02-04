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
$Admin 	  = new Admin ($Database);
$Result   = new Result ();
$Zones 	  = new FirewallZones($Database);

# validate session parameters
$User->check_user_session();

# fetch module settings
$firewallZoneSettings = pf_json_decode($User->settings->firewallZoneSettings,true);

# validations
# validate the action type
if($_POST['action'] != 'add' && $_POST['action'] != 'delete' && $_POST['action'] != 'edit'){
	$Result->show("danger", _("Invalid action."), true);
}

# check the zone name. valid values are alphanumeric characters and special characters like ".-_ "
if($_POST['zone'] && !preg_match('/^[0-9a-zA-Z.\-_ ]+$/i',$_POST['zone'])) {
	$Result->show("danger", _("Invalid zone name value."), true);
}

if($firewallZoneSettings['zoneGenerator']=="2")
if(is_blank(@$_POST['zone']) || strlen(@$_POST['zone'])>$firewallZoneSettings['zoneLength']) {
	$Result->show("danger", _("Invalid zone name length."), true);
}

# check the zone indicator ID. valid values are 0 or 1.
if($_POST['indicator'] && !preg_match('/^[0-1]$/i',$_POST['indicator'])) {
	$Result->show("danger", _("Invalid indicator ID."), true);
}

# check the generator value. valid value: integer
if($_POST['generator'] && !preg_match('/^[0-9]+$/i',$_POST['generator'])) {
	$Result->show("danger", _("Invalid generator ID."), true);
}

# check the padding value. valid value: on or off
if($_POST['padding'] && !preg_match('/^(on|off)$/i',$_POST['padding'])) {
	$Result->show("danger", _("Invalid padding setting."), true);
}

# transform the padding checkbox values into 1 or 0
if($_POST['generator'] != 2) {
	if($_POST['padding']) {
		$padding = 1;
	} else {
		$padding = 0;
	}
}

# transform description to valid value
$description = trim(htmlspecialchars($_POST['description']));

# generate a unique zone name if the generator is set to decimal or hex
if (!$_POST['zone'] && $_POST['action'] == 'add')  {
	if(!$zone=$Zones->generate_zone_name()){
		$Result->show("danger",  _("Cannot generate zone name"), true);
	}
} else {
	$zone = $_POST['zone'];
}

# validate the zone name if text mode is enabled
if ($_POST['generator'] == 2 ) {
	$textSettings = array ( $_POST['zone'],$_POST['id']);
	if(!$zone=$Zones->generate_zone_name($textSettings)){
		$Result->show("danger",  _("Cannot validate zone name"), true);
	}
}

# build the query parameter arrary
if($_POST['generator'] != 2 && $_POST['action'] == 'edit') {
	$values = array('id' => $_POST['id'],
					'indicator' => $_POST['indicator'],
					'padding' => $padding,
					'description' => $description,
					'network' => $_POST['network']
					);
}
else {
	$values = array('id' => $_POST['id'],
					'generator' => $_POST['generator'],
					'zone' => $zone,
					'indicator' => $_POST['indicator'],
					'padding' => $padding,
					'description' => $description,
					'network' => $_POST['network']
					);
}

# update
if(!$Zones->modify_zone($_POST['action'],$values))	{ $Result->show("danger",  _("Cannot add zone"), true); }
else 												{ $Result->show("success", _("Zone modified successfully"), true);  }

?>