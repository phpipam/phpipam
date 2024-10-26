<?php
/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database, false);
$Tools	 	= new Tools ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# create csrf token
$User->Crypto->csrf_cookie ("validate", "routing_bgp", $POST->csrf_cookie) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# perm check popup
if($POST->action=="edit") {
    $User->check_module_permissions ("routing", User::ACCESS_RW, true, true);
}
else {
    $User->check_module_permissions ("routing", User::ACCESS_RWA, true, true);
}

# validate
if ($POST->action=="edit" || $POST->action=="add") {
	if(!is_numeric($POST->local_as))  				{ $Result->show("danger",  _("Invalid local AS"), true); }
	if(!is_numeric($POST->peer_as)) 				{ $Result->show("danger",  _("Invalid peer AS"), true); }
	if(!$Tools->validate_ip ($POST->local_address))	{ $Result->show("danger",  _("Invalid local address"), true); }
	if(!$Tools->validate_ip ($POST->peer_address))	{ $Result->show("danger",  _("Invalid peer address"), true); }
}
if ($POST->action=="edit" || $POST->action=="delete") {
	if(!is_numeric($POST->id))						{ $Result->show("danger",  _("Invalid ID"), true); }
}

# permission recheck for modules
if(isset($POST->vrf_id)) {
	if( $User->get_module_permissions ("vrf")==User::ACCESS_NONE)  		{ $Result->show("danger",  _("Insufficient permissions for module VRF"), true); }
	if(!is_numeric($POST->vrf_id))  									{ $Result->show("danger",  _("Invalid VRF ID"), true); }
}
if(isset($POST->circuit_id)) {
	if( $User->get_module_permissions ("circuits")==User::ACCESS_NONE)  { $Result->show("danger",  _("Insufficient permissions for module Circuits"), true); }
	if(!is_numeric($POST->circuit_id))  								{ $Result->show("danger",  _("Invalid Circuits ID"), true); }
}
if(isset($POST->customer_id)) {
	if( $User->get_module_permissions ("customers")==User::ACCESS_NONE) { $Result->show("danger",  _("Insufficient permissions for module Customers"), true); }
	if(!is_numeric($POST->customer_id))  								{ $Result->show("danger",  _("Invalid Customer ID"), true); }
}

# fetch custom fields
$custom = $Tools->fetch_custom_fields('routing_bgp');
if(sizeof($custom) > 0) {
	foreach($custom as $myField) {

		//replace possible ___ back to spaces
		$myField['nameTest'] = str_replace(" ", "___", $myField['name']);
		if(isset($POST->{$myField['nameTest']})) { $POST->{$myField['name']} = $POST->{$myField['nameTest']};}

		//booleans can be only 0 and 1!
		if($myField['type']=="tinyint(1)") {
			if($POST->{$myField['name']}>1) {
				$POST->{$myField['name']} = 0;
			}
		}
		//not null!
		if($myField['Null']=="NO" && is_blank($POST->{$myField['name']})) { $Result->show("danger", $myField['name']." "._("can not be empty!"), true); }

		# save to update array
		$update[$myField['name']] = $POST->{$myField['nameTest']};
	}
}

# create update array
$values = [
			"id"			=> isset($POST->id) ? $POST->id : null,
			"bgp_type"      => $POST->bgp_type,
			"local_as"      => $POST->local_as,
			"local_address" => $POST->local_address,
			"peer_name"     => $POST->peer_name,
			"peer_as"       => $POST->peer_as,
			"peer_address"  => $POST->peer_address,
			"description"   => $POST->description,
			];
# modules
if(isset($POST->vrf_id)) {
	$values['vrf_id'] = $POST->vrf_id!=0 ? $POST->vrf_id : NULL;
}
if(isset($POST->circuit_id)) {
	$values['circuit_id'] = $POST->circuit_id!=0 ? $POST->circuit_id : NULL;
}
if(isset($POST->customer_id)) {
	$values['customer_id'] = $POST->customer_id!=0 ? $POST->customer_id : NULL;
}
# custom fields
if(isset($update)) {
	$values = array_merge($values, $update);
}

# execute update
if(!$Admin->object_modify ("routing_bgp", $POST->action, "id", $values)) {
    $Result->show("danger", _("BGP")." ".$User->get_post_action()." "._("failed"), false);
}
else {
    $Result->show("success", _("BGP")." ".$User->get_post_action()." "._("successful"), false);
}

# add
if($POST->action=="add") {
    print "<div class='new_nat_id hidden'>$Admin->lastId</div>";
}
