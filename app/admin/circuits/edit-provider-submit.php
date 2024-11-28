<?php
/**
 * Edit provider result
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
    $User->check_module_permissions ("circuits", User::ACCESS_RW, true, false);
}
else {
    $User->check_module_permissions ("circuits", User::ACCESS_RWA, true, false);
}

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "provider", $POST->csrf_cookie) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";
# validate action
$Admin->validate_action();
# ID must be numeric
if($POST->action!="add" && !is_numeric($POST->providerid))			{ $Result->show("danger", _("Invalid ID"), true); }

# Hostname must be present
if($POST->name == "") 												{ $Result->show("danger", _('Name is mandatory').'!', true); }

# set update values
$values = array(
				"id"          => $POST->providerid,
				"name"    	  => $POST->name,
				"description" => $POST->description,
				"contact"     => $POST->contact
				);

# fetch custom fields
$update = $Tools->update_POST_custom_fields('circuitProviders', $POST->action, $POST);
$values = array_merge($values, $update);

# update device
if ($Admin->object_modify("circuitProviders", $POST->action, "id", $values)) {
	$Result->show("success", _("Provider") . " " . $User->get_post_action() . " " . _("successful") . '!', false);
}

if($POST->action=="delete"){
	# remove all references
	$Admin->remove_object_references ("circuits", "provider", $values["id"]);
}
