<?php

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database = new Database_PDO;
$User = new User($Database);
$Admin = new Admin($Database, false);
$Tools = new Tools($Database);
$Result = new Result ();

# verify that user is logged in
$User->check_user_session();
# check maintaneance mode
$User->check_maintaneance_mode();

# strip input tags
$_POST = $Admin->strip_input_tags($_POST);

# validate csrf cookie
$User->Crypto->csrf_cookie("validate", "portMap", $_POST['csrf_cookie']) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# validations
if ($_POST['action'] == "delete" || $_POST['action'] == "edit") {
    if ($Admin->fetch_object('portMaps', "id", $_POST['id']) === false) {
        $Result->show("danger", _("Invalid port map object identifier"), false);
    }
}
if ($_POST['action'] == "add" || $_POST['action'] == "edit" || $_POST['action'] == "copy") {
    // name
    if (strlen($_POST['name']) < 1) {
        $Result->show("danger", _("Name must have at least 1 character"), true);
    }
}

# fetch custom fields
$custom = $Tools->fetch_custom_fields('portMaps');
if (sizeof($custom) > 0) {
    foreach ($custom as $myField) {
        //booleans can be only 0 and 1!
        if ($myField['type'] == "tinyint(1)") {
            if ($_POST[$myField['name']] > 1) {
                $_POST[$myField['name']] = 0;
            }
        }
        //not null!
        if ($myField['Null'] == "NO" && strlen($_POST[$myField['name']]) == 0) { {
                $Result->show("danger", $myField['name'] . '" can not be empty!', true);
            }
        }
        # save to update array
        $update[$myField['name']] = $_POST[$myField['name']];
    }
}


// set values
$values = array(
    "id" => @$_POST['id'],
    "name" => $_POST['name'],
    "description" => $_POST['description'],
    "hostDevice" => $_POST['device']
);

if ($values["hostDevice"] == 0) { //If no device is selected for the port map
    $values["hostDevice"] = NULL;
    if($_POST['action'] == "edit") {
        $Admin->remove_object_references("devices", "port_map", $values["id"]); //Remove all device references to this map
    }
} else {
    $Admin->remove_object_references("portMaps", "hostDevice", $values["hostDevice"]); //Remove all port map references to this device
}

# custom fields
if (isset($update)) {
    $values = array_merge($values, $update);
}

if ($_POST['action'] == "delete") {
    $mapId = array ("map_id"=>$values["id"]);
    $Admin->object_modify("ports", "delete", "map_id", $mapId); //Remove all ports beloning to this map
    $Admin->remove_object_references("devices", "port_map", $values["id"]); //Remove references from devices that point to this map
}

# execute update
if (!$Admin->object_modify("portMaps", $_POST['action'], "id", $values)) {
    $Result->show("danger", _("Port Map $_POST[action] failed"), false);
} else {
    $values["id"] = $Admin->lastId; //Get ID of new port map
    if ($_POST['action'] == "copy") { //If copying a port map also copy ports
        $ports = $Tools->fetch_multiple_objects("ports", "map_id", $_POST['id']); // Get all ports associated with old map
        foreach ($ports as $port) {
            $clonedPort = array(
                "map_id" => $values["id"],
                "number" => $port->number,
                "vlan" => $port->vlan,
                "tagged" => $port->tagged,
                "name" => $port->name,
                "type" => $port->type,
                "poe" => $port->poe
            );
            if (!$Admin->object_modify("ports", "add", "id", $clonedPort)) {
                $Result->show("danger", _("Port add failed"), false);
            }
        }
    }

    if (isset($values["hostDevice"])) { //If adding/changing device, point device to the new map
        $device = array(
            "id"=> $values["hostDevice"],
            "port_map"=> $values["id"]
        );
        $Admin->object_modify("devices", "edit", "id", $device);
    }

    $Result->show("success", _("Port Map $_POST[action] successful"), false);
}