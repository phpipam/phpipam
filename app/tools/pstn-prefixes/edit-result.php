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
    $User->check_module_permissions ("pstn", User::ACCESS_RW, true, false);
}
else {
    $User->check_module_permissions ("pstn", User::ACCESS_RWA, true, false);
}


# validate csrf cookie
if($POST->action=="add") {
    $User->Crypto->csrf_cookie ("validate", "pstn_add", $POST->csrf_cookie) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";
}
else {
    $User->Crypto->csrf_cookie ("validate", "pstn_".$POST->id, $POST->csrf_cookie) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";
}


# validations
if($POST->action=="delete" || $POST->action=="edit") {
    if($Admin->fetch_object ('pstnPrefixes', "id", $POST->id)===false) {
        $Result->show("danger",  _("Invalid PSTN object identifier"), false);
    }
}
if($POST->action=="add" || $POST->action=="edit") {
    // name
    if(strlen($POST->name)<3)                                        { $Result->show("danger",  _("Name must have at least 3 characters"), true); }

    // prefix
    if(!$POST->prefix)                                               { $Result->show("danger", _("Prefix can not be empty!"), true); }

    // number
    if(!is_numeric($POST->start))                                    { $Result->show("danger",  _("Start must be numeric"), true); }
    if(!is_numeric($POST->stop))                                     { $Result->show("danger",  _("Stop must be numeric"), true); }

    // check master
    if($POST->master!=0) {
        $master_prefix = $Tools->fetch_object("pstnPrefixes", "id", $POST->master);
        if($master_prefix===false)                                      { $Result->show("danger",  _("Invalid master prefix"), true); }

        // ranges
        $master_prefix->prefix_raw = $Tools->prefix_normalize ($master_prefix->prefix);
        $master_prefix->prefix_raw_start = $Tools->prefix_normalize ($master_prefix->prefix.$master_prefix->start);
        $master_prefix->prefix_raw_stop  = $Tools->prefix_normalize ($master_prefix->prefix.$master_prefix->stop);

        $POST->prefix_raw = $Tools->prefix_normalize ($POST->prefix);
        $POST->prefix_raw_start = $Tools->prefix_normalize ($POST->prefix.$POST->start);
        $POST->prefix_raw_stop  = $Tools->prefix_normalize ($POST->prefix.$POST->stop);

        // prefix must be inside range
        if ($POST->prefix_raw_start == $master_prefix->prefix_raw_start &&
            $POST->prefix_raw_stop == $master_prefix->prefix_raw_stop)
                                                                        { $Result->show("danger",  _("Prefix cannot be same as master"), true); }
        if ($POST->prefix_raw_start < $master_prefix->prefix_raw_start ||
            $POST->prefix_raw_stop  > $master_prefix->prefix_raw_stop)
                                                                        { $Result->show("danger",  _("Prefix not inside its master"), true); }
    }
}
// root check
if($POST->action=="add" && $POST->master==0) {
    // set raw values
    $POST->prefix_raw = $Tools->prefix_normalize ($POST->prefix);
    $POST->prefix_raw_start = $Tools->prefix_normalize ($POST->prefix.$POST->start);
    $POST->prefix_raw_stop  = $Tools->prefix_normalize ($POST->prefix.$POST->stop);
    $POST->prefix_size = $POST->prefix_raw_stop - $POST->prefix_raw_start;

    # fetch all
    $all_prefixes = $Tools->fetch_all_objects("pstnPrefixes", "master", 0);
    if($all_prefixes!==false) {
        foreach ($all_prefixes as $master_prefix) {

            $overlap_text = _("Prefix overlaps with prefix")." ".$master_prefix->name." (".$master_prefix->prefix.")";

            // ranges
            $master_prefix->prefix_raw = $Tools->prefix_normalize ($master_prefix->prefix);
            $master_prefix->prefix_raw_start = $Tools->prefix_normalize ($master_prefix->prefix.$master_prefix->start);
            $master_prefix->prefix_raw_stop  = $Tools->prefix_normalize ($master_prefix->prefix.$master_prefix->stop);
            $master_prefix->prefix_size  = $master_prefix->prefix_raw_stop - $master_prefix->prefix_raw_start;

            // if it begins before
            if ($POST->prefix_raw_start < $master_prefix->prefix_raw_start) {
                if($POST->prefix_raw_stop >= $master_prefix->prefix_raw_start) {
                    { $Result->show("danger", $overlap_text, true); }
                }
            }
            elseif($POST->prefix_raw_start > $master_prefix->prefix_raw_start) {
                if($POST->prefix_raw_start <= $master_prefix->prefix_raw_stop) {
                    { $Result->show("danger", $overlap_text, true); }
                }
            }
            else    { $Result->show("danger", $overlap_text, true); }
        }
    }
}

// set values
$values = array(
    "id"          =>$POST->id,
    "name"        =>$POST->name,
    "prefix"      =>$POST->prefix,
    "master"      =>$POST->master,
    "start"       =>$POST->start,
    "stop"        =>$POST->stop,
    "deviceId"    =>$POST->deviceId,
    "description" =>$POST->description
    );

# perm check
if ($User->get_module_permissions ("devices")==User::ACCESS_NONE) {
    unset ($values['deviceId']);
}

# custom fields
$update = $Tools->update_POST_custom_fields('pstnPrefixes', $POST->action, $POST);
$values = array_merge($values, $update);

# execute update
if(!$Admin->object_modify ("pstnPrefixes", $POST->action, "id", $values)) {
    $Result->show("danger", _("Prefix")." ".$User->get_post_action()." "._("failed"), false);
}
else {
    $Result->show("success", _("Prefix")." ".$User->get_post_action()." "._("successful"), false);
}

# if delete remove all slaves
if ($POST->action=="delete") {
    $values['master'] = $values['id'];
    # remove all references from prefixes and remove all numbers
    $Admin->remove_object_references ("pstnPrefixes", "master", $values["id"], 0);
    $Admin->object_modify ("pstnNumbers", "delete", "prefix", $values);
}
