<?php

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

# perm check popup
if($POST->action=="edit") {
    $User->check_module_permissions ("locations", User::ACCESS_RW, true, false);
}
else {
    $User->check_module_permissions ("locations", User::ACCESS_RWA, true, false);
}

# check maintaneance mode
$User->check_maintaneance_mode ();

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "location", $POST->csrf_cookie) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# validations
if($POST->action=="delete" || $POST->action=="edit") {
    if($Admin->fetch_object ('locations', "id", $POST->id)===false) {
        $Result->show("danger",  _("Invalid Location object identifier"), false);
    }
}
if($POST->action=="add" || $POST->action=="edit") {
    // name
    if(is_blank($POST->name))                                            {  $Result->show("danger",  _("Name must have at least 1 character"), true); }
    // lat, long
    if($POST->action!=="delete") {
        // lat
        if(!is_blank($POST->lat)) {
            if(!preg_match('/^(\-?\d+(\.\d+)?).\s*(\-?\d+(\.\d+)?)$/', $POST->lat)) { $Result->show("danger",  _("Invalid Latitude"), true); }
        }
        // long
        if(!is_blank($POST->long)) {
            if(!preg_match('/^(\-?\d+(\.\d+)?).\s*(\-?\d+(\.\d+)?)$/', $POST->long)) { $Result->show("danger",  _("Invalid Longitude"), true); }
        }

        // fetch latlng
        if(is_blank($POST->lat) && is_blank($POST->long) && !is_blank($POST->address)) {
            $OSM = new OpenStreetMap($Database);
            $latlng = $OSM->get_latlng_from_address ($POST->address);
            if(isset($latlng['lat']) && isset($latlng['lng'])) {
                $POST->lat = $latlng['lat'];
                $POST->long = $latlng['lng'];
            }
            else {
                if (!Config::ValueOf('offline_mode')) {
                    $Result->show("warning", _("Failed to update location lat/lng from Nominatim").".<br>".escape_input($latlng['error']), false);
                }
            }
        }
    }
}

# fetch custom fields
$custom = $Tools->fetch_custom_fields('locations');
if(sizeof($custom) > 0) {
	foreach($custom as $myField) {
		//booleans can be only 0 and 1!
		if($myField['type']=="tinyint(1)") {
			if($POST->{$myField['name']}>1) {
				$POST->{$myField['name']} = 0;
			}
		}
		//not null!
		if($myField['Null']=="NO" && is_blank($POST->{$myField['name']})) {
			{ $Result->show("danger", $myField['name']." "._("can not be empty!"), true); }
		}
		# save to update array
		$update[$myField['name']] = $POST->{$myField['name']};
	}
}


// set values
$values = array(
    "id"          =>$POST->id,
    "name"        =>$POST->name,
    "address"     =>$POST->address,
    "lat"         =>$POST->lat,
    "long"        =>$POST->long,
    "description" =>$POST->description
    );

# custom fields
if(isset($update)) {
	$values = array_merge($values, $update);
}

# execute update
if(!$Admin->object_modify ("locations", $POST->action, "id", $values)) {
    $Result->show("danger", _("Location")." ".$User->get_post_action()." "._("failed"), false);
}
else {
    $Result->show("success", _("Location")." ".$User->get_post_action()." "._("successful"), false);
}

// remove all references
if($POST->action=="delete"){
    $Admin->remove_object_references ("circuits", "location1", $values["id"]);
    $Admin->remove_object_references ("circuits", "location2", $values["id"]);
    $Admin->remove_object_references ("subnets", "location", $values["id"]);
    $Admin->remove_object_references ("devices", "location", $values["id"]);
    $Admin->remove_object_references ("racks", "location", $values["id"]);
}
