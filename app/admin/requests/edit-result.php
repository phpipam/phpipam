<?php

/**
 * Script to confirm / reject IP address request
 ***********************************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

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

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "requests", $POST->csrf_cookie) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# verify permissions
if($Subnets->check_permission($User->user, $POST->subnetId) != 3)	{ $Result->show("danger", _('You do not have permissions to process this request')."!", true); }


# fetch subnet
$subnet = (array) $Admin->fetch_object("subnets", "id", $POST->subnetId);

/* if action is reject set processed and accepted to 1 and 0 */
if($POST->action == "reject") {
	//set reject values
	$values = array("id"=>$POST->requestId,
					"processed"=>1,
					"accepted"=>0,
					"adminComment"=>$POST->adminComment
					);
	if(!$Admin->object_modify("requests", "edit", "id", $values))		{ $Result->show("danger",  _("Failed to reject IP request"), true); }
	else																{ $Result->show("success", _("Request has been rejected"), false); }

	# send mail
	$Tools->ip_request_send_mail ("reject", $POST->as_array());
}
/* accept */
else {
	// fetch subnet
	$subnet_temp = $Addresses->transform_to_dotted ($subnet['subnet'])."/".$subnet['mask'];

	//check if already existing and die
	if ($Addresses->address_exists($Addresses->transform_address($POST->ip_addr, "decimal"), $subnet['id'])) { $Result->show("danger", _('IP address already exists'), true); }

	//insert to ipaddresses table
	$values = array(
					"action"      =>"add",
					"ip_addr"     =>$Addresses->transform_address($POST->ip_addr,"decimal"),
					"subnetId"    =>$POST->subnetId,
					"description" =>$POST->description,
					"hostname"    =>$POST->hostname,
					"mac"         =>$POST->mac,
					"owner"       =>$POST->owner,
					"state"       =>$POST->state,
					"switch"      =>$POST->switch,
					"port"        =>$POST->port,
					"note"        =>$POST->note
					);

	# fetch custom fields
	$update = $Tools->update_POST_custom_fields('ipaddresses', $POST->action, $POST);
	$values = array_merge($values, $update);

	if(!$Addresses->modify_address($values))	{ $Result->show("danger",  _("Failed to create IP address"), true); }

	//accept message
	$values2 = array("id"=>$POST->requestId,
					"processed"=>1,
					"accepted"=>1,
					"adminComment"=>$comment
					);
	if(!$Admin->object_modify("requests", "edit", "id", $values2))		{ $Result->show("danger",  _("Cannot confirm IP address"), true); }
	else																{ $Result->show("success", _("IP request accepted/rejected"), false); }


	# send mail

	//save subnet
	$tmp['subnetId'] = $POST->subnetId;
	unset($POST->subnetId);
	// gateway
	$gateway=$Subnets->find_gateway ($tmp['subnetId']);
	if($gateway !== false) { $tmp['gateway'] = $Subnets->transform_address($gateway->ip_addr,"dotted"); }
	//set vlan
	$vlan = $Tools->fetch_object ("vlans", "vlanId", $subnet['vlanId']);
	$tmp['vlan'] = $vlan==false ? "" : $vlan->number." - ".$vlan->description;
	//set dns
	$dns = $Tools->fetch_object ("nameservers", "id", $subnet['nameserverId']);
	$tmp['dns'] = $dns==false ? "" : $dns->description." <br> ".str_replace(";", ", ", $dns->namesrv1);

	$POST->read($tmp);

	$Tools->ip_request_send_mail ("accept", $POST->as_array());
}
