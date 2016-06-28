<?php

/**
 * Function to set subnet permissions
 *************************************************/

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Sections	= new Sections ($Database);
$Subnets	= new Subnets ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# validate csrf cookie
$User->csrf_cookie ("validate", "permissions", $_POST['csrf_cookie']) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";


// init
$new_permissions = array();             // permissions posted
$old_permissions = array();             // existing subnet permissions
$removed_permissions = array();         // removed permissions
$changed_permissions = array();         // changed permissions

# fetch old subnet
$subnet_old = $Subnets->fetch_subnet ("id", $_POST['subnetId']);
// parse old permissions
$old_permissions = json_decode($subnet_old->permissions, true);


# get new permissions
foreach($_POST as $key=>$val) {
	if(substr($key, 0,5) == "group") {
		if($val != 0) {
			$new_permissions[substr($key,5)] = $val;
		}
	}
}


// calculate diff
if(is_array($old_permissions)) {
    foreach ($old_permissions as $k1=>$p1) {
        // if there is not permisison in new that remove old
        if (!array_key_exists($k1, $new_permissions)) {
            $removed_permissions[$k1] = 0;
        }
        // if change than save
        elseif ($old_permissions[$k1]!==$new_permissions[$k1]) {
            $changed_permissions[$k1] = $new_permissions[$k1];
        }
    }
}
// add also new groups if available
if(is_array($new_permissions)) {
    foreach ($new_permissions as $k1=>$p1) {
        if(!array_key_exists($k1, $old_permissions)) {
            $changed_permissions[$k1] = $new_permissions[$k1];
        }
    }
}

# set permissions for self
$permissions_self = array("permissions"=>json_encode($new_permissions));

# propagate ?
if (@$_POST['set_inheritance']=="Yes") {
    // fetch all possible slaves + master
    $Subnets->fetch_subnet_slaves_recursive($_POST['subnetId']);

    // append self
    $Subnets->slaves_full[$subnet_old->id] = $subnet_old;

    // calculate diff
    foreach ($Subnets->slaves_full as $s) {
        // to array
        $s_old_perm = json_decode($s->permissions, true);
        // removed
        if (sizeof($removed_permissions)>0) {
            foreach ($removed_permissions as $k=>$p) {
                unset($s_old_perm[$k]);
            }
        }
        // added
        if (sizeof($changed_permissions)>0) {
            foreach ($changed_permissions as $k=>$p) {
                $s_old_perm[$k] = $p;
            }
        }

        // set values
        $values = array(
                    "id" => $s->id,
                    "permissions" => json_encode($s_old_perm)
                    );

        // update
        if($Subnets->modify_subnet ("edit", $values)===false)       { $Result->show("danger",  _("Failed to set subnet permissons for subnet")." $s->name!", true); }
    }
    // all ok
    $Result->show("success", _("Subnet permissions set")."!", true);
}
else {
    if(!$Admin->object_modify("subnets", "edit", "id", array_merge(array("id"=>$_POST['subnetId']), $permissions_self)))	{ $Result->show("danger",  _("Failed to set subnet permissons")."!", true); }
    else																					                                { $Result->show("success", _("Subnet permissions set")."!", true); }
}

?>