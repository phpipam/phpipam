<?php

/**
 * Script to confirm / reject IP address request
 ***********************************************/

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database, false);
$Addresses	= new Addresses ($Database);
$Subnets	= new Subnets ($Database);
$Tools		= new Tools ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();
# check maintaneance mode
$User->check_maintaneance_mode ();

# strip input tags
$_POST = $Admin->strip_input_tags($_POST);

# validate csrf cookie
$User->csrf_cookie ("validate", "requests", $_POST['csrf_cookie']) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# verify permissions
if($Subnets->check_permission($User->user, $_POST['subnetId']) != 3)	{ $Result->show("danger", _('You do not have permissions to process this request')."!", true); }

# fetch custom fields
$custom = $Tools->fetch_custom_fields('ipaddresses');
if(sizeof($custom) > 0) {
	foreach($custom as $myField) {
		if(isset($_POST[$myField['name']])) { $_POST[$myField['name']] = $_POST[$myField['name']];}
	}
}

# fetch subnet
$subnet = (array) $Admin->fetch_object("subnets", "id", $_POST['subnetId']);

/* if action is reject set processed and accepted to 1 and 0 */
if($_POST['action'] == "reject") {
	//set reject values
	$values = array("id"=>$_POST['requestId'],
					"processed"=>1,
					"accepted"=>0,
					"adminComment"=>@$_POST['adminComment']
					);
	if(!$Admin->object_modify("requests", "edit", "id", $values))		{ $Result->show("danger",  _("Failed to reject IP request"), true); }
	else																{ $Result->show("success", _("Request has beed rejected"), false); }

	# send mail
	$Tools->ip_request_send_mail ("reject", $_POST);
}
/* accept */
else {
	// fetch subnet
	$subnet_temp = $Addresses->transform_to_dotted ($subnet['subnet'])."/".$subnet['mask'];

	//verify IP and subnet
	$Addresses->verify_address( $Addresses->transform_address($_POST['ip_addr'], "dotted"), $subnet_temp, false, true);

	//check if already existing and die
	if ($Addresses->address_exists($Addresses->transform_address($_POST['ip_addr'], "decimal"), $subnet['id'])) { $Result->show("danger", _('IP address already exists'), true); }

	//insert to ipaddresses table
	$values = array("action"=>"add",
					"ip_addr"=>$Addresses->transform_address($_POST['ip_addr'],"decimal"),
					"subnetId"=>$_POST['subnetId'],
					"description"=>@$_POST['description'],
					"dns_name"=>@$_POST['dns_name'],
					"mac"=>@$_POST['mac'],
					"owner"=>@$_POST['owner'],
					"state"=>@$_POST['state'],
					"switch"=>@$_POST['switch'],
					"port"=>@$_POST['port'],
					"note"=>@$_POST['note']
					);
	if(!$Addresses->modify_address($values))	{ $Result->show("danger",  _("Failed to create IP address"), true); }

	//accept message
	$values2 = array("id"=>$_POST['requestId'],
					"processed"=>1,
					"accepted"=>1,
					"adminComment"=>$comment
					);
	if(!$Admin->object_modify("requests", "edit", "id", $values2))		{ $Result->show("danger",  _("Cannot confirm IP address"), true); }
	else																{ $Result->show("success", _("IP request accepted/rejected"), false); }


	# send mail

	//save subnt
	$tmp['subnetId'] = $_POST['subnetId'];
	unset($_POST['subnetId']);
	// gateway
	$gateway=$Subnets->find_gateway ($tmp['subnetId']);
	if($gateway !== false) { $tmp['gateway'] = $Subnets->transform_address($gateway->ip_addr,"dotted"); }
	//set vlan
	$vlan = $Tools->fetch_object ("vlans", "vlanId", $subnet['vlanId']);
	$tmp['vlan'] = $vlan==false ? "" : $vlan->number." - ".$vlan->description;
	//set dns
	$dns = $Tools->fetch_object ("nameservers", "id", $subnet['nameserverId']);
	$tmp['dns'] = $dns==false ? "" : $dns->description." <br> ".str_replace(";", ", ", $dns->namesrv1);

	$_POST = array_merge($tmp, $_POST);

	$Tools->ip_request_send_mail ("accept", $_POST);
}

?>
