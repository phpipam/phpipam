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
# check maintaneance mode
$User->check_maintaneance_mode ();

# perm check popup
if($_POST['action'] == "edit" || $_POST['action'] == "add") {
    $User->check_module_permissions ("portMaps", 2, true, true);
}
else {
    $User->check_module_permissions ("portMaps", 3, true, true);
}

# strip input tags
$_POST = $Admin->strip_input_tags($_POST);

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "port", $_POST['csrf_cookie']) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# validations
if($_POST['action']=="delete" || $_POST['action']=="edit") {
    if($Admin->fetch_object ('ports', "id", $_POST['id'])===false) {
        $Result->show("danger",  _("Invalid port map object identifier"), false);
    }
}
if($_POST['action']=="add" || $_POST['action']=="edit") {
    // name
    if(strlen($_POST['name'])<1)                                            {  $Result->show("danger",  _("Name must have at least 1 character"), true); }
}

# fetch custom fields
$custom = $Tools->fetch_custom_fields('ports');
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
    "id" => @$_POST['id'],
    "map_id" => $_POST['map_id'],
    "name" => $_POST['name'],
    "number" => $_POST['number'],
    "device" => $_POST['device'],
    "vlan" => $_POST['vlan'],
    "tagged" => $_POST['tagged'],
    "type" => $_POST['type'],
    "poe" => $_POST['poe'],
);

if($values["device"] == 0) {
    $values["device"] = NULL;
}

# custom fields
if (isset($update)) {
    $values = array_merge($values, $update);
}

# execute update
if (!$Admin->object_modify("ports", $_POST['action'], "id", $values)) {
    $Result->show("danger", _("Port $_POST[action] failed"), false);
} else {
    $Result->show("success", _("Port $_POST[action] successful"), false);
}

// remove all references and delete associated ports
if ($_POST['action'] == "delete") {
    //Do nothing on port deletion. No other associations 
}