<?php

/*
 * Discover new hosts with snmp
 *******************************/

# verify that user is logged in
$User->check_user_session();

# scan disabled
if ($User->settings->enableSNMP!="1")           { $Result->show("danger", "SNMP module disbled", true); }
# subnet check
$subnet = $Subnets->fetch_subnet ("id", $_POST['subnetId']);
if ($subnet===false)                            { $Result->show("danger", "Invalid subnet Id", true);  }

# verify that user has write permissionss for subnet
if($Subnets->check_permission ($User->user, $_POST['subnetId']) != 3) 	{ $Result->show("danger", _('You do not have permissions to modify hosts in this subnet')."!", true, true); }

# set class
$Snmp = new phpipamSNMP ();

// no errors
error_reporting(E_ERROR);

// fetch all hosts to be scanned
$all_subnet_hosts = (array) $Addresses->fetch_subnet_addresses ($_POST['subnetId']);

// execute only if some exist
if (sizeof($all_subnet_hosts)>0) {
    // set default statuses
    foreach ($all_subnet_hosts as $h) {
        $result[$h->ip_addr] = (array) $h;
        $result[$h->ip_addr]['code'] = 1;
        $result[$h->ip_addr]['status'] = "Offline";
    }

    # fetch devices that use get_routing_table query
    $devices_used = $Tools->fetch_multiple_objects ("devices", "snmp_queries", "%get_arp_table%", "id", true, true);

    # filter out not in this section
    if ($devices_used !== false) {
        foreach ($devices_used as $d) {
            // get possible sections
            $permitted_sections = explode(";", $d->sections);
            // check
            if (in_array($subnet->sectionId, $permitted_sections)) {
                $permitted_devices[] = $d;
            }
        }
    }

    // if none set die
    if (!isset($permitted_devices))                 { $Result->show("danger", "No devices for SNMP ARP query available", true); }

    // ok, we have devices, connect to each device and do query
    foreach ($permitted_devices as $d) {
        // init
        $Snmp->set_snmp_device ($d);
        // execute
        try {
           $res = $Snmp->get_query("get_arp_table");
           // remove those not in subnet
           if (sizeof($res)>0) {
               // save for debug
               $debug[$d->hostname]["get_arp_table"] = $res;
               // check
               foreach ($res as $kr=>$r) {
                   if ($Subnets->is_subnet_inside_subnet ($r['ip']."/32", $Subnets->transform_address($subnet->subnet, "dotted")."/".$subnet->mask)===true) {
                       // must be existing
                       if (array_key_exists($Subnets->transform_address($r['ip'], "decimal"), $result)) {
                           // add to alive
                           $result[$Subnets->transform_address($r['ip'], "decimal")]['code'] = 0;
                           $result[$Subnets->transform_address($r['ip'], "decimal")]['status'] = "Online";
                           // update alive time
                           @$Scan->ping_update_lastseen($result[$Subnets->transform_address($r['ip'], "decimal")]['id']);
                       }
                   }
               }
           }
           $found[$d->id] = $res;

         } catch (Exception $e) {
    		$Result->show("danger", "<pre>"._("Error").": ".$e."</pre>", false); ;
    		die();
    	}
    }
}
?>




<h5><?php print _('Scan results');?>:</h5>
<hr>

<?php
# empty
if(sizeof($all_subnet_hosts)==0) 			{ $Result->show("info", _("Subnet is empty")."!", false); }
# ok
else {
	//table
	print "<table class='table table-condensed table-top'>";

	//headers
	print "<tr>";
	print "	<th>"._('IP')."</th>";
	print "	<th>"._('Description')."</th>";
	print "	<th>"._('status')."</th>";
	print "	<th>"._('hostname')."</th>";
	print "</tr>";

	//loop
	foreach($result as $r) {
		//set class
		if($r['code']==0)		{ $class='success'; }
		elseif($r['code']==100)	{ $class='warning'; }
		else					{ $class='danger'; }

		print "<tr class='$class'>";
		print "	<td>".$Subnets->transform_to_dotted($r['ip_addr'])."</td>";
		print "	<td>".$r['description']."</td>";
		print "	<td>"._("$r[status]")."</td>";
		print "	<td>".$r['dns_name']."</td>";

		print "</tr>";
	}
	print "</table>";
}
//print scan method
print "<div class='text-right' style='margin-top:7px;'>";
print " <span class='muted'>";
print " Scan method: SNMP ARP<hr>";
print " Scanned devices: <br>";
foreach ($debug as $k=>$d) {
    print "&middot; ".$k."<br>";
}
print "</span>";
print "</div>";

# show debug?
if($_POST['debug']==1) 				{ print "<pre>"; print_r($debug); print "</pre>"; }
?>