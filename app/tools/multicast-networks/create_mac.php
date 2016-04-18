<?php

/**
 * creates mac address from provided IP address
 */

# include required scripts
require( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize required objects
$Database 	= new Database_PDO;
$Result		= new Result;
$User		= new User ($Database);
$Subnets	= new Subnets ($Database);

# verify that user is logged in
$User->check_user_session();

# check that multicast is enabled
if ($User->settings->enableMulticast!="1")          { die("False"); }

# validations
if ($Subnets->verify_cidr ($_POST['ip'])===false)   { die("False"); }
if ($Subnets->is_multicast ($_POST['ip'])===false)  { die("False"); }

# get mac
$text = $Subnets->create_multicast_mac ($_POST['ip']);

# print mas
if ($text===false)  { die("False"); }
else                { print $text; }

?>