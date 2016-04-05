<?php

/**
 * Script to display agent edit result
 *************************************/

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# strip input tags
$_POST = $Admin->strip_input_tags($_POST);

# validate csrf cookie
$User->csrf_cookie ("validate", "agent", $_POST['csrf_cookie']) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

/* checks */
$error = array();

# for edit check old details
if ($_POST['action']=="edit" || $_POST['action']=="delete") {
	# old
	$agent_old = $Admin->fetch_object ("scanAgents", "id", $_POST['id']);
	// invalid id
	if($agent_old===false)	{ $error[] = "Invalid agent Id"; }
	// remove type and code if direct
	if (@$agent_old->type=="direct") {
		unset($_POST['type'], $_POST['code']);
	}
}

# die if direct and delete
if (@$agent_old->type=="direct" && $_POST['action']=="delete") {
	$Result->show("danger", _("Cannot remove localhost scan agent"),true);
}

# checks for edit / add
if($_POST['action']!="delete") {
	# code must be exactly 32 chars long and alfanumeric if app_security = crypt
	if(@$agent_old->type!="direct") {
	if(strlen($_POST['code'])!=32 || !ctype_alnum($_POST['code']))		{ $error[] = "Invalid agent code"; }
	}
	# name must be more than 2 and alphanumberic
	if(strlen($_POST['name'])==0)										{ $error[] = "Invalid agent name"; }
}

# die if errors
if(sizeof($error) > 0) {
	$Result->show("danger", $error, true);
}
else {
	# create array of values for modification
	$values = array("id"=>@$_POST['id'],
					"name"=>$_POST['name'],
					"description"=>@$_POST['description'],
					"code"=>@$_POST['code'],
					"type"=>@$_POST['type']
					);
	# null
	$values = $Admin->remove_empty_array_fields ($values);

	# execute
	if(!$Admin->object_modify("scanAgents", $_POST['action'], "id", $values)) 	{ $Result->show("danger",  _("Agent $_POST[action] error"), true); }
	else 																		{ $Result->show("success", _("Agent $_POST[action] success"), false); }

	# delete - unset scanning in all subnets
	if ($_POST['action']=="delete") {
		$query = "update `subnets` set `scanAgent`=0, `pingSubnet`=0, `discoverSubnet`=0 where `scanAgent` = ?;";

		try { $Database->runQuery($query, array($_POST['id'])); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage());
			return false;
		}
		// references removed
		$this->Result->show("info", _("Scan agent references removed"));
	}
}

?>