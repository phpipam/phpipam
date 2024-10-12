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

# fetch custom fields
$custom = $Tools->fetch_custom_fields('circuitProviders');
if(sizeof($custom) > 0) {
	foreach($custom as $myField) {

		//replace possible ___ back to spaces
		$myField['nameTest'] = str_replace(" ", "___", $myField['name']);
		if(isset($POST->{$myField['nameTest']})) { $POST->{$myField['name']} = $POST->{$myField['nameTest']};}

		//booleans can be only 0 and 1!
		if($myField['type']=="tinyint(1)") {
			if($POST->{$myField['name']}>1) {
				$POST->{$myField['name']} = 0;
			}
		}
		//not null!
		if($myField['Null']=="NO" && is_blank($POST->{$myField['name']})) { $Result->show("danger", $myField['name']." "._("can not be empty").'!', true); }

		# save to update array
		$update[$myField['name']] = $POST->{$myField['nameTest']};
	}
}

# set update values
$values = array(
				"id"          => $POST->providerid,
				"name"    	  => $POST->name,
				"description" => $POST->description,
				"contact"     => $POST->contact
				);
# custom fields
if(isset($update)) {
	$values = array_merge($values, $update);
}

# update device
if ($Admin->object_modify("circuitProviders", $POST->action, "id", $values)) {
	$Result->show("success", _("Provider") . " " . $POST->action . " " . _("successful") . '!', false);
}

if($POST->action=="delete"){
	# remove all references
	$Admin->remove_object_references ("circuits", "provider", $values["id"]);
}
