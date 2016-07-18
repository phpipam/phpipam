<?php

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database, false);
$Tools	 	= new Tools ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# strip input tags
$_POST = $Admin->strip_input_tags($_POST);

# check permissions
if($Tools->check_prefix_permission ($User->user) <2)   { $Result->show("danger", _('You do not have permission to manage PSTN numbers'), true, true); }

# validate csrf cookie
$User->csrf_cookie ("validate", "pstn_number", $_POST['csrf_cookie']) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# validations
if($_POST['action']=="delete" || $_POST['action']=="edit") {
    if($Admin->fetch_object ('pstnNumbers', "id", $_POST['id'])===false) {
        $Result->show("danger",  _("Invalid PSTN number identifier"), false);
    }
}
if($_POST['action']=="add" || $_POST['action']=="edit") {
    // name
    if(strlen($_POST['name'])<3)                                        { $Result->show("danger",  _("Name must have at least 3 characters"), true); }

    // number
    if(!is_numeric($_POST['number']))                                   { $Result->show("danger",  _("Number must be numeric"), true); }

    // check prefix
    $prefix = $Tools->fetch_object("pstnPrefixes", "id", $_POST['prefix']);
    if($prefix===false)                                                 { $Result->show("danger",  _("Invalid prefix"), true); }

    // duplicate check
    if($_POST['action']=="add")
    if ($Tools->check_number_duplicates ($prefix->id, $_POST['number'])){ $Result->show("danger",  _("Duplicate number"), true); }

    // ranges
    $prefix->prefix_raw = $Tools->prefix_normalize ($prefix->prefix);
    $prefix->prefix_raw_start = $Tools->prefix_normalize ($prefix->prefix.$prefix->start);
    $prefix->prefix_raw_stop  = $Tools->prefix_normalize ($prefix->prefix.$prefix->stop);

    // pad number
    $_POST['number'] = str_pad($_POST['number'], (strlen($prefix->prefix_raw_start)-strlen($prefix->prefix_raw)),  "0", STR_PAD_LEFT);

    $_POST['prefix_number'] = $Tools->prefix_normalize ($prefix->prefix.$_POST['number']);

    // number must be inside range
    if (!($_POST['prefix_number'] >= $prefix->prefix_raw_start && $_POST['prefix_number'] <= $prefix->prefix_raw_stop))
                                                                        { $Result->show("danger",  _("Number not inside prefix"), true); }
}

# fetch custom fields
$custom = $Tools->fetch_custom_fields('pstnNumbers');
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
    "prefix"=>$_POST['prefix'],
    "number"=>$_POST['number'],
    "owner"=>$_POST['owner'],
    "state"=>$_POST['state'],
    "deviceId"=>$_POST['deviceId'],
    "description"=>$_POST['description']
    );

# custom fields
if(isset($update)) {
	$values = array_merge($values, $update);
}

# execute update
if(!$Admin->object_modify ("pstnNumbers", $_POST['action'], "id", $values))    { $Result->show("danger",   _("Number $_POST[action] failed"), false); }
else																	       { $Result->show("success", _("Number $_POST[action] successful"), false); }

?>