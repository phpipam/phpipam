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



# get posted permissions
foreach($_POST as $key=>$val) {
	if(substr($key, 0,5) == "group") {
		if($val != 0) {
			$perm[substr($key,5)] = $val;
		}
	}
}

# set values and update
$permissions = isset($perm) ? array("permissions"=>json_encode($perm)) : array("permissions"=>"");

# propagate ?
if (@$_POST['set_inheritance']=="Yes") {
    # fetch all possible slaves + master
    $Subnets->fetch_subnet_slaves_recursive($_POST['subnetId']);
    # update
    if(!$Admin->object_modify("subnets", "edit-multiple", $Subnets->slaves, $permissions))	{ $Result->show("danger",  _("Failed to set subnet permissons")."!", true); }
    else																					{ $Result->show("success", _("Subnet permissions set")."!", true); }
}
else {
    if(!$Admin->object_modify("subnets", "edit", "id", array_merge(array("id"=>$_POST['subnetId']), $permissions)))	{ $Result->show("danger",  _("Failed to set subnet permissons")."!", true); }
    else																					                        { $Result->show("success", _("Subnet permissions set")."!", true); }
}

?>