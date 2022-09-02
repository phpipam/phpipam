<?php

/**
 * unlink object prom provider
 ***************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

// initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database, false);
$Tools	 	= new Tools ($Database);
$Result 	= new Result ();

// verify that user is logged in
$User->check_user_session();
// verify module permissions
$User->check_module_permissions ("customers", 2, true, true);
// check maintaneance mode
$User->check_maintaneance_mode ();
// get modified details
$_POST = $Admin->strip_input_tags($_POST);

// make sure correct object is applied
if(!array_key_exists($_POST['object'], $Tools->get_customer_object_types())) {
	$Result->show ("danger", _("Invalid object"), true, true);
}
// ID must be numeric
if (!is_numeric($_POST['id'])) {
	$Result->show ("danger", _("Invalid object ID"), true, true);
}

// set field
$field = "id";
if($_POST['object']=="vlans")	{ $field = "vlanId"; }
elseif($_POST['object']=="vrf")	{ $field = "vrfId"; }

// unlink
if ($Admin->object_modify ($_POST['object'], "edit", $field, [$field=>$_POST['id'], "customer_id"=>NULL])!==false) {
	$Result->show ("success", _("Object removed"), true, true, false, false, true);
}