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


# Name and primary nameserver must be present!
if($_POST['name'] == "") { $Result->show("danger", _("Name is mandatory"), true); }
if($_POST['namesrv1'] == "") { $Result->show("danger", _("Primary nameserver is mandatory"), true); }

// set sections
if(@$_POST['nameserverId']!=1) {
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

# set update array
$values = array("id"=>@$_POST['nameserverId'],
				"name"=>$_POST['name'],
				"namesrv1"=>$_POST['namesrv1'],
				"namesrv2"=>$_POST['namesrv2'],
				"namesrv3"=>$_POST['namesrv3'],
				"description"=>$_POST['description'],
				"permissions"=>$_POST['permissions']
				);
# update
if(!$Admin->object_modify("nameservers", $_POST['action'], "nameserverid", $values))	{ $Result->show("danger",  _("Failed to $_POST[action] nameserver set").'!', true); }
else																	{ $Result->show("success", _("Nameserver set $_POST[action] successfull").'!', false); }


# remove all references if delete
if($_POST['action']=="delete") { $Admin->remove_object_references ("nameservers", "id", $_POST['nameserverId']); }
?>
