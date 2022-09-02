<?php
/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Tools	 	= new Tools ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# create csrf token
$User->Crypto->csrf_cookie ("validate", "routing_bgp", $_POST['csrf_cookie']) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# perm check popup
if($_POST['action']=="edit") {
    $User->check_module_permissions ("routing", 2, true, true);
}
else {
    $User->check_module_permissions ("routing", 3, true, true);
}

# validate
if ($_POST['action']=="edit" || $_POST['action']=="add") {
	if(!is_numeric($_POST['local_as']))  				{ $Result->show("danger",  _("Invalid local AS"), true); }
	if(!is_numeric($_POST['peer_as'])) 					{ $Result->show("danger",  _("Invalid peer AS"), true); }
	if(!$Tools->validate_ip ($_POST['local_address']))	{ $Result->show("danger",  _("Invalid local address"), true); }
	if(!$Tools->validate_ip ($_POST['peer_address']))	{ $Result->show("danger",  _("Invalid peer address"), true); }
}
if ($_POST['action']=="edit" || $_POST['action']=="delete") {
	if(!is_numeric($_POST['id']))  						{ $Result->show("danger",  _("Invalid ID"), true); }
}

# permission recheck for modules
if(isset($_POST['vrf_id'])) {
	if( $User->get_module_permissions ("vrf")==0)  		{ $Result->show("danger",  _("Insufficient permissions for module VRF"), true); }
	if(!is_numeric($_POST['vrf_id']))  					{ $Result->show("danger",  _("Invalid VRF ID"), true); }
}
if(isset($_POST['circuit_id'])) {
	if( $User->get_module_permissions ("circuits")==0)  { $Result->show("danger",  _("Insufficient permissions for module Circuits"), true); }
	if(!is_numeric($_POST['circuit_id']))  				{ $Result->show("danger",  _("Invalid Circuits ID"), true); }
}
if(isset($_POST['customer_id'])) {
	if( $User->get_module_permissions ("customers")==0) { $Result->show("danger",  _("Insufficient permissions for module Customers"), true); }
	if(!is_numeric($_POST['customer_id']))  			{ $Result->show("danger",  _("Invalid Customer ID"), true); }
}

# create update array
$values = [
			"id"			=> $_POST['id'],
			"bgp_type"      => $Tools->strip_xss ($_POST['bgp_type']),
			"local_as"      => $_POST['local_as'],
			"local_address" => $_POST['local_address'],
			"peer_name"     => $Tools->strip_xss ($_POST['peer_name']),
			"peer_as"       => $_POST['peer_as'],
			"peer_address"  => $_POST['peer_address'],
			"description"   => $Tools->strip_xss ($_POST['description']),
			];
# modules
if(isset($_POST['vrf_id'])) {
	$values['vrf_id'] = $_POST['vrf_id']!=0 ? $_POST['vrf_id'] : NULL;
}
if(isset($_POST['circuit_id'])) {
	$values['circuit_id'] = $_POST['circuit_id']!=0 ? $_POST['circuit_id'] : NULL;
}
if(isset($_POST['customer_id'])) {
	$values['customer_id'] = $_POST['customer_id']!=0 ? $_POST['customer_id'] : NULL;

}


# execute update
if(!$Admin->object_modify ("routing_bgp", $_POST['action'], "id", $values))  { $Result->show("danger",  _("BGP $_POST[action] failed"), false); }
else																 		 { $Result->show("success", _("BGP $_POST[action] successful"), false); }

# add
if($_POST['action']=="add") {
    print "<div class='new_nat_id hidden'>$Admin->lastId</div>";
}