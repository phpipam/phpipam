<?php

/**
 * Edit switch result
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
if($_POST['action']=="edit") {
    $User->check_module_permissions ("vlan", 2, true, false);
}
else {
    $User->check_module_permissions ("vlan", 3, true, false);
}

# strip input tags
$_POST = $Admin->strip_input_tags($_POST);

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "vlan_domain", $_POST['csrf_cookie']) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# we cannot delete default domain
if(@$_POST['id']==1 && $_POST['action']=="delete")						{ $Result->show("danger", _("Default domain cannot be deleted"), true); }
// ID must be numeric
if($_POST['action']!="add" && !is_numeric($_POST['id']))				{ $Result->show("danger", _("Invalid ID"), true); }
// Hostname must be present
if(@$_POST['name'] == "") 												{ $Result->show("danger", _('Name is mandatory').'!', true); }


// set sections
if(@$_POST['id']!=1) {
	$temp = [];
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
$values = array(
				"id"          =>@$_POST['id'],
				"name"        =>@$_POST['name'],
				"description" =>@$_POST['description'],
				"permissions" =>@$_POST['permissions']
				);

# update domain
if(!$Admin->object_modify("vlanDomains", $_POST['action'], "id", $values))	{}
else																		{ $Result->show("success", _("Domain $_POST[action] successfull").'!', false); }

# if delete move all vlans to default domain!
if($_POST['action']=="delete") {
	$Admin->update_object_references ("vlans", "domainId", $_POST['id'], 1);
}