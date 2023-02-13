<?php

/**
 * Script to edit nameserver sets
 ***************************/

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
$User->Crypto->csrf_cookie ("validate", "ns", $_POST['csrf_cookie']) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";


# Name and primary nameserver must be present!
if ($_POST['action']!="delete") {

	$m=1;
	$nservers_reindexed = array ();
	# reindex
	foreach($_POST as $k=>$v) {
		if(strpos($k, "namesrv-")!==false) {
			$nservers_reindexed["namesrv-".$m] = $v;
			$m++;
			unset($_POST[$k]);
		}
	}
	# join
	$_POST = array_merge($_POST, $nservers_reindexed);

	if($_POST['name'] == "") 				{ $Result->show("danger", _("Name is mandatory"), true); }
	if(trim($_POST['namesrv-1']) == "") 	{ $Result->show("danger", _("Primary nameserver is mandatory"), true); }
}

// merge nameservers
foreach($_POST as $key=>$line) {
	if (!is_blank(strstr($key,"namesrv-"))) {
		if (!is_blank($line)) {
			$all_nameservers[] = trim($line);
		}
	}
}
$_POST['namesrv1'] = isset($all_nameservers) ? implode(";", $all_nameservers) : "";

// set sections
foreach($_POST as $key=>$line) {
	if (!is_blank(strstr($key,"section-"))) {
		$key2 = str_replace("section-", "", $key);
		$temp[] = $key2;
		unset($_POST[$key]);
	}
}
# glue sections together
$_POST['permissions'] = sizeof($temp)>0 ? implode(";", $temp) : null;

# set update array
$values = array("id"=>@$_POST['nameserverid'],
				"name"=>$_POST['name'],
				"permissions"=>$_POST['permissions'],
				"namesrv1"=>$_POST['namesrv1'],
				"description"=>$_POST['description']
				);
# update
if(!$Admin->object_modify("nameservers", $_POST['action'], "id", $values))	{ $Result->show("danger", _("Failed to")." ".$_POST["action"]." "._("nameserver set").'!', true); }
else { $Result->show("success", _("Nameserver set")." ".$_POST["action"]." "._("successful").'!', false); }


# remove all references if delete
if($_POST['action']=="delete") { $Admin->remove_object_references ("subnets", "nameserverid", $_POST['nameserverid']); }
