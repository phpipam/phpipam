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
$Params		= new Params ($Admin->strip_input_tags($_POST));

// verify that user is logged in
$User->check_user_session();
// verify module permissions
$User->check_module_permissions ("customers", User::ACCESS_RW, true, true);
// check maintaneance mode
$User->check_maintaneance_mode ();

// make sure correct object is applied
if(!array_key_exists($Params->object, $Tools->get_customer_object_types())) {
	$Result->show ("danger", _("Invalid object"), true, true);
}
// ID must be numeric
if (!is_numeric($Params->id)) {
	$Result->show ("danger", _("Invalid object ID"), true, true);
}

// set field
$field = "id";
if($Params->object=="vlans")	{ $field = "vlanId"; }
elseif($Params->object=="vrf")	{ $field = "vrfId"; }

// unlink
if ($Admin->object_modify ($Params->object, "edit", $field, [$field=>$Params->id, "customer_id"=>null])!==false) {
	$Result->show ("success", _("Object removed"), true, true, false, false, true);
}
