<?php

/**
 * Edit snmp result
 ***************************/

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

// no errors
error_reporting(E_ERROR);

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# validate csrf cookie
$_POST['csrf_cookie']==$_SESSION['csrf_cookie'] ? :             $Result->show("danger", _("Invalid CSRF cookie"), true, false, false, true);

# get modified details
$device = $Admin->strip_input_tags($_POST);

# ID, port snd community must be numeric
if(!is_numeric($_POST['device_id']))			              { $Result->show("danger", _("Invalid ID"), true, false, false, true); }
if(!is_numeric($_POST['snmp_version']))			              { $Result->show("danger", _("Invalid version"), true, false, false, true); }
if($_POST['snmp_version']!=0) {
if(!is_numeric($_POST['snmp_port']))			              { $Result->show("danger", _("Invalid port"), true, false, false, true); }
if(!is_numeric($_POST['snmp_timeout']))			              { $Result->show("danger", _("Invalid timeout"), true, false, false, true); }
}

# version can be 0, 1 or 2
if ($_POST['snmp_version']<0 || $_POST['snmp_version']>2)     { $Result->show("danger", _("Invalid version"), true, false, false, true); }

# validate device
$device = $Admin->fetch_object ("devices", "id", $_POST['device_id']);
if($device===false)                                           { $Result->show("danger", _("Invalid device"), true, false, false, true); }

# set new snmp variables
$device->snmp_community = $_POST['snmp_community'];
$device->snmp_version   = $_POST['snmp_version'];
$device->snmp_port      = $_POST['snmp_port'];
$device->snmp_timeout   = $_POST['snmp_timeout'];

# init snmp class
$Snmp = new phpipamSNMP ($Database, $device);


# set queries
foreach($_POST as $k=>$p) {
    if(strpos($k, "query-")!==false) {
        if($p=="on") {
            $queries[] = substr($k, 6);
        }
    }
}

# reindex queries and get details
foreach ($Snmp->snmp_queries as $q) {
    if (in_array($q->id, $queries)) {
        $queries_parsed[] = $q;
    }
}

# save all queries
$device->snmp_queries = implode(";", $queries);

# open connection
if (isset($queries_parsed)) {
    // loop
    foreach($queries_parsed as $kk=>$q) {
        // remove old
        $method = false;

        try {
            # try to get details
            if ($q->method == "info")        { $method = "get_sysinfo"; }
            elseif ($q->method == "arp")     { $method = "get_arp_table";; }
            elseif ($q->method == "route")   { $method = "get_routing_table"; }

            # execute
            if ($method!==false) {
                $debug[$kk]['method'] = $method;
                $debug[$kk]['oid']    = $q->oid;
                $debug[$kk]['result'] = $Snmp->$method($device, $q->id);
                # print
                if ($method=="get_sysinfo") {
                    $res[] = $Result->show("success", "<strong>$method</strong>: OK<hr> <pre>".$Snmp->last_result."</pre>", false, false, true);
                }
                else {
                    $res[] = $Result->show("success", "<strong>$method</strong>: OK", false, false, true);
                }
            }
            else {
                $res[] = $Result->show("warning", "<strong>$method</strong><hr> "._("Invalid method"), false, false, true);
            }
        } catch ( Exception $e ) {
             $res[] = $Result->show("danger", "<strong>$method</strong><hr> ".$e->getMessage(), false, false, true);
        }
    }

    // debug
    $res[] = "<hr>";
    $res[] = "<div class='text-right'>";
    $res[] = "  <a class='btn btn-sm btn-default pull-right' id='toggle_debug'>Toggle debug</a>";
    $res[] = "</div>";
    $res[] = " <pre id='debug' style='display:none;'>";
    $res[] = print_r($debug, true);
    $res[] = "</pre>";

    //print
    $Result->show("Query result", implode("<br>", $res), false, true, false, true);
}
else {
   $Result->show("warning", _("No queries"), false, true, false, true);
}
?>


<script type="text/javascript">
$(document).ready(function(){
    $('#toggle_debug').click(function() { $('#debug').toggle() });
});