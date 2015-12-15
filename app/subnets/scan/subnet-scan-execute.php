<?php

/*
 * Discover new hosts with ping
 *******************************/

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Subnets	= new Subnets ($Database);
$Addresses	= new Addresses ($Database);
$Scan	 	= new Scan ($Database, $User->settings);
$DNS	 	= new DNS ($Database, $User->settings);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# subnet Id must be a integer
if(!is_numeric($_POST['subnetId']))	{ $Result->show("danger", _("Invalid ID"), true); }

# verify that user has write permissionss for subnet
if($Subnets->check_permission ($User->user, $_POST['subnetId']) != 3) 	{ $Result->show("danger", _('You do not have permissions to modify hosts in this subnet')."!", true); }

# fetch subnet details
$subnet = $Subnets->fetch_subnet (null, $_POST['subnetId']);
$subnet!==false ? : $Result->show("danger", _("Invalid ID"), true, true);

# full
if ($_POST['type']!="update-icmp" && $subnet->isFull==1)                { $Result->show("warning", _("Cannot scan as subnet is market as used"), true); }

# verify ping path
if(!file_exists($Scan->php_exec))	{ $Result->show("danger", _("Invalid ping path"), true); }



# invoke proper script!
if($_POST['type']=="scan-icmp")			{ include("subnet-scan-icmp.php"); }
elseif($_POST['type']=="scan-telnet")	{ include("subnet-scan-telnet.php"); }
elseif($_POST['type']=="update-icmp")	{ include("subnet-update-icmp.php"); }
else									{ $Result->show("danger", _("Invalid scan type"), true); }

?>