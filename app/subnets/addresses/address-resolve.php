<?php

/**
 *	Script that resolved hostname from IP address
 */

# include required scripts
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize required objects
$Database 	= new Database_PDO;
$User		= new User ($Database);
$Subnets	= new Subnets ($Database);
$DNS		= new DNS ($Database);

# verify that user is logged in
$User->check_user_session();

# fetch subnet
$subnet = $Subnets->fetch_subnet ("id", $POST->subnetId);
$nsid = $subnet===false ? false : $subnet->nameserverId;

# resolve
$hostname = $DNS->resolve_address ($POST->ipaddress, false, true, $nsid);

# print result
print $hostname['name'];