<?php

/*
 * Print edit subnet
 *********************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database, false);
$Subnets	= new Subnets ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();
# check maintaneance mode
$User->check_maintaneance_mode ();

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "linkedsubnet", $POST->csrf_cookie) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# check subnet permissions
if($Subnets->check_permission ($User->user, $POST->subnetId) != 3) 	{ $Result->show("danger", _('You do not have permissions to add edit/delete this subnet')."!", true); }

# ID must be numeric
if(!is_numeric($POST->subnetId))	{ $Result->show("danger", _("Invalid ID"), true); }
if(!is_numeric($POST->linked_subnet))	{ $Result->show("danger", _("Invalid ID"), true); }

# submit
$values = array(
    "id" => $POST->subnetId,
    "linked_subnet" => $POST->linked_subnet
);

# verify that user has write permissions for subnet
if($Subnets->modify_subnet ("edit", $values)!==false) {
    $Result->show("success", _("Subnet linked"), false);
}
