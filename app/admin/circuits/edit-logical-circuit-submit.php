<?php

/**
 * Edit logical circuit result
 ***************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

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

# perm check popup
if($POST->action=="edit") {
    $User->check_module_permissions ("circuits", User::ACCESS_RW, true, false);
}
else {
    $User->check_module_permissions ("circuits", User::ACCESS_RWA, true, false);
}

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "circuitsLogical", $POST->csrf_cookie) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";
# validate action
$Admin->validate_action();

# IDs must be numeric
if($POST->action!="add" && !is_numeric($POST->id)) { $Result->show("danger", _("Invalid ID"), true); }

# Logical circuit ID must be present
if($POST->logical_cid == "") 	{ $Result->show("danger", _('Logical Circuit ID is mandatory').'!', true); }

# Validate to make sure there aren't duplicates of the same circuit in the list of circuit ids
# Create list of member circuit IDs for mapping
$POST->circuit_list = str_replace("undefined.", "", $POST->circuit_list);
$id_list = $POST->circuit_list!=="" ? pf_explode("." , rtrim($POST->circuit_list,".")) : [];
if(sizeof($id_list ) != sizeof(array_unique($id_list))){  $Result->show("danger", _('Remove duplicates of circuit').'!', true); }
if($POST->action == "add" && sizeof($id_list) == 0){  $Result->show("danger", _('No circuits selected').'!', true); }

# set update values
$values = array(
				"id"           => $POST->id,
				"logical_cid"  => $POST->logical_cid,
				"purpose"      => $POST->purpose,
				"comments"     => $POST->comments,
				"member_count" => sizeof($id_list)
				);

# fetch custom fields
$update = $Tools->update_POST_custom_fields('circuitsLogical', $POST->action, $POST);
$values = array_merge($values, $update);

# update circuit
if($Admin->object_modify("circuitsLogical", $POST->action, "id", $values))
{
	// If this is a new circuit, save id of insert and process
	if($POST->id == "") {
		if ($Admin->lastId==null) {
			$Result->show("danger", _('Logical circuit added, but failed to create mapping').'!', true);
		}
		else {
			$POST->id = $Admin->lastId;
		}
	}

	// delete
	if($POST->action != 'add') {
		try { $Database->deleteObjectsByIdentifier("circuitsLogicalMapping", "logicalCircuit_id", $POST->id); }
		catch (Exception $e) {
			$Result->show("danger", _("Error dropping mapping: ").$e->getMessage());
		}
	}

	// add mapping
	// Grab list of IDs and create list
	$order = 0;

	if(sizeof($id_list)>0) {
		foreach($id_list as $member_id) {
			// insert values
			$values = [
						"logicalCircuit_id" => $POST->id,
						"circuit_id"        => $member_id,
						"order"             => $order
					  ];

			// insert to mapping
			if(!$Admin->object_modify("circuitsLogicalMapping", "add", "id", $values)) {
				$Result->show("danger", _("Error inserting mapping."));
			}
			$order++;
		}
		// all ok
		$Result->show("success", _("Logical Circuit")." ". $User->get_post_action()." "._("successful")."!", false);
	}
	else {
		if($POST->action == "delete"){
        $Result->show("success", _("Logical Circuit")." ". $User->get_post_action()." "._("successful")."!", false);
		}
		else{
        $Result->show("warning", _("No circuits selected")."!", false);
		}
	}
}
