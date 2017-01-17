<?php

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Tools	 	= new Tools ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();
# check maintaneance mode
$User->check_maintaneance_mode ();

# strip input tags
$_POST = $Admin->strip_input_tags($_POST);

# validate csrf cookie
$User->csrf_cookie ("validate", "location", $_POST['csrf_cookie']) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# validations
if($_POST['action']=="delete" || $_POST['action']=="edit") {
    if($Admin->fetch_object ('locations', "id", $_POST['id'])===false) {
        $Result->show("danger",  _("Invalid Location object identifier"), false);
    }
}
if($_POST['action']=="add" || $_POST['action']=="edit") {
    // name
    if(strlen($_POST['name'])<3)                                            {  $Result->show("danger",  _("Name must have at least 3 characters"), true); }
    // lat, long
    if($_POST['action']!=="delete") {
        // lat
        if(strlen($_POST['lat'])>0) {
            if(!preg_match('/^(\-?\d+(\.\d+)?).\s*(\-?\d+(\.\d+)?)$/', $_POST['lat'])) { $Result->show("danger",  _("Invalid Latitude"), true); }
        }
        // long
        if(strlen($_POST['long'])>0) {
            if(!preg_match('/^(\-?\d+(\.\d+)?).\s*(\-?\d+(\.\d+)?)$/', $_POST['long'])) { $Result->show("danger",  _("Invalid Longitude"), true); }
        }

        // fetch latlng
        if(strlen($_POST['lat'])==0 && strlen($_POST['long'])==0 && strlen($_POST['address'])>0) {
            $latlng = $Tools->get_latlng_from_address ($_POST['address']);
            if($latlng['lat']!=NULL && $latlng['lng']!=NULL) {
                $_POST['lat'] = $latlng['lat'];
                $_POST['long'] = $latlng['lng'];
            }
            else {
                $Result->show("warning", _('Failed to update location lat/lng from google'), false);
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
			if($_POST[$myField['name']]>1) {
				$_POST[$myField['name']] = 0;
			}
		}
		//not null!
		if($myField['Null']=="NO" && strlen($_POST[$myField['name']])==0) {
																		{ $Result->show("danger", $myField['name'].'" can not be empty!', true); }
		}
		# save to update array
		$update[$myField['name']] = $_POST[$myField['name']];
	}
}


// set values
$values = array(
    "id"=>@$_POST['id'],
    "name"=>$_POST['name'],
    "address"=>$_POST['address'],
    "lat"=>$_POST['lat'],
    "long"=>$_POST['long'],
    "description"=>$_POST['description']
    );

# custom fields
if(isset($update)) {
	$values = array_merge($values, $update);
}

# execute update
if(!$Admin->object_modify ("locations", $_POST['action'], "id", $values))   { $Result->show("danger",  _("Location $_POST[action] failed"), false); }
else																	    { $Result->show("success", _("Location $_POST[action] successful"), false); }

?>