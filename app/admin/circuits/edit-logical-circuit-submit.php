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
if($_POST['action']=="edit") {
    $User->check_module_permissions ("circuits", 2, true, false);
}
else {
    $User->check_module_permissions ("circuits", 3, true, false);
}

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "circuitsLogical", $_POST['csrf_cookie']) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";
# validate action
$Admin->validate_action ($_POST['action'], true);
# get modified details
$circuit = $Admin->strip_input_tags($_POST);

# IDs must be numeric
if($circuit['action']!="add" && !is_numeric($circuit['id'])) { $Result->show("danger", _("Invalid ID"), true); }

# Logical circuit ID must be present
if($circuit['logical_cid'] == "") 	{ $Result->show("danger", _('Logical Circuit ID is mandatory').'!', true); }

# Validate to make sure there aren't duplicates of the same circuit in the list of circuit ids
# Create list of member circuit IDs for mapping
$_POST['circuit_list'] = str_replace("undefined.", "", $_POST['circuit_list']);
$id_list = $_POST['circuit_list']!=="" ? explode("." , rtrim($_POST['circuit_list'],".")) : [];
if(sizeof($id_list ) != sizeof(array_unique($id_list))){  $Result->show("danger", _('Remove duplicates of circuit').'!', true); }
if(($circuit['action'] == "add" && sizeof($id_list ) == 0)){  $Result->show("danger", _('No circuits selected').'!', true); }

# fetch custom fields
$custom = $Tools->fetch_custom_fields('circuitsLogical');
if(sizeof($custom) > 0) {
	foreach($custom as $myField) {
		//replace possible ___ back to spaces
		$myField['nameTest'] = str_replace(" ", "___", $myField['name']);
		if(isset($circuit[$myField['nameTest']])) { $circuit[$myField['name']] = $circuit[$myField['nameTest']];}
		//booleans can be only 0 and 1!
		if($myField['type']=="tinyint(1)") {
			if($circuit[$myField['name']]>1) {
				$circuit[$myField['name']] = 0;
			}
		}
		//not null!
		if($myField['Null']=="NO" && strlen($circuit[$myField['name']])==0) { $Result->show("danger", $myField['name'].'" can not be empty!', true); }
		# save to update array
		$update[$myField['name']] = $circuit[$myField['nameTest']];
	}
}

# set update values
$values = array(
				"id"           => $circuit['id'],
				"logical_cid"  => $circuit['logical_cid'],
				"purpose"      => $circuit['purpose'],
				"comments"     => $circuit['comments'],
				"member_count" => sizeof($id_list)
				);

# custom fields
if(isset($update)) {
	$values = array_merge($values, $update);
}

# update circuit
if(!$Admin->object_modify("circuitsLogical", $circuit['action'], "id", $values)) {}
else {
	// If this is a new circuit, save id of insert and process
	if($circuit['id'] == "") {
		if ($Admin->lastId==null) {
			$Result->show("danger", _('Logical circuit added, but failed to create mapping').'!', true);
		}
		else {
			$circuit['id'] = $Admin->lastId;
		}
	}

	// delete
	if($circuit['action'] != 'add') {
		try { $Database->deleteObjectsByIdentifier("circuitsLogicalMapping", "logicalCircuit_id", $circuit['id']); }
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
						"logicalCircuit_id" => $circuit['id'],
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
		$Result->show("success", _("Logical Circuit $circuit[action] successful").'!', false);
	}
	else {
		if($circuit['action'] == "delete"){
        		$Result->show("success", _("Logical Circuit $circuit[action] successful").'!', false);
		}
		else{
        		$Result->show("warning", _("No circuits selected").'!', false);
		}
	}
}
