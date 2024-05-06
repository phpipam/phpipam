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
$Params		= new Params ($Admin->strip_input_tags($_POST));

// verify that user is logged in
$User->check_user_session();
// verify module permissions
if($Params->action=="edit") {
	$User->check_module_permissions ("customers", User::ACCESS_RW, true, false);
}
else {
	$User->check_module_permissions ("customers", User::ACCESS_RWA, true, false);
}

// check maintaneance mode
$User->check_maintaneance_mode ();

// validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "customer", $Params->csrf_cookie) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";
// validate action
$Admin->validate_action();


/**
 * Validations
 */

// IDs must be numeric
if($Params->action!="add" && !is_numeric($Params->id))					{ $Result->show("danger", _("Invalid ID"), true); }

// add / edit validations
if ($Params->action!="delete") {
	// check strings
	if(strlen($Params->title)<3)		{ $Result->show("danger", _("Invalid Title"), true); }
	if(strlen($Params->address)<3)		{ $Result->show("danger", _("Invalid Address"), true); }
	if(strlen($Params->city)<3)			{ $Result->show("danger", _("Invalid City"), true); }
	if(strlen($Params->state)<3)		{ $Result->show("danger", _("Invalid State"), true); }
	// validate postcode
	if(!$Tools->validate_postcode ($Params->postcode, $Params->state)) { $Result->show("danger", _("Invalid Postcode"), true); }
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
		if($myField['Null']=="NO" && is_blank($customer[$myField['name']])) { $Result->show("danger", $myField['name']." "._("can not be empty!"), true); }
		// save to update array
		$update[$myField['name']] = $customer[$myField['name']];
	}
}


/**
 * Update database
 */

// set update values
$values = array(
				"id"             => $Params->id,
				"title"          => $Params->title,
				"address"        => $Params->address,
				"postcode"       => $Params->postcode,
				"city"           => $Params->city,
				"state"          => $Params->state,
				"contact_person" => $Params->contact_person,
				"contact_phone"  => $Params->contact_phone,
				"contact_mail"   => $Params->contact_mail,
				"note"           => $Params->note
				);
// custom fields
if(isset($update)) {
	$values = array_merge($values, $update);
}

// set lat lng
$OSM = new OpenStreetMap($Database);
$latlng = $OSM->get_latlng_from_address ($Params->address.", ".$Params->postcode." ".$Params->city.", ".$Params->state);
if(isset($latlng['lat']) && isset($latlng['lng'])) {
    $values['lat']  = $latlng['lat'];
    $values['long'] = $latlng['lng'];
}
else {
	if (!(Config::ValueOf('offline_mode') || Config::ValueOf('disable_geoip_lookups'))) {
		$Result->show("warning", _('Failed to update location lat/lng from Nominatim').".<br>".escape_input($latlng['error']), false);
	}
}

// update customer
if($Admin->object_modify("customers", $Params->action, "id", $values)) {
    $Result->show("success", _("Customer")." ".$Params->action." "._("successful").'!', false);
}
