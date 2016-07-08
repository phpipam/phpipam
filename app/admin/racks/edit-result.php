<?php

/**
 * Edit rack result
 ***************************/

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Tools	 	= new Tools ($Database);
$Racks      = new phpipam_rack ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# validate csrf cookie
$User->csrf_cookie ("validate", "rack", $_POST['csrf_cookie']) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# get modified details
$rack = $Tools->strip_input_tags($_POST);

# ID must be numeric
if($_POST['action']!="add" && !is_numeric($_POST['rackid']))			{ $Result->show("danger", _("Invalid ID"), true); }

# Hostname must be present
if($rack['name'] == "") 											    { $Result->show("danger", _('Name is mandatory').'!', true); }

# rack checks
# validate position and size
if (!is_numeric($rack['size']))                                         { $Result->show("danger", _('Invalid rack size').'!', true); }
# validate rack
if ($rack['action']=="edit") {
    if (!is_numeric($rack['rackid']))                                       { $Result->show("danger", _('Invalid rack identifier').'!', true); }
    $rack_details = $Racks->fetch_rack_details ($rack['rackid']);
    if ($rack_details===false)                                          { $Result->show("danger", _('Rack does not exist').'!', true); }
}
elseif($rack['action']=="delete") {
    if (!is_numeric($rack['rackid']))                                       { $Result->show("danger", _('Invalid rack identifier').'!', true); }
}

# fetch custom fields
$custom = $Tools->fetch_custom_fields('racks');
if(sizeof($custom) > 0) {
	foreach($custom as $myField) {
		//booleans can be only 0 and 1!
		if($myField['type']=="tinyint(1)") {
			if($rack[$myField['name']]>1) {
				$rack[$myField['name']] = 0;
			}
		}
		//not null!
		if($myField['Null']=="NO" && strlen($rack[$myField['name']])==0) {
																		{ $Result->show("danger", $myField['name'].'" can not be empty!', true); }
		}
		# save to update array
		$update[$myField['name']] = $rack[$myField['name']];
	}
}

# set update values
$values = array("id"=>@$rack['rackid'],
				"name"=>@$rack['name'],
				"size"=>@$rack['size'],
				"location"=>@$rack['location'],
				"description"=>@$rack['description']
				);
# custom fields
if(isset($update)) {
	$values = array_merge($values, $update);
}

# update rack
if(!$Admin->object_modify("racks", $_POST['action'], "id", $values))	{}
else																	{ $Result->show("success", _("Rack $rack[action] successfull").'!', false); }

if($_POST['action']=="delete"){
	# remove all references from subnets and ip addresses
	$Admin->remove_object_references ("devices", "rack", $values["id"]);
}

?>
