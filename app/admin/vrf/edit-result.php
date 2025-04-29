<?php

/**
 * Script to edit VRF
 ***************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database, false);
$Tools	 	= new Tools ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();
# check maintaneance mode
$User->check_maintaneance_mode ();
# perm check popup
if($POST->action=="edit") {
    $User->check_module_permissions ("vrf", User::ACCESS_RW, true, true);
}
else {
    $User->check_module_permissions ("vrf", User::ACCESS_RWA, true, true);
}

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "vrf", $POST->csrf_cookie) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# fetch custom fields
$custom = $Tools->fetch_custom_fields('vrf');


# Hostname must be present!
if($POST->name == "") { $Result->show("danger", _("Name is mandatory"), true); }

// set sections
foreach($POST as $key=>$line) {
	if (!is_blank(strstr($key,"section-"))) {
		$key2 = str_replace("section-", "", $key);
		$temp[] = $key2;
		unset($POST->{$key});
	}
}
# glue sections together
$POST->sections = isset($temp) ? implode(";", $temp) : null;

# set update array
$values = array(
				"vrfId"       =>$POST->vrfId,
				"name"        =>$POST->name,
				"rd"          =>$POST->rd,
				"sections"    =>$POST->sections,
				"description" =>$POST->description
				);
# append custom
if(sizeof($custom) > 0) {
	foreach($custom as $myField) {
		# replace possible ___ back to spaces!
		$myField['nameTest']      = str_replace(" ", "___", $myField['name']);
		if(isset($POST->{$myField['nameTest']})) { $values[$myField['name']] = $POST->{$myField['nameTest']};}
	}
}
# append customerId
if($User->settings->enableCustomers=="1") {
	if (is_numeric($POST->customer_id)) {
		if ($POST->customer_id>0) {
			$values['customer_id'] = $POST->customer_id;
		}
		else {
			$values['customer_id'] = null;
		}
	}
}
# update
if(!$Admin->object_modify("vrf", $POST->action, "vrfId", $values)) {
    $Result->show("danger", _("Failed to")." ".$User->get_post_action()." "._("VRF").'!', true);
}
else {
    $Result->show("success", _("VRF")." ".$User->get_post_action()." "._("successful").'!', false);
}

# remove all references if delete
if($POST->action=="delete") { $Admin->remove_object_references ("subnets", "vrfId", $POST->vrfId); }
