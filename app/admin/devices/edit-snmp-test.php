<?php

/**
 * Edit snmp result
 ***************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

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
$User->Crypto->csrf_cookie ("validate", "device_snmp", $_POST['csrf_cookie']) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true, true, false, true) : "";

# get modified details
$device = $Admin->strip_input_tags($_POST);

# ID, port snd community must be numeric
if(!is_numeric($_POST['device_id']))			              { $Result->show("danger", _("Invalid ID"), true, true, false, true); }
if(!is_numeric($_POST['snmp_version']))			              { $Result->show("danger", _("Invalid version"), true, true, false, true); }
if($_POST['snmp_version']!=0) {
if(!is_numeric($_POST['snmp_port']))			              { $Result->show("danger", _("Invalid port"), true, true, false, true); }
if(!is_numeric($_POST['snmp_timeout']))			              { $Result->show("danger", _("Invalid timeout"), true, true, false, true); }
if($_POST['snmp_timeout'] > 10000)				              { $Result->show("danger", _("Invalid timeout").' > 10,000ms (10s)', true, true, false, true); }
}

# version can be 0, 1 or 2
if ($_POST['snmp_version']<0 || $_POST['snmp_version']>3)     { $Result->show("danger", _("Invalid version"), true, true, false, true); }

# validate device
$device = $Admin->fetch_object ("devices", "id", $_POST['device_id']);
if($device===false)                                           { $Result->show("danger", _("Invalid device"), true, true, false, true); }

# set new snmp variables
$device->snmp_community          = $_POST['snmp_community'];
$device->snmp_version            = $_POST['snmp_version'];
$device->snmp_port               = $_POST['snmp_port'];
$device->snmp_timeout            = $_POST['snmp_timeout'];
$device->snmp_v3_sec_level       = $_POST['snmp_v3_sec_level'];
$device->snmp_v3_auth_protocol   = $_POST['snmp_v3_auth_protocol'];
$device->snmp_v3_auth_pass       = $_POST['snmp_v3_auth_pass'];
$device->snmp_v3_priv_protocol   = $_POST['snmp_v3_priv_protocol'];
$device->snmp_v3_priv_pass       = $_POST['snmp_v3_priv_pass'];
$device->snmp_v3_ctx_name        = $_POST['snmp_v3_ctx_name'];
$device->snmp_v3_ctx_engine_id   = $_POST['snmp_v3_ctx_engine_id'];

# init snmp class
$Snmp = new phpipamSNMP ();


# set queries
foreach($_POST as $k=>$p) {
    if(strpos($k, "query-")!==false) {
        if($p=="on") {
            $queries[] = substr($k, 6);
        }
    }
}
# fake as device queries
$device->snmp_queries = implode(";", $queries);

# open connection
if (isset($queries)) {
    // set device
    $Snmp->set_snmp_device ($device);

    // loop
    foreach($queries as $query) {
        try {
            // overrides for MAC table query - we need to test with some vlan number, so we need vlan first
            if ($query=="get_mac_table") {
                $Snmp->get_query ("get_vlan_table");
                if (is_array($Snmp->last_result)) {
                    foreach ($Snmp->last_result as $k=>$r) {
                        if (!is_numeric($k)) { continue; }

                        try {
                            // ok, we have vlan, set query
                            $Snmp->set_snmp_device ($device, $k);
                            $Snmp->get_query ($query);
                            $poll_success = true;
                            break;
                        }
                        catch (Exception $e) { $last_error = $e->getMessage(); }
                    }
                    if (!isset($poll_success)) { throw new Exception($last_error); }
                }
            }
            else {
                // reset vlan
                $Snmp->set_snmp_device ($device);
                $Snmp->get_query ($query);
            }

            // ok
            $debug[$query]['oid']    = $Snmp->snmp_queries[$query]->oid;
            $debug[$query]['result'] = $Snmp->last_result;

            $res[] = $Result->show("success", "<strong>$query</strong>: OK<br><span class='text-muted'>".$Snmp->snmp_queries[$query]->description."</span>", false, false, true);

        } catch ( Exception $e ) {
            // fail
            $res[] = $Result->show("danger", "<strong>$query</strong><br><span class='text-muted'>".$Snmp->snmp_queries[$query]->description."</span><hr> ".$e->getMessage(), false, false, true);
        }
    }

    // debug
    $res[] = "<hr>";
    $res[] = "<div class='text-right'>";
    $res[] = "  <a class='btn btn-sm btn-default pull-right' id='toggle_debug'>Toggle debug</a><br><br>";
    $res[] = "</div>";
    $res[] = " <pre id='debug' style='display:none;'>";
    $res[] = print_r($debug, true);
    $res[] = "</pre>";

    //print
    $Result->show("Query result", implode("", $res), false, true, false, true);
}
else {
   $Result->show("warning", _("No queries"), false, true, false, true);
}
?>


<script type="text/javascript">
$(document).ready(function(){
    $('#toggle_debug').click(function() { $('#debug').toggle() });
});