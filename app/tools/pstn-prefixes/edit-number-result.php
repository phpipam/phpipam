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

# perm check
$User->check_module_permissions ("pstn", User::ACCESS_RW, true, false);

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "pstn_number", $POST->csrf_cookie) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# validations
if($POST->action=="delete" || $POST->action=="edit") {
    if($Admin->fetch_object ('pstnNumbers', "id", $POST->id)===false) {
        $Result->show("danger",  _("Invalid PSTN number identifier"), false);
    }
}
if($POST->action=="add" || $POST->action=="edit") {
    // name
    if(strlen($POST->name)<3)                                        { $Result->show("danger",  _("Name must have at least 3 characters"), true); }

    // number
    if(!is_numeric($POST->number))                                   { $Result->show("danger",  _("Number must be numeric"), true); }

    // check prefix
    $prefix = $Tools->fetch_object("pstnPrefixes", "id", $POST->prefix);
    if($prefix===false)                                                 { $Result->show("danger",  _("Invalid prefix"), true); }

    // duplicate check
    if($POST->action=="add")
    if ($Tools->check_number_duplicates ($prefix->id, $POST->number)){ $Result->show("danger",  _("Duplicate number"), true); }

    // ranges
    $prefix->prefix_raw = $Tools->prefix_normalize ($prefix->prefix);
    $prefix->prefix_raw_start = $Tools->prefix_normalize ($prefix->prefix.$prefix->start);
    $prefix->prefix_raw_stop  = $Tools->prefix_normalize ($prefix->prefix.$prefix->stop);

    // pad number
    $POST->number = str_pad($POST->number, (strlen($prefix->prefix_raw_start)-strlen($prefix->prefix_raw)),  "0", STR_PAD_LEFT);

    $POST->prefix_number = $Tools->prefix_normalize ($prefix->prefix.$POST->number);

    // number must be inside range
    if (!($POST->prefix_number >= $prefix->prefix_raw_start && $POST->prefix_number <= $prefix->prefix_raw_stop))
                                                                        { $Result->show("danger",  _("Number not inside prefix"), true); }
}

// set values
$values = array(
    "id"          =>$POST->id,
    "name"        =>$POST->name,
    "prefix"      =>$POST->prefix,
    "number"      =>$POST->number,
    "owner"       =>$POST->owner,
    "state"       =>$POST->state,
    "deviceId"    =>$POST->deviceId,
    "description" =>$POST->description
    );
# remove device
if ($User->get_module_permissions ("devices")<User::ACCESS_RW) {
    unset ($values['deviceId']);
}

# fetch custom fields
$update = $Tools->update_POST_custom_fields('pstnNumbers', $POST->action, $POST);
$values = array_merge($values, $update);

# execute update
if(!$Admin->object_modify ("pstnNumbers", $POST->action, "id", $values)) {
    $Result->show("danger", _("Number")." ".$User->get_post_action()." "._("failed"), false);
}
else {
    $Result->show("success", _("Number")." ".$User->get_post_action()." "._("successful"), false);
}
