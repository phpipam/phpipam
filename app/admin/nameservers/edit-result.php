<?php

/**
 * Script to edit nameserver sets
 ***************************/

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


# Name and primary nameserver must be present!
if ($_POST['action']!="delete") {
	if($_POST['name'] == "") 				{ $Result->show("danger", _("Name is mandatory"), true); }
	if(trim($_POST['namesrv-1']) == "") 	{ $Result->show("danger", _("Primary nameserver is mandatory"), true); }
}

// merge nameservers
foreach($_POST as $key=>$line) {
	if (strlen(strstr($key,"namesrv-"))>0) {
		if (strlen($line)>0) {
			$all_nameservers[] = trim($line);
		}
	}
}
$_POST['namesrv1'] = isset($all_nameservers) ? implode(";", $all_nameservers) : "";

// set sections
foreach($_POST as $key=>$line) {
	if (strlen(strstr($key,"section-"))>0) {
		$key2 = str_replace("section-", "", $key);
		$temp[] = $key2;
		unset($_POST[$key]);
	}
}
# glue sections together
$_POST['permissions'] = sizeof($temp)>0 ? implode(";", $temp) : null;

# set update array
$values = array("id"=>@$_POST['nameserverId'],
				"name"=>$_POST['name'],
				"permissions"=>$_POST['permissions'],
				"namesrv1"=>$_POST['namesrv1'],
				"description"=>$_POST['description']
				);
# update
if(!$Admin->object_modify("nameservers", $_POST['action'], "id", $values))	{ $Result->show("danger",  _("Failed to $_POST[action] nameserver set").'!', true); }
else																		{ $Result->show("success", _("Nameserver set $_POST[action] successfull").'!', false); }


# remove all references if delete
if($_POST['action']=="delete") { $Admin->remove_object_references ("nameservers", "id", $_POST['nameserverId']); }
?>
