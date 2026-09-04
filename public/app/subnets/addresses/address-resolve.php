<?php

/**
 *	Script that resolved hostname from IP address
 */

# include required scripts
require_once  __DIR__ . '/../../../../functions/functions.php';

# initialize required objects
$Database 	= new Database_PDO;
$User		= new User ($Database);
$Subnets	= new Subnets ($Database);
$Addresses	= new Addresses ($Database);
$DNS		= new DNS ($Database);

# verify that user is logged in
$User->check_user_session();

# fetch subnet
$subnet = $Subnets->fetch_subnet ("id", $POST->subnetId);
if (!is_object($subnet) || $Subnets->check_permission($User->user, $POST->subnetId) < User::ACCESS_RW) {
    print _("Invalid ID");
    die();
}
if (!$Addresses->address_within_subnet($POST->ipaddress, $subnet, false)) {
    print _("Invalid IP address");
    die();
}

$nsid = is_object($subnet) ? $subnet->nameserverId : false;
$hostname = $DNS->resolve_address ($POST->ipaddress, false, true, $nsid);

print $hostname['name'];