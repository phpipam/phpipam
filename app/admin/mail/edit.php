<?php

/**
 *	Mail settings
 **************************/

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

# set update query
$values = array("id"=>1,
				"mtype"=>$_POST['mtype'],
				"msecure"=>@$_POST['msecure'],
				"mauth"=>@$_POST['mauth'],
				"mserver"=>@$_POST['mserver'],
				"mport"=>@$_POST['mport'],
				"muser"=>@$_POST['muser'],
				"mpass"=>@$_POST['mpass'],
				"mAdminName"=>@$_POST['mAdminName'],
				"mAdminMail"=>@$_POST['mAdminMail']
				);

# update
if(!$Admin->object_modify("settingsMail", "edit", "id", $values))	{ $Result->show("danger",  _('Cannot update settings').'!', true); }
else																{ $Result->show("success", _('Settings updated successfully')."!", true); }
?>