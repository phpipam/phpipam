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

# check if $POST input data has been truncated (canary=true input dropped)
if(!isset($POST->canary))
	$Result->show("danger", _("phpIPAM received truncated POST data")."<br>"._("Please check your webserver and/or php.ini setting:"). " `max_input_vars` = ".ini_get('max_input_vars'), true);
else
	unset($POST->canary);

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "scan", $POST->csrf_cookie) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

$type = $POST->type;

switch ($type) {
    case "scan-icmp":
    case "scan-telnet":
    case "scan-snmp-arp":
    case "snmp-mac":
    case "snmp-route-all":
        require("subnet-scan-result-$type.php");
        break;
    default:
        $Result->show("danger", _("Invalid scan type").' ('.escape_input($type).')', true);
}