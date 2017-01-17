<?php

/**
 * Script to display language edit
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
# check maintaneance mode
$User->check_maintaneance_mode ();

# strip input tags
$_POST = $Admin->strip_input_tags($_POST);

# validate csrf cookie
$User->csrf_cookie ("validate", "languages", $_POST['csrf_cookie']) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# verify that description is present if action != delete
if($_POST['action'] != "delete" && strlen($_POST['l_code']) < 2)		{ $Result->show("danger", _('Code must be at least 2 characters long'), true); }
if($_POST['action'] != "delete" && strlen($_POST['l_name']) < 2)		{ $Result->show("danger", _('Name must be at least 2 characters long'), true); }

# create update array
$values = array("l_id"=>@$_POST['l_id'],
				"l_code"=>$_POST['l_code'],
				"l_name"=>$_POST['l_name']
				);

# update
if(!$Admin->object_modify("lang", $_POST['action'], "l_id", $values))	{ $Result->show("danger",  _("Language $_POST[action] error"), true); }
else																	{ $Result->show("success", _("Language $_POST[action] success"), true); }
?>