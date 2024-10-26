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
$Subnets	= new Subnets ($Database);
$Addresses	= new Addresses ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();
# check maintaneance mode
$User->check_maintaneance_mode ();

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "split", $POST->csrf_cookie) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";


# id must be numeric
if(!is_numeric($POST->subnetId))			{ $Result->show("danger", _("Invalid ID"), true); }

# get subnet details
$subnet_old = $Subnets->fetch_subnet (null, $POST->subnetId);

# verify that user has write permissions for subnet
$subnetPerm = $Subnets->check_permission ($User->user, $subnet_old->id);
if($subnetPerm < 3) 						{ $Result->show("danger", _('You do not have permissions to resize subnet').'!', true); }

# verify
$Subnets->subnet_split ($subnet_old, $POST->number, $POST->prefix, $POST->group, $POST->custom_fields);

# all good
$Result->show("success", _("Subnet split successfully")."!", true);
