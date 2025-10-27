<?php

/**
 * Script to edit timeserver sets
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
# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "ns", $POST->csrf_cookie) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";


# Name and primary timeserver must be present!
if ($POST->action!="delete") {

	$m=1;
	$nservers_reindexed = array ();
	# reindex
	foreach($POST as $k=>$v) {
		if(strpos($k, "timesrv-")!==false) {
			$nservers_reindexed["timesrv-".$m] = $v;
			$m++;
			unset($POST->{$k});
		}
	}
	# join
	$POST->read($nservers_reindexed);

	if($POST->name == "") 				{ $Result->show("danger", _("Name is mandatory"), true); }
	if(trim($POST->timesrv-1) == "") 	{ $Result->show("danger", _("Primary timeserver is mandatory"), true); }
}

// merge timeservers
foreach($POST as $key=>$line) {
	if (!is_blank(strstr($key,"timesrv-"))) {
		if (!is_blank($line)) {
			$all_timeservers[] = trim($line);
		}
	}
}
$POST->timesrv1 = isset($all_timeservers) ? implode(";", $all_timeservers) : "";

// set sections
$temp = array();
foreach($POST as $key=>$line) {
	if (!is_blank(strstr($key,"section-"))) {
		$key2 = str_replace("section-", "", $key);
		$temp[] = $key2;
		unset($POST->{$key});
	}
}
# glue sections together
$POST->permissions = sizeof($temp)>0 ? implode(";", $temp) : null;

# set update array
$values = array("id"=>$POST->timeserverid,
				"name"=>$POST->name,
				"permissions"=>$POST->permissions,
				"timesrv1"=>$POST->timesrv1,
				"description"=>$POST->description
				);
# update
if(!$Admin->object_modify("timeservers", $POST->action, "id", $values))	{ $Result->show("danger", _("Failed to")." ".$User->get_post_action()." "._("timeserver set").'!', true); }
else { $Result->show("success", _("Timeserver set")." ".$User->get_post_action()." "._("successful").'!', false); }


# remove all references if delete
if($POST->action=="delete") { $Admin->remove_object_references ("subnets", "timeserverid", $POST->timeserverid); }
