<?php

/**
 * Edit provider result
 ***************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

// initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database, false);
$Tools	 	= new Tools ($Database);
$Result 	= new Result ();

// verify that user is logged in
$User->check_user_session();
// verify module permissions
if($_POST['action']=="edit") {
	$User->check_module_permissions ("customers", User::ACCESS_RW, true, false);
}
else {
	$User->check_module_permissions ("customers", User::ACCESS_RWA, true, false);
}

// check maintaneance mode
$User->check_maintaneance_mode ();

// validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "customer", $_POST['csrf_cookie']) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";
// validate action
$Admin->validate_action ($_POST['action'], true);
// get modified details
$customer = $Admin->strip_input_tags($_POST);


/**
 * Validations
 */

// IDs must be numeric
if($customer['action']!="add" && !is_numeric($customer['id']))					{ $Result->show("danger", _("Invalid ID"), true); }

// add / edit validations
if ($customer['action']!="delete") {
	// check strings
	if(strlen($_POST['title'])<3)		{ $Result->show("danger", _("Invalid Title"), true); }
	if(strlen($_POST['address'])<3)		{ $Result->show("danger", _("Invalid Address"), true); }
	if(strlen($_POST['city'])<3)		{ $Result->show("danger", _("Invalid City"), true); }
	if(strlen($_POST['state'])<3)		{ $Result->show("danger", _("Invalid State"), true); }
	// validate postcode
	if(!$Tools->validate_postcode ($_POST['postcode'], $_POST['state'])) { $Result->show("danger", _("Invalid Postcode"), true); }
}

// fetch custom fields
$custom = $Tools->fetch_custom_fields('customers');
if(sizeof($custom) > 0) {
	foreach($custom as $myField) {
		//booleans can be only 0 and 1!
		if($myField['type']=="tinyint(1)") {
			if($customer[$myField['name']]>1) {
				$customer[$myField['name']] = 0;
			}
		}
		//not null!
		if($myField['Null']=="NO" && strlen($customer[$myField['name']])==0) { $Result->show("danger", $myField['name']." "._("can not be empty!"), true); }
		// save to update array
		$update[$myField['name']] = $customer[$myField['name']];
	}
}


/**
 * Update database
 */

// set update values
$values = array(
				"id"             => $customer["id"],
				"title"          => $customer["title"],
				"address"        => $customer["address"],
				"postcode"       => $customer["postcode"],
				"city"           => $customer["city"],
				"state"          => $customer["state"],
				"contact_person" => $customer["contact_person"],
				"contact_phone"  => $customer["contact_phone"],
				"contact_mail"   => $customer["contact_mail"],
				"note"           => $customer["note"]
				);
// custom fields
if(isset($update)) {
	$values = array_merge($values, $update);
}

// set lat lng
$OSM = new OpenStreetMap($Database);
$latlng = $OSM->get_latlng_from_address ($_POST['address'].", ".$_POST['postcode']." ".$_POST['city'].", ".$_POST['state']);
if($latlng['lat']!=NULL && $latlng['lng']!=NULL) {
    $values['lat']  = $latlng['lat'];
    $values['long'] = $latlng['lng'];
}
else {
	if (!Config::ValueOf('offline_mode')) {
		$Result->show("warning", _('Failed to update location lat/lng from Nominatim').".<br>".escape_input($latlng['error']), false);
	}
}

// update customer
if(!$Admin->object_modify("customers", $customer['action'], "id", $values))	{}
else {
    $Result->show("success", _("Customer")." ".$customer["action"]." "._("successful").'!', false);
}
