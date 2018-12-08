<?php

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database, false);
$Subnets	= new Subnets ($Database);
$Sections	= new Sections ($Database);
$Addresses	= new Addresses ($Database);
$Tools      = new Tools ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "scan", $_POST['csrf_cookie']) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";


$type = $_POST['type'];

switch ($type) {
    case "scan-icmp":
    case "scan-telnet":
    case "snmp-arp":
    case "snmp-mac":
    case "snmp-route-all":
        $subnet_scan_result_included = true;
        require("subnet-scan-result-$type.php");
        break;
    default:
        $Result->show("danger", _("Invalid scan type"), true);
}