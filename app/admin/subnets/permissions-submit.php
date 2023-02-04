<?php

/**
 * Function to set subnet permissions
 *************************************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Sections	= new Sections ($Database);
$Subnets	= new Subnets ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();
# check maintaneance mode
$User->check_maintaneance_mode ();

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "permissions", $_POST['csrf_cookie']) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";


# fetch old subnet
$subnet_old = $Subnets->fetch_subnet ("id", $_POST['subnetId']);
// parse old permissions
$old_permissions = pf_json_decode($subnet_old->permissions, true);

list($removed_permissions, $changed_permissions) = $Subnets->get_permission_changes ((array) $_POST, $old_permissions);

$subnet_list = array();
# propagate ?
if (@$_POST['set_inheritance']=="Yes") {
    // fetch all possible slaves + master
    $Subnets->fetch_subnet_slaves_recursive($_POST['subnetId']);

	if (is_array($Subnets->slaves_full))
		$subnet_list = $Subnets->slaves_full;
}
// append self
$subnet_list[] = $subnet_old;

// apply permission changes
$Subnets->set_permissions($subnet_list, $removed_permissions, $changed_permissions);