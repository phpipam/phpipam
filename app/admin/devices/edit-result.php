<?php

/**
 * Edit switch result
 ***************************/

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Tools	 	= new Tools ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();


# get modified details
$device = $_POST;


# ID must be numeric
if($_POST['action']!="add" && !is_numeric($_POST['switchId']))			{ $Result->show("danger", _("Invalid ID"), true); }

# available devices set
foreach($device as $key=>$line) {
	if (strlen(strstr($key,"section-"))>0) {
		$key2 = str_replace("section-", "", $key);
		$temp[] = $key2;

		unset($device[$key]);
	}
}
# glue sections together
$device['sections'] = sizeof($temp)>0 ? implode(";", $temp) : null;

# Hostname must be present
if($device['hostname'] == "") 											{ $Result->show("danger", _('Hostname is mandatory').'!', true); }


# fetch custom fields
$custom = $Tools->fetch_custom_fields('devices');
if(sizeof($custom) > 0) {
	foreach($custom as $myField) {
		//booleans can be only 0 and 1!
		if($myField['type']=="tinyint(1)") {
			if($device[$myField['name']]>1) {
				$device[$myField['name']] = 0;
			}
		}
		//not null!
		if($myField['Null']=="NO" && strlen($device[$myField['name']])==0) {
																		{ $Result->show("danger", $myField['name'].'" can not be empty!', true); }
		}
		# save to update array
		$update[$myField['name']] = $device[$myField['name']];
	}
}

# set update values
$values = array("id"=>@$device['switchId'],
				"hostname"=>@$device['hostname'],
				"ip_addr"=>@$device['ip_addr'],
				"type"=>@$device['type'],
				"vendor"=>@$device['vendor'],
				"model"=>@$device['model'],
				"description"=>@$device['description'],
				"sections"=>@$device['sections']
				);
# custom fields
if(isset($update)) {
	$values = array_merge($values, $update);
}

# update device
if(!$Admin->object_modify("devices", $_POST['action'], "id", $values))	{}
else																	{ $Result->show("success", _("Device $device[action] successfull").'!', true); }

?>