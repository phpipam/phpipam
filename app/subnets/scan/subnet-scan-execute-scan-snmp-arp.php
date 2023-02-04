<?php

# Check we have been included and not called directly
require( dirname(__FILE__) . '/../../../functions/include-only.php' );

# Don't corrupt output with php errors!
disable_php_errors();

/*
 * Discover new hosts with snmp
 *******************************/

//title
print "<h5>"._('Scan results').":</h5><hr>";

# scan disabled
if ($User->settings->enableSNMP!="1")           { $Result->show("danger", _("SNMP module disbled"), true); }
# subnet check
$subnet = $Subnets->fetch_subnet ("id", $_POST['subnetId']);
if ($subnet===false)                            { $Result->show("danger", _("Invalid subnet Id"), true);  }

# verify that user has write permissionss for subnet
if($Subnets->check_permission ($User->user, $_POST['subnetId']) != 3) 	{ $Result->show("danger", _('You do not have permissions to modify hosts in this subnet')."!", true, true); }

# set class
$Snmp = new phpipamSNMP ();

// fetch all existing hosts
$all_subnet_hosts = (array) $Addresses->fetch_subnet_addresses ($_POST['subnetId']);
// reindex
if (sizeof($all_subnet_hosts)>0) {
    foreach ($all_subnet_hosts as $h) {
        $subnet_ip_addresses[] = $Subnets->transform_address($h->ip_addr, "dotted");
    }
}

# set selected address fields array
$selected_ip_fields = $User->settings->IPfilter;
$selected_ip_fields = pf_explode(";", $selected_ip_fields);

# fetch devices that use get_routing_table query
$devices_used = $Tools->fetch_multiple_objects ("devices", "snmp_queries", "%get_arp_table%", "id", true, true);

# filter out not in this section
if ($devices_used !== false) {
    foreach ($devices_used as $d) {
        // get possible sections
        $permitted_sections = pf_explode(";", $d->sections);
        // check
        if (in_array($subnet->sectionId, $permitted_sections)) {
            $permitted_devices[] = $d;
        }
    }
}

// if none set die
if (!isset($permitted_devices))                 { $Result->show("danger", _("No devices for SNMP ARP query available"), true); }

// ok, we have devices, connect to each device and do query
foreach ($permitted_devices as $d) {
    // init
    $Snmp->set_snmp_device ($d);
    // fetch arp table
    try {
        $res = $Snmp->get_query("get_arp_table");
        // remove those not in subnet
        if (is_array($res) && sizeof($res)>0) {
           // save for debug
           $debug[$d->hostname]["get_arp_table"] = $res;
           // check
           foreach ($res as $kr=>$r) {
               // if is inside subnet
               if ($Subnets->is_subnet_inside_subnet ($r['ip']."/32", $Subnets->transform_address($subnet->subnet, "dotted")."/".$subnet->mask)===false) { }
               // check if host already exists, than remove it
               elseif (in_array($r['ip'], $subnet_ip_addresses)) { }
               // save
               else {
                   $found[$d->id][] = $r;
               }
           }
        }
        // get interfaces
        $res = $Snmp->get_query("get_interfaces_ip");
        // remove those not in subnet
        if (is_array($res) && sizeof($res)>0) {
           // save for debug
           $debug[$d->hostname]["get_interfaces_ip"] = $res;
           // check
           foreach ($res as $kr=>$r) {
               // if is inside subnet
               if ($Subnets->is_subnet_inside_subnet ($r['ip']."/32", $Subnets->transform_address($subnet->subnet, "dotted")."/".$subnet->mask)===false) { }
               // check if host already exists, than remove it
               elseif (in_array($r['ip'], $subnet_ip_addresses)) { }
               // save
               else {
                   $found[$d->id][] = $r;
               }
           }
        }
    } catch (Exception $e) {
       // save for debug
       $debug[$d->hostname]['get_arp_table'] = $res;
       $errors[] = $e->getMessage();
	}
}

# none and errors
if(sizeof($found)==0 && isset($errors))          {
    $Result->show("info", _("No new hosts found"), false);
    $Result->show("warning", implode("<hr>", $errors), false);
}
# none
elseif(sizeof($found)==0) 	                     { $Result->show("info", _("No new hosts found")."!", false); }
# ok
else {
	// fetch subnet and set nsid
	$nsid = $subnet===false ? false : $subnet->nameserverId;

    // fetch custom fields and check for required
    $Tools = new Tools ($Database);
    $required_fields = $Tools->fetch_custom_fields ('ipaddresses');
    if($required_fields!==false) {
        foreach ($required_fields as $k=>$f) {
            if ($f['Null']!="NO") {
                unset($required_fields[$k]);
            }
        }
    }

    // calculate colspan
	$colspan = 5 + sizeof(@$required_fields);
	// port
	if(in_array('port', $selected_ip_fields)) { $colspan++; }


	//form
	print "<form name='scan-snmp-arp-form' class='scan-snmp-arp-form'>";
	print "<input type='hidden' name='csrf_cookie' value='$csrf'>";
	print "<table class='table table-striped table-top table-condensed'>";

	// titles
	print "<tr>";
	print "	<th>"._("IP")."</th>";
	print "	<th>"._("Description")."</th>";
	print "	<th>"._("MAC")."</th>";
	print "	<th>"._("Hostname")."</th>";
	// port
	if(in_array('port', $selected_ip_fields)) {
	print "	<th>"._('Port')."</th>";
	}
    // custom
	if (isset($required_fields)) {
		foreach ($required_fields as $field) {
            print "<th>".$Tools->print_custom_field_name($field['name'])."</th>";
		}
    }
	print "	<th></th>";
	print "</tr>";

	// alive
	$m=0;
	foreach($found as $deviceid=>$device) {
    	foreach ($device as $ip ) {
            print "<tr class='result$m'>";
    		//resolve?
    		$hostname = $DNS->resolve_address($ip['ip'], false, true, $nsid);

    		//ip
    		print "<td>$ip[ip]</td>";
    		//description, ip, device
    		print "<td>";
    		print "	<input type='text' class='form-control input-sm' name='description$m'>";
    		print "	<input type='hidden' name='ip$m' value='$ip[ip]'>";
    		print "	<input type='hidden' name='device$m' value='$deviceid'>";
    		print "</td>";
    		// mac
    		print "<td>";
    		print "	<input type='text' class='form-control input-sm' name='mac$m' value='$ip[mac]'>";
    		print "</td>";
    		//hostname
    		print "<td>";
    		print "	<input type='text' class='form-control input-sm' name='hostname$m' value='".@$hostname['name']."'>";
    		print "</td>";
    		// port
    		if(in_array('port', $selected_ip_fields)) {
    		print "<td>";
    		print "	<input type='text' class='form-control input-sm' name='port$m' value='".@$ip['port']."'>";
    		print "</td>";
    		}
			// custom
			if (isset($required_fields)) {
				$timepicker_index = 0;
				foreach ($required_fields as $field) {
					$custom_input = $Tools->create_custom_field_input ($field, $address, $timepicker_index, false, '', $m);
					$timepicker_index = $custom_input['timepicker_index'];

					print "<td>".$custom_input['field']."</td>\n";
				}
			}
    		//remove button
    		print 	"<td><a href='' class='btn btn-xs btn-danger resultRemove' data-target='result$m'><i class='fa fa-times'></i></a></td>";
    		print "</tr>";

    		$m++;
		}
	}

	//submit
	print "<tr>";
	print "	<td colspan='$colspan'>";
	print "<div id='subnetScanAddResult'></div>";
	print "		<a href='' class='btn btn-sm btn-success pull-right' id='saveScanResults' data-script='scan-snmp-arp' data-subnetId='".$_POST['subnetId']."'><i class='fa fa-plus'></i> "._("Add discovered hosts")."</a>";
	print "	</td>";
	print "</tr>";

	print "</table>";
	print "</form>";

    // print errors
    if (isset($errors)) {
        print "<hr>";
        foreach ($errors as $e) {
            print $Result->show ("warning", $e, false);
        }
    }
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