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
$firewallZoneSettings = db_json_decode($User->settings->firewallZoneSettings,true);

# validations
# validate the action type
if($POST->action != 'add' && $POST->action != 'delete' && $POST->action != 'edit'){
	$Result->show("danger", _("Invalid action."), true);
}

# check the zone name. valid values are alphanumeric characters and special characters like ".-_ "
if($POST->zone && !preg_match('/^[0-9a-z.\-_ ]+$/i',$POST->zone)) {
	$Result->show("danger", _("Invalid zone name value."), true);
}

if($firewallZoneSettings['zoneGenerator']=="2")
if(is_blank($POST->zone) || strlen($POST->zone)>$firewallZoneSettings['zoneLength']) {
	$Result->show("danger", _("Invalid zone name length."), true);
}

# check the zone indicator ID. valid values are 0 or 1.
if($POST->indicator && !preg_match('/^[0-1]$/i',$POST->indicator)) {
	$Result->show("danger", _("Invalid indicator ID."), true);
}

# check the generator value. valid value: integer
if($POST->generator && !preg_match('/^[0-9]+$/i',$POST->generator)) {
	$Result->show("danger", _("Invalid generator ID."), true);
}

# check the padding value. valid value: on or off
if($POST->padding && !preg_match('/^(on|off)$/i',$POST->padding)) {
	$Result->show("danger", _("Invalid padding setting."), true);
}

# transform the padding checkbox values into 1 or 0
if($POST->generator != 2) {
	if($POST->padding) {
		$padding = 1;
	} else {
		$padding = 0;
	}
}

# transform description to valid value
$description = trim(htmlspecialchars($POST->description));

# generate a unique zone name if the generator is set to decimal or hex
if (!$POST->zone && $POST->action == 'add')  {
	if(!$zone=$Zones->generate_zone_name()){
		$Result->show("danger",  _("Cannot generate zone name"), true);
	}
} else {
	$zone = $POST->zone;
}

# validate the zone name if text mode is enabled
if ($POST->generator == 2 ) {
	$textSettings = array ( $POST->zone,$POST->id);
	if(!$zone=$Zones->generate_zone_name($textSettings)){
		$Result->show("danger",  _("Cannot validate zone name"), true);
	}
}

# build the query parameter arrary
if($POST->generator != 2 && $POST->action == 'edit') {
	$values = array('id' => $POST->id,
					'indicator' => $POST->indicator,
					'padding' => $padding ?? 0,
					'description' => $description,
					'network' => $POST->network
					);
}
else {
	$values = array('id' => $POST->id,
					'generator' => $POST->generator,
					'zone' => $zone,
					'indicator' => $POST->indicator,
					'padding' => $padding ?? 0,
					'description' => $description,
					'network' => $POST->network
					);
}

# update
if(!$Zones->modify_zone($POST->action,$values))	{ $Result->show("danger",  _("Cannot add zone"), true); }
else 												{ $Result->show("success", _("Zone modified successfully"), true);  }

