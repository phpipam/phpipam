<?php

/**
 *	Script that resolved hostname from IP address
 */

# include required scripts
require( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize required objects
$Database 	= new Database_PDO;
$User		= new User ($Database);
$DNS		= new DNS ($Database);

# verify that user is logged in
$User->check_user_session();

# create object
$address = new StdClass();
$address->ip_addr  = $_POST['ipaddress'];
$address->dns_name = null;

# resolve
$hostname = $DNS->resolve_address ($address, true);

# print result
print $hostname['name'];
?>