<?php

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database, false);
$Tools      = new Tools ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();
# check maintaneance mode
$User->check_maintaneance_mode ();

# perm check popup
if($_POST['action']=="edit") {
    $User->check_module_permissions ("nat", User::ACCESS_RW, true, false);
}
else {
    $User->check_module_permissions ("nat", User::ACCESS_RWA, true, false);
}

# fetch custom fields
$custom = $Tools->fetch_custom_fields('nat');

# strip input tags
$_POST = $Admin->strip_input_tags($_POST);

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "nat", $_POST['csrf_cookie']) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# validations
if($_POST['action']=="delete" || $_POST['action']=="edit") {
    if($Admin->fetch_object ('nat', "id", $_POST['id'])===false) {
        $Result->show("danger",  _("Invalid NAT object identifier"), false);
    }
}
if($_POST['action']=="add" || $_POST['action']=="edit") {
    // name
    if(strlen($_POST['name'])<3)                                            {  $Result->show("danger",  _("Name must have at least 3 characters"), true); }
    if(!in_array($_POST['type'], array("source", "static", "destination"))) {  $Result->show("danger",  _("Invalid NAT type"), true); }
    if(isset($_POST['device'])) {
        if(!is_numeric($_POST['device']))                                   {  $Result->show("danger",  _("Invalid device"), true); }
    }
}

// set values
// nothing to do here for l10n, the content of the array goes into the database
$values = array(
    "id"          => @$_POST['id'],
    "name"        => $_POST['name'],
    "type"        => $_POST['type'],
    "src_port"    => $_POST['src_port'],
    "dst_port"    => $_POST['dst_port'],
    "device"      => $_POST['device'],
    "description" => $_POST['description'],
    "policy"      => "No",
    "policy_dst"  =>  ""
     );

if ($User->get_module_permissions ("devices")==User::ACCESS_NONE) {
    unset ($values['device']);
}

// policy NAT override
if($_POST['action']=="edit" && !is_blank($_POST['policy_dst'])) {
    $values['policy']     = "Yes";
    $values['policy_dst'] = $Tools->strip_input_tags($_POST['policy_dst']);
}

// append custom
if(sizeof($custom) > 0) {
    foreach($custom as $myField) {
        # replace possible ___ back to spaces!
        $myField['nameTest'] = str_replace(" ", "___", $myField['name']);
        if(isset($_POST[$myField['nameTest']])) { $values[$myField['name']] = @$_POST[$myField['nameTest']];}
    }
}

# execute update
if(!$Admin->object_modify ("nat", $_POST['action'], "id", $values)) {
    $Result->show("danger", _("NAT")." "._($_POST["action"])." "._("failed"), false);
}
else {
    $Result->show("success", _("NAT")." "._($_POST["action"])." "._("successful"), false);
}
# add
if($_POST['action']=="add") {
    print "<div class='new_nat_id hidden'>$Admin->lastId</div>";
}
