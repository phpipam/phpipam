<?php

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();
# check maintaneance mode
$User->check_maintaneance_mode ();

# strip input tags
$_POST = $Admin->strip_input_tags($_POST);

# validate csrf cookie
$User->csrf_cookie ("validate", "nat", $_POST['csrf_cookie']) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

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
$values = array(
    "id"=>@$_POST['id'],
    "name"=>$_POST['name'],
    "type"=>$_POST['type'],
    "src_port"=>$_POST['src_port'],
    "dst_port"=>$_POST['dst_port'],
    "device"=>$_POST['device'],
    "description"=>$_POST['description']
    );

# execute update
if(!$Admin->object_modify ("nat", $_POST['action'], "id", $values))  { $Result->show("danger",  _("NAT $_POST[action] failed"), false); }
else																 { $Result->show("success", _("NAT $_POST[action] successful"), false); }

# add
if($_POST['action']=="add") {
    print "<div class='new_nat_id hidden'>$Admin->lastId</div>";
}
?>