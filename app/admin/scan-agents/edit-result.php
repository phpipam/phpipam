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
# check if site is demo
$User->is_demo();
# check maintaneance mode
$User->check_maintaneance_mode ();

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "agent", $POST->csrf_cookie) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

/* checks */
$error = array();

# for edit check old details
if ($POST->action=="edit" || $POST->action=="delete") {
	# old
	$agent_old = $Admin->fetch_object ("scanAgents", "id", $POST->id);
	// invalid id
	if($agent_old===false)	{ $error[] = _("Invalid agent Id"); }
	// remove type and code if direct
	if (@$agent_old->type=="direct") {
		unset($POST->type, $POST->code);
	}
}

# die if direct and delete
if (@$agent_old->type=="direct" && $POST->action=="delete") {
	$Result->show("danger", _("Cannot remove localhost scan agent"),true);
}

# checks for edit / add
if($POST->action!="delete") {
	# code must be exactly 32 chars long and alfanumeric if app_security = crypt
	if(@$agent_old->type!="direct") {
	if(strlen($POST->code)!=32 || !preg_match("#^[a-zA-Z0-9-_=]+$#", $POST->code))		{ $error[] = _("Invalid agent code"); }
	}
	# name must be more than 2 and alphanumberic
	if(is_blank($POST->name))										{ $error[] = _("Invalid agent name"); }
}

# die if errors
if(sizeof($error) > 0) {
	$Result->show("danger", $error, true);
}
else {
	# create array of values for modification
	$values = array("id"=>$POST->id,
					"name"=>$POST->name,
					"description"=>$POST->description,
					"code"=>$POST->code,
					"type"=>$POST->type
					);
	# null
	$values = $Admin->remove_empty_array_fields ($values);

	# execute
	if(!$Admin->object_modify("scanAgents", $POST->action, "id", $values)) 	{ $Result->show("danger",  _("Agent ".$POST->action." error"), true); }
	else 																		{ $Result->show("success", _("Agent ".$POST->action." success"), false); }

	# delete - unset scanning in all subnets
	if ($POST->action=="delete") {
		$query = "update `subnets` set `scanAgent`=0, `pingSubnet`=0, `discoverSubnet`=0 where `scanAgent` = ?;";

		try { $Database->runQuery($query, array($POST->id)); }
		catch (Exception $e) {
			$Result->show("danger", _("Error: ").$e->getMessage());
			return false;
		}
		// references removed
		$Result->show("info", _("Scan agent references removed"));
	}
}