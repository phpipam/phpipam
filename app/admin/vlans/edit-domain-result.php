<?php

/**
 * Edit switch result
 ***************************/

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Tools	 	= new Tools ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# validate csrf cookie
$_POST['csrf_cookie']==$_SESSION['csrf_cookie'] ? :                      $Result->show("danger", _("Invalid CSRF cookie"), true);

# we cannot delete default domain
if(@$_POST['id']==1 && $_POST['action']=="delete")						{ $Result->show("danger", _("Default domain cannot be deleted"), true); }
// ID must be numeric
if($_POST['action']!="add" && !is_numeric($_POST['id']))				{ $Result->show("danger", _("Invalid ID"), true); }
// Hostname must be present
if(@$_POST['name'] == "") 												{ $Result->show("danger", _('Name is mandatory').'!', true); }


// set sections
if(@$_POST['id']!=1) {
	foreach($_POST as $key=>$line) {
		if (strlen(strstr($key,"section-"))>0) {
			$key2 = str_replace("section-", "", $key);
			$temp[] = $key2;
			unset($_POST[$key]);
		}
	}
	# glue sections together
	$_POST['permissions'] = sizeof($temp)>0 ? implode(";", $temp) : null;
}
else {
	$_POST['permissions'] = "";
}

# set update values
$values = array("id"=>@$_POST['id'],
				"name"=>@$_POST['name'],
				"description"=>@$_POST['description'],
				"permissions"=>@$_POST['permissions']
				);

# update domain
if(!$Admin->object_modify("vlanDomains", $_POST['action'], "id", $values))	{}
else																		{ $Result->show("success", _("Domain $_POST[action] successfull").'!', false); }

# if delete move all vlans to default domain!
if($_POST['action']=="delete") {
	$Admin->update_object_references ("vlans", "domainId", $_POST['id'], 1);
}

?>