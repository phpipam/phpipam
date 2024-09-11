<?php
/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database, false);
$Tools	 	= new Tools ($Database);
$Result 	= new Result ();
$Params		= new Params ($User->strip_input_tags ($_POST));

# verify that user is logged in
$User->check_user_session();

# create csrf token
$User->Crypto->csrf_cookie ("validate", "routing_bgp", $Params->csrf_cookie) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# perm check popup
if($Params->action=="edit") {
    $User->check_module_permissions ("routing", User::ACCESS_RW, true, true);
}
else {
    $User->check_module_permissions ("routing", User::ACCESS_RWA, true, true);
}

# validate
if ($Params->action=="edit" || $Params->action=="add") {
	if(!is_numeric($Params->local_as))  				{ $Result->show("danger",  _("Invalid local AS"), true); }
	if(!is_numeric($Params->peer_as)) 					{ $Result->show("danger",  _("Invalid peer AS"), true); }
	if(!$Tools->validate_ip ($Params->local_address))	{ $Result->show("danger",  _("Invalid local address"), true); }
	if(!$Tools->validate_ip ($Params->peer_address))	{ $Result->show("danger",  _("Invalid peer address"), true); }
}
if ($Params->action=="edit" || $Params->action=="delete") {
	if(!is_numeric($Params->id))						{ $Result->show("danger",  _("Invalid ID"), true); }
}

# permission recheck for modules
if(isset($Params->vrf_id)) {
	if( $User->get_module_permissions ("vrf")==User::ACCESS_NONE)  		{ $Result->show("danger",  _("Insufficient permissions for module VRF"), true); }
	if(!is_numeric($Params->vrf_id))  					{ $Result->show("danger",  _("Invalid VRF ID"), true); }
}
if(isset($Params->circuit_id)) {
	if( $User->get_module_permissions ("circuits")==User::ACCESS_NONE)  { $Result->show("danger",  _("Insufficient permissions for module Circuits"), true); }
	if(!is_numeric($Params->circuit_id))  				{ $Result->show("danger",  _("Invalid Circuits ID"), true); }
}
if(isset($Params->customer_id)) {
	if( $User->get_module_permissions ("customers")==User::ACCESS_NONE) { $Result->show("danger",  _("Insufficient permissions for module Customers"), true); }
	if(!is_numeric($Params->customer_id))  			{ $Result->show("danger",  _("Invalid Customer ID"), true); }
}

# fetch custom fields
$custom = $Tools->fetch_custom_fields('routing_bgp');
if(sizeof($custom) > 0) {
	foreach($custom as $myField) {

		//replace possible ___ back to spaces
		$myField['nameTest'] = str_replace(" ", "___", $myField['name']);
		if(isset($Params->{$myField['nameTest']})) { $Params->{$myField['name']} = $Params->{$myField['nameTest']};}

		//booleans can be only 0 and 1!
		if($myField['type']=="tinyint(1)") {
			if($Params->{$myField['name']}>1) {
				$Params->{$myField['name']} = 0;
			}
		}
		//not null!
		if($myField['Null']=="NO" && is_blank($Params->{$myField['name']})) { $Result->show("danger", $myField['name']." "._("can not be empty!"), true); }

		# save to update array
		$update[$myField['name']] = $Params->{$myField['nameTest']};
	}
}

# create update array
$values = [
			"id"			=> isset($Params->id) ? $Params->id : null,
			"bgp_type"      => $Tools->strip_xss ($Params->bgp_type),
			"local_as"      => $Params->local_as,
			"local_address" => $Params->local_address,
			"peer_name"     => $Tools->strip_xss ($Params->peer_name),
			"peer_as"       => $Params->peer_as,
			"peer_address"  => $Params->peer_address,
			"description"   => $Tools->strip_xss ($Params->description),
			];
# modules
if(isset($Params->vrf_id)) {
	$values['vrf_id'] = $Params->vrf_id!=0 ? $Params->vrf_id : NULL;
}
if(isset($Params->circuit_id)) {
	$values['circuit_id'] = $Params->circuit_id!=0 ? $Params->circuit_id : NULL;
}
if(isset($Params->customer_id)) {
	$values['customer_id'] = $Params->customer_id!=0 ? $Params->customer_id : NULL;
}
# custom fields
if(isset($update)) {
	$values = array_merge($values, $update);
}

# execute update
if(!$Admin->object_modify ("routing_bgp", $Params->action, "id", $values)) {
    $Result->show("danger", _("BGP")." ".$User->get_post_action()." "._("failed"), false);
}
else {
    $Result->show("success", _("BGP")." ".$User->get_post_action()." "._("successful"), false);
}

# add
if($Params->action=="add") {
    print "<div class='new_nat_id hidden'>$Admin->lastId</div>";
}
