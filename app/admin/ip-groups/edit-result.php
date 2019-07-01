<?php

/**
 * Script to display agent edit result
 *************************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();
# check maintaneance mode
$User->check_maintaneance_mode ();

# strip input tags
$_POST = $Admin->strip_input_tags($_POST);

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "agent", $_POST['csrf_cookie']) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

/* checks */
$error = array();

# for edit check old details
if ($_POST['action']=="edit" || $_POST['action']=="delete") {
	# old
	$agent_old = $Admin->fetch_object ("ipGroups", "id", $_POST['id']);
	// invalid id
	if($agent_old===false)	{ $error[] = "Invalid agent Id"; }
	// remove type and code if direct
	if (@$agent_old->type=="direct") {
		unset($_POST['type'], $_POST['code']);
	}
}

# checks for edit / add
if ($_POST['action']!="delete") {
	# name must be more than 2 and alphanumberic
	if(strlen($_POST['name'])==0)										{ $error[] = "Invalid agent name"; }
}

# die if errors
if(sizeof($error) > 0) {
	$Result->show("danger", $error, true);
}
else {
    $parents = implode(', ', @$_POST['parents']);
	# create array of values for modification
	$values = [
	    "id"          => @$_POST['id'],
        "name"        => $_POST['name'],
        "description" => @$_POST['description'],
        "type"        => @$_POST['type'],
        "parents"     => $parents
    ];
	# null
	$values = $Admin->remove_empty_array_fields($values);

	# execute
	if (!$Admin->object_modify("ipGroups", $_POST['action'], "id", $values)) {
	    $Result->show("danger",  _("IP Group $_POST[action] error"), true);
	} else {
	    $Result->show("success", _("IP Group $_POST[action] success"), false);
	}

//	# delete - unset scanning in all subnets
//	if ($_POST['action']=="delete") {
//		$query = "update `subnets` set `scanAgent`=0, `pingSubnet`=0, `discoverSubnet`=0 where `scanAgent` = ?;";
//
//		try { $Database->runQuery($query, array($_POST['id'])); }
//		catch (Exception $e) {
//			$Result->show("danger", _("Error: ").$e->getMessage());
//			return false;
//		}
//		// references removed
//		$Result->show("info", _("Scan agent references removed"));
//	}
}

?>