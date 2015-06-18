<?php
/*
 * insert new hosts to database
 *******************************/

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Subnets	= new Subnets ($Database);
$Addresses	= new Addresses ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();


# subnet Id must be a integer
if(!is_numeric($_POST['subnetId']) || $_POST['subnetId']==0)			{ $Result->show("danger", _("Invalid ID"), true); }
# verify that user has write permissionss for subnet
if($Subnets->check_permission ($User->user, $_POST['subnetId']) != 3) 	{ $Result->show("danger", _('You do not have permissions to modify hosts in this subnet')."!", true, true); }


# ok, lets get results form post array!
foreach($_POST as $key=>$line) {
	// IP address
	if(substr($key, 0,2)=="ip") 			{ $res[substr($key, 2)]['ip_addr']  	= $line; }
	// description
	if(substr($key, 0,11)=="description") 	{ $res[substr($key, 11)]['description'] = $line; }
	// dns name
	if(substr($key, 0,8)=="dns_name") 		{ $res[substr($key, 8)]['dns_name']  	= $line; }
	//verify that it is not already in table!
	if(substr($key, 0,2)=="ip") {
		if($Addresses->address_exists ($line, $_POST['subnetId']) === true) {
			$Result->show("danger", "IP address $line already exists!", true);

		}
	}
}

# insert entries
if(sizeof($res)>0) {
	$errors = 0;
	foreach($res as $r) {
		# set insert values
		$values = array("ip_addr"=>$Subnets->transform_to_decimal($r['ip_addr']),
						"dns_name"=>$r['dns_name'],
						"subnetId"=>$_POST['subnetId'],
						"description"=>$r['description'],
						"lastSeen"=>date("Y-m-d H:i:s")
						);
		# insert
		if(!$Admin->object_modify("ipaddresses", "add", "id", $values))	{ $Result->show("danger", "Failed to import entry ".$r['ip_addr'], false); $errors++; }
	}

	# success if no errors
	if($errors==0) {  $Result->show("success", _("Scan results added to database")."!", true); }
}
# error
else { $Result->show("danger", _("No entries available"), true); }
?>