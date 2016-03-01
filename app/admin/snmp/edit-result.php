<?php

/**
 * Script to edit snmp method
 *************************************/

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Snmp       = new phpipamSNMP ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# strip input tags
$_POST = $Admin->strip_input_tags($_POST);

# validate csrf cookie
$_POST['csrf_cookie']==$_SESSION['csrf_cookie'] ? :                      $Result->show("danger", _("Invalid CSRF cookie"), true);

/* checks */
$error = array();

if($_POST['action']!="delete") {
	# name must be more than 2 and alphanumberic
	if(strlen($_POST['name'])<3)			                            { $error[] = "Invalid name"; }
    # numeric oid (strip dots)
    if(!is_numeric(str_replace(".", "", $_POST['oid'])))                { $error[] = "Invalid OID"; }
    # method check
    if(!in_array($_POST['method'], $Snmp->snmp_groups))                 { $error[] = "Invalid group"; }
}

# die if errors
if(sizeof($error) > 0) {
	$Result->show("danger", $error, true);
}
else {
	# create array of values for modification
	$values = array("id"=>@$_POST['id'],
					"name"=>$_POST['name'],
					"oid"=>$_POST['oid'],
					"method"=>$_POST['method'],
					"description"=>@$_POST['description']
					);

	# execute
	if(!$Admin->object_modify("snmp", $_POST['action'], "id", $values)) 	{ $Result->show("danger",  _("SNMP $_POST[action] error"), true); }
	else 																    { $Result->show("success", _("SNMP $_POST[action] success"), true); }
}

?>