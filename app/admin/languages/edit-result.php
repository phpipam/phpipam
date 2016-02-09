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

# validate csrf cookie
$_POST['csrf_cookie']==$_SESSION['csrf_cookie'] ? :                      $Result->show("danger", _("Invalid CSRF cookie"), true);


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