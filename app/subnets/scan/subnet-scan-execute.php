<?php

/*
 * Discover new hosts with ping
 *******************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Tools	 	= new Tools ($Database);
$Admin	 	= new Admin ($Database, false);
$Sections	= new Sections ($Database);
$Subnets	= new Subnets ($Database);
$Addresses	= new Addresses ($Database);
$Scan	 	= new Scan ($Database, $User->settings);
$DNS	 	= new DNS ($Database, $User->settings);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();
# check maintaneance mode
$User->check_maintaneance_mode ();

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "scan", $POST->csrf_cookie) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# scan type
$type = !empty($POST->type) ? $POST->type : "";

# flag for ajax-loaded
$ajax_loaded = isset($POST->ajax_loaded) ? true : false;

if (!($type==="snmp-route" || ($type==="snmp-route-all" && $ajax_loaded))) {
    # subnet Id must be a integer
    if(!is_numeric($POST->subnetId))	{ $Result->show("danger", _("Invalid ID"), true); }

    # verify that user has write permissionss for subnet
    if($Subnets->check_permission ($User->user, $POST->subnetId) != 3) 	{ $Result->show("danger", _('You do not have permissions to modify hosts in this subnet')."!", true, true); }

    # fetch subnet details
    $subnet = $Subnets->fetch_subnet (null, $POST->subnetId);
    $subnet!==false ? : $Result->show("danger", _("Invalid ID"), true, true);

    # fake sectionId for snmp-route-all scan
    $POST->sectionId = $subnet->sectionId;

    # full
    if ($POST->type!="update-icmp" && $subnet->isFull==1)                { $Result->show("warning", _("Cannot scan as subnet is market as used"), true, true); }

    # verify php path
    if(!file_exists($Scan->php_exec))	{ $Result->show("danger", _("Invalid php path"), true, true); }
}

switch ($type) {
#scan
    case "scan-icmp":
    case "scan-telnet":
    case "scan-snmp-arp":
    case "snmp-mac":
    case "snmp-route":
    case "snmp-route-all":
# discovery
    case "update-icmp":
    case "update-snmp-arp":
        $csrf = $POST->csrf_cookie;
        require("subnet-scan-execute-$type.php");
        break;
    default:
        $Result->show("danger", _("Invalid scan type").' ('.$type.')', true);
}
