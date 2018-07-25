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

# check permissions
if(!($User->is_admin(false) || $User->user->editCircuits=="Yes")) { $Result->show("danger", _("You are not allowed to modify Circuit details"), true); }


# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "logicalCircuit", $_POST['csrf_cookie']) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";
# validate action
$Admin->validate_action ($_POST['action'], true);
# get modified details
$circuit = $Admin->strip_input_tags($_POST);

# IDs must be numeric
if($circuit['action']!="add" && !is_numeric($circuit['id'])) { $Result->show("danger", _("Invalid ID"), true); }

# Logical circuit ID must be present
if($circuit['logical_cid'] == "") 	{ $Result->show("danger", _('Logical Circuit ID is mandatory').'!', true); }

# Validate to make sure there aren't duplicates of the same circuit in the list of circuit ids
#Create list of member circuit IDs for mapping
$id_list = explode("." , rtrim($_POST['circuit_list'],"."));
if(sizeof($id_list ) != sizeof(array_unique($id_list))){  $Result->show("danger", _('Remove duplicates of circuit').'!', true); }



# fetch custom fields
$custom = $Tools->fetch_custom_fields('logicalCircuit');
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
				"id"        => $circuit['id'],
				"logical_cid"       => $circuit['logical_cid'],
				"purpose"  => $circuit['purpose'],
				"comments"      => $circuit['comments'],
				"member_count" => sizeof($id_list)
				);

# custom fields
if(isset($update)) {
	$values = array_merge($values, $update);
}

# update device
if(!$Admin->object_modify("logicalCircuit", $circuit['action'], "id", $values))	{}

//If this is a new circuit, locate the ID (last_insert_id() would probably be better suited for this)
if($circuit['id'] == ""){
	$query[] = "select";
	$query[] = "id";
	$query[] = "from logicalCircuit";
	if($circuit['id'] == "")
	$query[] = "where logical_cid = '".$_POST['logical_cid']."';";

	//error_log(implode("\n", $query));
	try{ $db_circuit = $Database->getObjectsQuery(implode("\n", $query), array()); }
	catch (Exception $e){
		$Result->show("danger", $e->getMessage(), true);
	}
	//Grab the first row circuit ID
	$circuit['id'] = $db_circuit[0]->id;
}

if($circuit['id'] == ""){
	$Result->show("danger", _('Logical circuit added, but failed to create mapping').'!', true);
}else{
	$drop_query = "DELETE FROM `logicalCircuitMapping` where `logicalCircuit_id` = ".$circuit['id'].";";
	try { $Database->runQuery($drop_query); }
	catch (Exception $e) {
		$Result->show("danger", _("Error dropping mapping: ").$e->getMessage());
	}
	if($circuit['action'] != 'delete'){
		#Grab list of IDs and create list
		$id_list = explode("." , rtrim($_POST['circuit_list'],"."));
		$order = 0;
		foreach($id_list as $member_id){
			$insert_query = "INSERT INTO logicalCircuitMapping (`logicalCircuit_id`,`circuit_id`,`order`) VALUES ('$circuit[id]','$member_id','$order')";
			try { $Database->runQuery($insert_query); }
			catch (Exception $e) {
				$Result->show("danger", _("Error inserting mapping: ").$e->getMessage());
			}
			$order++;
		}
		$Result->show("success", _("Logical Circuit $circuit[action] successful").'!', false);
	}
}
