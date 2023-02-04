<?php

/**
 * validate mac address for multicast
 */

# include required scripts
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize required objects
$Database 	= new Database_PDO;
$Result		= new Result;
$User		= new User ($Database);
$Subnets	= new Subnets ($Database);

# verify that user is logged in
$User->check_user_session();

# check that multicast is enabled
if ($User->settings->enableMulticast!="1")          { die("True"); }

# default vlan/id if not set
if (is_blank($_POST['vlanId']))                    { $_POST['vlanId'] = 0; }
if (is_blank($_POST['id']))                        { $_POST['id'] = 0; }

# validations
if (strlen($_POST['mac'])>21)                       { die("True"); }
if (!is_numeric($_POST['sectionId']))               { die("True"); }
if (!is_numeric($_POST['vlanId']))                  { die("True"); }
if (!is_numeric($_POST['id']))                      { die("True"); }

# if address is not multicast return true
if ($Subnets->validate_ip ($_POST['ip'])===false)   { die("True"); }
if ($Subnets->is_multicast ($_POST['ip'])===false)  { die("True"); }

# validate
# change last parameter to section / vlan
$text = $Subnets->validate_multicast_mac($_POST['mac'], $_POST['sectionId'], $_POST['vlanId'], MCUNIQUE, $_POST['id']);

# validate mac
if ($text===true)  { die("True"); }
else               { print $text; }

?>