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
if (is_blank($POST->vlanId))                    { $POST->vlanId = 0; }
if (is_blank($POST->id))                        { $POST->id = 0; }

# validations
if (strlen($POST->mac)>21)                       { die("True"); }
if (!is_numeric($POST->sectionId))               { die("True"); }
if (!is_numeric($POST->vlanId))                  { die("True"); }
if (!is_numeric($POST->id))                      { die("True"); }

# if address is not multicast return true
if ($Subnets->validate_ip ($POST->ip)===false)   { die("True"); }
if ($Subnets->is_multicast ($POST->ip)===false)  { die("True"); }

# validate
# change last parameter to section / vlan
$text = $Subnets->validate_multicast_mac($POST->mac, $POST->sectionId, $POST->vlanId, MCUNIQUE, $POST->id);

# validate mac
if ($text===true)  { die("True"); }
else               { print $text; }

?>