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
if($POST->action=="edit") {
    $User->check_module_permissions ("l2dom", User::ACCESS_RW, true, false);
}
else {
    $User->check_module_permissions ("l2dom", User::ACCESS_RWA, true, false);
}

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "vlan_domain", $POST->csrf_cookie) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# we cannot delete default domain
if($POST->id==1 && $POST->action=="delete")						{ $Result->show("danger", _("Default domain cannot be deleted"), true); }
// ID must be numeric
if($POST->action!="add" && !is_numeric($POST->id))				{ $Result->show("danger", _("Invalid ID"), true); }
// Hostname must be present
if($POST->name == "") 												{ $Result->show("danger", _('Name is mandatory').'!', true); }


// set sections
if($POST->id!=1) {
	$temp = [];
	foreach($POST as $key=>$line) {
		if (!is_blank(strstr($key,"section-"))) {
			$key2 = str_replace("section-", "", $key);
			$temp[] = $key2;
			unset($POST->{$key});
		}
	}
	# glue sections together
	$POST->permissions = sizeof($temp)>0 ? implode(";", $temp) : null;
}
else {
	$POST->permissions = "";
}

# set update values
$values = array(
				"id"          =>$POST->id,
				"name"        =>$POST->name,
				"description" =>$POST->description,
				"permissions" =>$POST->permissions
				);

# update domain
if(!$Admin->object_modify("vlanDomains", $POST->action, "id", $values))	{}
else { $Result->show("success", _("Domain")." ".$User->get_post_action()." "._("successful").'!', false); }

# if delete move all vlans to default domain!
if($POST->action=="delete") {
	$Admin->update_object_references ("vlans", "domainId", $POST->id, 1);
}
