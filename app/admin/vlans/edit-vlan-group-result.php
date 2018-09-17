<?php

/**
 * Script to edit VLAN group details
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
$User->Crypto->csrf_cookie ("validate", "vlan", $_POST['csrf_cookie']) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

if($_POST['action']!="delete") {
	if(!is_numeric($_POST['firstVlan']))                                                                                    { $Result->show("danger", _('First VLAN is not a number').'!', true); }
	if(!is_numeric($_POST['lastVlan']))											{ $Result->show("danger", _('Last VLAN is not a number').'!', true); }
}

//if number too high or too low
if($_POST['lastVlan']<1 || $_POST['firstVlan']<1)                                                                               { $Result->show("danger", _('VLAN number must be higher than 0!'), true); }
if($_POST['lastVlan']>$User->settings->vlanMax && $_POST['action']!="delete")                                                   { $Result->show("danger", _('Highest possible VLAN number is ').$settings['vlanMax'].'!', true); }

//if last vlan lower than first vlan
if($_POST['lastVlan'] < $_POST['firstVlan'] && $_POST['action']!="delete")                                                      { $Result->show("danger", _('First VLAN is higher than Last VLAN!'), true); }

if(strlen($_POST['name'])==0)													{ $Result->show("danger", _('Name is required').'!', true); }

//chek if overlaps with existing
if ((!isset($_POST["overrideOverlapCheck"]) && $_POST["action"] == "add") || (!isset($_POST["overrideOverlapCheck"]) && $_POST["action"] == "edit")) {
	$Groups = $Tools->fetch_multiple_objects("vlanGroups", "domainId", $_POST["domainId"]);
	if($Groups){
		foreach ($Groups as $group) {
			if ($_POST["id"] != $group->id) {
				if($_POST["firstVlan"] >= $group->firstVlan  && $_POST["firstVlan"] <= $group->lastVlan)	{ $Result->show("danger", "Overlaps with an already existing VLAN Group!", true); }
				if($_POST["lastVlan"] >= $group->firstVlan && $_POST["lastVlan"] <= $group->lastVlan) 		{ $Result->show("danger", "Overlaps with an already existing VLAN Group!", 	true); }
				if ($_POST["firstVlan"] <= $group->firstVlan && $_POST["lastVlan"] >= $group->lastVlan) 	{ $Result->show("danger", "Overlaps with an already existing VLAN Group!", 	true); }
			}
		}
	}
}

# formulate update query
$values = array("name"=>$_POST["name"],
				"firstVlan"=>$_POST["firstVlan"],
				"lastVlan"=>$_POST["lastVlan"],
				"domainId"=>$_POST["domainId"],
				"id"=>$_POST["id"]
				);

# update
if(!$Admin->object_modify("vlanGroups", $_POST['action'], "id", $values))	{ $Result->show("danger",  _("Failed to $_POST[action] VLAN Group").'!', true); }
//else																	{ $Result->show("success", _("VLAN Group $_POST[action] successfull").'!', false); }
