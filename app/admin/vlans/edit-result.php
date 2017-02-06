<?php

/**
 * Script to edit VLAN details
 *******************************/

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database, false);
$Tools	 	= new Tools ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();
# check maintaneance mode
$User->check_maintaneance_mode ();

# make sue user can edit
if ($User->is_admin(false)==false && $User->user->editVlan!="Yes") {
    $Result->show("danger", _("Not allowed to change VLANs"), true, true);
}

# strip input tags
$_POST = $Admin->strip_input_tags($_POST);

# validate csrf cookie
$User->csrf_cookie ("validate", "vlan", $_POST['csrf_cookie']) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# fetch custom fields
$custom = $Tools->fetch_custom_fields('vlans');

//if it already exist die
if($User->settings->vlanDuplicate==0 && $_POST['action']=="add") {
	$check_vlan = $Admin->fetch_multiple_objects ("vlans", "domainId", $_POST['domainId'], "vlanId");
	if($check_vlan!==false) {
		foreach($check_vlan as $v) {
			if($v->number == $_POST['number']) {
																			{ $Result->show("danger", _("VLAN already exists"), true); }
			}
		}
	}
}

// if unique required
if (isset($_POST['unique'])) {
	if ($_POST['unique']=="on") {
		if ($Tools->fetch_object ("vlans", "number", $_POST['number'])!==false) { $Result->show("danger", _("VLAN already exists in another domain!"), true); }
	}
}

//if number too high
if($_POST['number']>$User->settings->vlanMax && $_POST['action']!="delete")	{ $Result->show("danger", _('Highest possible VLAN number is ').$settings['vlanMax'].'!', true); }
if($_POST['action']=="add") {
	if($_POST['number']<0)													{ $Result->show("danger", _('VLAN number cannot be negative').'!', true); }
	elseif(!is_numeric($_POST['number']))									{ $Result->show("danger", _('Not number').'!', true); }
}
if(strlen($_POST['name'])==0)												{ $Result->show("danger", _('Name is required').'!', true); }


# formulate update query
$values = array("vlanId"=>@$_POST['vlanId'],
				"number"=>$_POST['number'],
				"name"=>$_POST['name'],
				"description"=>@$_POST['description'],
				"domainId"=>$_POST['domainId']
				);
# append custom
if(sizeof($custom) > 0) {
	foreach($custom as $myField) {
		# replace possible ___ back to spaces!
		$myField['nameTest']      = str_replace(" ", "___", $myField['name']);
		if(isset($_POST[$myField['nameTest']])) { $values[$myField['name']] = @$_POST[$myField['nameTest']];}
	}
}


# update
if(!$Admin->object_modify("vlans", $_POST['action'], "vlanId", $values))	{ $Result->show("danger",  _("Failed to $_POST[action] VLAN").'!', true); }
else																		{ $Result->show("success", _("VLAN $_POST[action] successfull").'!', false); }

# remove all references if delete
if($_POST['action']=="delete") { $Admin->remove_object_references ("subnets", "vlanId", $_POST['vlanId']); }

# print value for on the fly
if($_POST['action']=="add")	   { print '<p id="vlanidforonthefly"    style="display:none">'.$Admin->lastId.'</p>'; }

?>