<?php

/*
 * Print edit subnet
 *********************/

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database, false);
$Sections	= new Sections ($Database);
$Subnets	= new Subnets ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# strip input tags
$_POST = $Admin->strip_input_tags($_POST);

# validate csrf cookie
$User->csrf_cookie ("validate", "linkedsubnet", $_POST['csrf_cookie']) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# ID must be numeric
if(!is_numeric($_POST['subnetId']))	{ $Result->show("danger", _("Invalid ID"), true); }
if(!is_numeric($_POST['linked_subnet']))	{ $Result->show("danger", _("Invalid ID"), true); }

# submit
$values = array(
    "id" => $_POST['subnetId'],
    "linked_subnet" => $_POST['linked_subnet']
);

# verify that user has write permissions for subnet
if($Admin->object_modify ("subnets", "edit", "id", $values)!==false) {
    $Result->show("success", _("Subnet linked"), false);
}
?>