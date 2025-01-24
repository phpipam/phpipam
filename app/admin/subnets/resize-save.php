<?php

/*
 * Print resize subnet
 *********************/


/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database, false);
$Sections	= new Sections ($Database);
$Subnets	= new Subnets ($Database);
$Addresses	= new Addresses ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();
# check maintaneance mode
$User->check_maintaneance_mode ();

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "resize", $POST->csrf_cookie) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# ID must be numeric
if(!is_numeric($POST->subnetId))									{ $Result->show("danger", _("Invalid ID"), true); }
# verify that user has write permissions for subnet
if($Subnets->check_permission ($User->user, $POST->subnetId)<3)	{ $Result->show("danger", _('You do not have permissions to resize subnet').'!', true); }

# fetch old subnet details and set new
$subnet_old = (array) $Subnets->fetch_subnet (null, $POST->subnetId);

# verify resizing
$Subnets->verify_subnet_resize ($subnet_old['subnet'], $POST->newMask, $subnet_old['id'], $subnet_old['vrfId'], $subnet_old['masterSubnetId'], $subnet_old['mask'], $subnet_old['sectionId']);

# we need to recalculate subnet address if needed
if ($subnet_old['mask'] < $POST->newMask) {
	$subnet_new['subnet'] = $subnet_old['subnet'];
}
else {
	$new_boundaries		  = $Subnets->get_network_boundaries ($Subnets->transform_address($subnet_old['subnet'], "dotted"), $POST->newMask);
	$subnet_new['subnet'] = $Subnets->transform_address($new_boundaries['network'], "decimal");
}

# set update values
$values = array("id"=>$POST->subnetId,
				"subnet"=>$subnet_new['subnet'],
				"mask"=>$POST->newMask
				);
if(!$Subnets->modify_subnet ("resize", $values))				{ $Result->show("danger",  _("Error resizing subnet")."!", true); }
else															{ $Result->show("success", _("Subnet resized successfully")."!", true); }
