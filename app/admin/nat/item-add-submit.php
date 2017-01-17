<?php

/**
 *	remove item from nat
 ************************************************/

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
# check maintaneance mode
$User->check_maintaneance_mode ();

# get NAT object
$nat = $Admin->fetch_object ("nat", "id", $_POST['id']);
$nat!==false ? : $Result->show("danger", _("Invalid ID"), true);

# static NAT checks
if($nat->type=="static") {
    // static NAT can only have IP address
    if($_POST['object_type']!="ipaddresses") {
        $Result->show("danger", _("Static NAT can only contain IP address"), true);
    }

    // decode
    $nat_src = json_decode($nat->src, true);
    $nat_dst = json_decode($nat->dst, true);

    // validate all objects
    if(sizeof(@$nat_src['ipaddresses'])>0) {
        foreach ($nat_src['ipaddresses'] as $ik=>$iv) {
            if($Tools->fetch_object("ipaddresses", "id", $iv)===false) {
                unset($nat_src['ipaddresses'][$ik]);
            }
        }
    }
    if(sizeof(@$nat_dst['ipaddresses'])>0) {
        foreach ($nat_dst['ipaddresses'] as $ik=>$iv) {
            if($Tools->fetch_object("ipaddresses", "id", $iv)===false) {
                unset($nat_dst['ipaddresses'][$ik]);
            }
        }
    }

    // check
    if(is_array($nat_src) && $_POST['type']=="src") {
        $nat_src = array_filter($nat_src);

        if(isset($nat_src['ipaddresses'])) {
            if(sizeof($nat_src['ipaddresses'])>0) {
                 $Result->show("danger", _("Static NAT can only have 1 source address"), true);
            }
        }
    }

    // check
    if(is_array($nat_dst) && $_POST['type']=="dst") {
        $nat_dst = array_filter($nat_dst);

        if(isset($nat_dst['ipaddresses'])) {
            if(sizeof($nat_dst['ipaddresses'])>0) {
                 $Result->show("danger", _("Static NAT can only have 1 destination address"), true);
            }
        }
    }
}

// type: src, dst
// object_type: (subnets, ipaddresses) - optional
// object_id - optional

# validate type
if($_POST['type']!=="src" && $_POST['type']!=="dst") { $Result->show("danger", _("Invalid type"), true); }

# if type (subnets, ipaddresses) is set and id than just link
if(isset($_POST['object_type']) && isset($_POST['object_id'])) {

    // parameters
    $obj_type = $_POST['object_type'];      // subnets, ipaddresses
    $obj_id   = $_POST['object_id'];        // object identifier
    $nat_id   = $_POST['id'];               // nat id
    $nat_type = $_POST['type'];             // src, dst

    // validate object
    $item = $Tools->fetch_object ($obj_type, "id", $obj_id);
    if($item!==false) {
        // update
        if($nat_type=="src") {
            $nat_array = json_decode($nat->src, true);
        }
        else {
            $nat_array = json_decode($nat->dst, true);
        }

        if(is_array($nat_array[$obj_type]))
        $nat_array[$obj_type] = array_merge($nat_array[$obj_type], array($obj_id));
        else
        $nat_array[$obj_type] = array($obj_id);

        // to json
        if ($nat_type=="src")   { $nat->src = json_encode($nat_array); }
        else                    { $nat->dst = json_encode($nat_array); }

        // update
        if ($Admin->object_modify ("nat", "edit", "id", array("id"=>$nat_id, "src"=>$nat->src, "dst"=>$nat->dst))) {
            $Result->show("success", "Object added", false);
        }
        else {
            $Result->show("danger", "Failed to add object", false);
        }
    }
    else {
        $Result->show("danger", _("Invalid object identifier"), true);
    }
}
else {
     $Result->show("danger", _("Missing object type or id"), true);
}
?>