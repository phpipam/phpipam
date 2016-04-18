<?php

/*
 * Discover new hosts with snmp
 *******************************/

# verify that user is logged in
$User->check_user_session();

# create csrf token
$csrf = $User->csrf_cookie ("create", "scan");

//title
print "<h5>"._('Scan results').":</h5><hr>";

# verify that user has write permissionss for subnet
if($Subnets->check_permission ($User->user, $_POST['subnetId']) != 3) 	{ $Result->show("danger", _('You do not have permissions to modify hosts in this subnet')."!", true, true); }


# scan disabled
if ($User->settings->enableSNMP!="1")           { $Result->show("danger", _("SNMP module disbled"), true); }
# subnet check
$subnet = $Subnets->fetch_object ("subnets", "id", $_POST['subnetId']);
if ($subnet===false)                            { $Result->show("danger", _("Invalid subnet Id"), true);  }

# fetch vlan
$vlan = $Tools->fetch_object ("vlans", "vlanId", $subnet->vlanId);
if ($vlan===false)                              { $Result->show("danger", _("Subnet must have VLAN assigned for MAc address query"), true);  }

# set class
$Snmp = new phpipamSNMP ();

// fetch all existing hosts
$all_subnet_hosts = (array) $Addresses->fetch_subnet_addresses ($subnet->id);
// reindex
if (sizeof($all_subnet_hosts)>0) {
    foreach ($all_subnet_hosts as $h) {
        $subnet_ip_addresses[] = $Subnets->transform_address($h->ip_addr, "dotted");
    }
}

# set selected address fields array
$selected_ip_fields = $User->settings->IPfilter;
$selected_ip_fields = explode(";", $selected_ip_fields);

// no errors
error_reporting(E_ERROR);

# fetch devices that use get_routing_table query
$devices_used_arp = $Tools->fetch_multiple_objects ("devices", "snmp_queries", "%get_arp_table%", "id", true, true);
$devices_used_mac = $Tools->fetch_multiple_objects ("devices", "snmp_queries", "%get_mac_table%", "id", true, true);

# filter out devices not in this section - ARP
if ($devices_used_arp !== false) {
    foreach ($devices_used_arp as $d) {
        // get possible sections
        $permitted_sections = explode(";", $d->sections);
        // check
        if (in_array($subnet->sectionId, $permitted_sections)) {
            $permitted_devices_arp[] = $d;
        }
    }
}
# filter out not in this section
if ($devices_used_mac !== false) {
    foreach ($devices_used_mac as $d) {
        // get possible sections
        $permitted_sections = explode(";", $d->sections);
        // check
        if (in_array($subnet->sectionId, $permitted_sections)) {
            $permitted_devices_mac[] = $d;
        }
    }
}

// if none set die
if (!isset($permitted_devices_arp))                 { $Result->show("danger", _("No devices for SNMP ARP query available"), true); }
if (!isset($permitted_devices_mac))                 { $Result->show("danger", _("No devices for SNMP MAC address query available"), true); }


// first we need ARP table to fetchIP <> MAC mappings
foreach ($permitted_devices_arp as $d) {
    // init
    $Snmp->set_snmp_device ($d);
    // fetch arp table
    try {
        $res = $Snmp->get_query("get_arp_table");
        // remove those not in subnet
        if (sizeof($res)>0) {
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
                   $found_arp[] = $r;
               }
           }
        }
    } catch (Exception $e) {
       // save for debug
       $debug[$d->hostname]['get_arp_table'] = $res;
       $errors[] = $e->getMessage();
	}
}

// if none found via ARP die
if (!isset($found_arp))                         { $Result->show("danger", _("No new hosts found from ARP scan, MAC address scan will not be performed"), true); }


// ok, we have devices, connect to each device and do query
foreach ($permitted_devices_mac as $d) {
    // init
    $Snmp->set_snmp_device ($d, $vlan->number);
    // fetch mac table
    try {
        $res = $Snmp->get_query("get_mac_table");
        // remove those not in subnet
        if (sizeof($res)>0) {
           // save for debug
           $debug[$d->hostname]["get_mac_table"] = $res;
           // save found
           foreach ($res as $r) {
               $r['device'] = $d->id;
               $r['device_name'] = $d->hostname;
               $found_mac[] = $r;
           }
        }
    } catch (Exception $e) {
       // save for debug
       $debug[$d->hostname]['get_mac_table'] = $res;
       $errors[] = $e->getMessage();
	}
}


// if none found via ARP die
if (!isset($found_mac))                         { $Result->show("danger", _("No MAC address found via MAC address scan"), true); }



// now check for match
$k=0;
foreach ($found_mac as $mac) {
    foreach ($found_arp as $arp) {
        // check for match
        if ($mac['mac']==$arp['mac']) {
            $found[$k]['ip']     = $arp['ip'];
            $found[$k]['mac']    = $mac['mac'];
            $found[$k]['port']   = $mac['port'];
            $found[$k]['device'] = $mac['device'];
            $found[$k]['device_name'] = $mac['device_name'];
            $found[$k]['port_alias'] = $mac['port_alias'];
            // next index
            $k++;
        }
    }
}

// remove duplicates
foreach ($found as $k1=>$f) {
    foreach ($found as $k2=>$f1) {
        if ($k1!=$k2) {
            if ($f['mac']==$f1['mac'] && $f['device']==$f1['device']) {
                unset($found[$k1]);
            }
        }
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
	$colspan = 7 + sizeof(@$required_fields);
	// port
	if(in_array('port', $selected_ip_fields)) { $colspan++; }

    /**
     * Sorts array by ip
     */
    function sort_array($a, $b) {
        // same
        if ($a['ip']==$b['ip'])     { return 0; }
        elseif ($a['ip']>$b['ip'])  { return 1; }
        else                        { return -1; }
    }
    // sort ip addresses
    usort($found, "sort_array");


	//form
	print "<form name='scan-snmp-mac-form' class='scan-snmp-mac-form'>";
	print "<table class='table table-striped table-top table-condensed'>";

	// titles
	print "<tr>";
	print "	<th>"._("IP")."</th>";
	print "	<th>"._("Description")."</th>";
	print "	<th>"._("MAC")."</th>";
	print "	<th>"._("Device")."</th>";
	print "	<th>"._("Hostname")."</th>";
	print "	<th>"._('Port')."</th>";
	print " <th></th>";
    // custom
	if (isset($required_fields)) {
		foreach ($required_fields as $field) {
            print "<th>"._($field['name'])."</th>";
		}
    }
	print "	<th></th>";
	print "</tr>";

	// alive
	$m=0;
	foreach($found as $k=>$ip) {
            print "<tr class='result$m'>";
    		//resolve?
    		$hostname = $DNS->resolve_address($ip['ip'], false, true, $nsid);

    		//ip - done print same !
    		if ($k!=0) {
        		if ($found[$k]['ip'] != $found[$k-1]['ip']) {
        		    print "<td><span class='ip-address'>$ip[ip]</span></td>";
        		}
        		else {
            		print "<td><span class='ip-address hidden'>$ip[ip]</span></td>";
        		}
    		}
    		else {
        		print "<td><span class='ip-address'>$ip[ip]</span></td>";
            }
    		//description, ip, device
    		print "<td>";
    		print "	<input type='text' class='form-control input-sm' name='description$m'>";
    		print "	<input type='hidden' name='ip$m' value='$ip[ip]'>";
    		print "	<input type='hidden' name='device$m' value='$ip[device]'>";
    		print " <input type='hidden' name='csrf_cookie' value='$csrf'>";
    		print "</td>";
    		// mac
    		print "<td>";
    		print "	<input type='text' class='form-control input-sm' name='mac$m' value='$ip[mac]'>";
    		print "</td>";
    		// device
    		print "<td>$ip[device_name]</td>";
    		//hostname
    		print "<td>";
    		print "	<input type='text' class='form-control input-sm' name='dns_name$m' value='".@$hostname['name']."'>";
    		print "</td>";
    		// port
    		print "<td>";
    		print "	<input type='text' class='form-control input-sm' name='port$m' value='".@$ip['port']."'>";
    		print "</td>";
    		// info
    		print "<td>";
    		if(strlen(@$ip['port_alias'])>0)
    		print "	<i class='fa fa-info-circle' rel='tooltip' title='Interface description: $ip[port_alias]'></i>";
    		print "</td>";
    		// custom
    		if (isset($required_fields)) {
        		foreach ($required_fields as $field) {
        			# replace spaces with |
        			$field['nameNew'] = str_replace(" ", "___", $field['name']);

        			print '	<td>'. "\n";

        			//set type
        			if(substr($field['type'], 0,3) == "set" || substr($field['type'], 0,4) == "enum") {
        				//parse values
        				$tmp = substr($field['type'], 0,3)=="set" ? explode(",", str_replace(array("set(", ")", "'"), "", $field['type'])) : explode(",", str_replace(array("enum(", ")", "'"), "", $field['type']));
        				//null
        				if($field['Null']!="NO") { array_unshift($tmp, ""); }

        				print "<select name='$field[nameNew]$m' class='form-control input-sm input-w-auto' rel='tooltip' data-placement='right' title='$field[Comment]'>";
        				foreach($tmp as $v) {
        					if($v==@$address[$field['name']])	{ print "<option value='$v' selected='selected'>$v</option>"; }
        					else								{ print "<option value='$v'>$v</option>"; }
        				}
        				print "</select>";
        			}
        			//date and time picker
        			elseif($field['type'] == "date" || $field['type'] == "datetime") {
        				// just for first
        				if($timeP==0) {
        					print '<link rel="stylesheet" type="text/css" href="css/1.2/bootstrap/bootstrap-datetimepicker.min.css">';
        					print '<script type="text/javascript" src="js/1.2/bootstrap-datetimepicker.min.js"></script>';
        					print '<script type="text/javascript">';
        					print '$(document).ready(function() {';
        					//date only
        					print '	$(".datepicker").datetimepicker( {pickDate: true, pickTime: false, pickSeconds: false });';
        					//date + time
        					print '	$(".datetimepicker").datetimepicker( { pickDate: true, pickTime: true } );';

        					print '})';
        					print '</script>';
        				}
        				$timeP++;

        				//set size
        				if($field['type'] == "date")	{ $size = 10; $class='datepicker';		$format = "yyyy-MM-dd"; }
        				else							{ $size = 19; $class='datetimepicker';	$format = "yyyy-MM-dd"; }

        				//field
        				if(!isset($address[$field['name']]))	{ print ' <input type="text" class="'.$class.' form-control input-sm input-w-auto" data-format="'.$format.'" name="'. $field['nameNew'].$m .'" maxlength="'.$size.'" '.$delete.' rel="tooltip" data-placement="right" title="'.$field['Comment'].'">'. "\n"; }
        				else									{ print ' <input type="text" class="'.$class.' form-control input-sm input-w-auto" data-format="'.$format.'" name="'. $field['nameNew'].$m .'" maxlength="'.$size.'" value="'. $address[$field['name']]. '" '.$delete.' rel="tooltip" data-placement="right" title="'.$field['Comment'].'">'. "\n"; }
        			}
        			//boolean
        			elseif($field['type'] == "tinyint(1)") {
        				print "<select name='$field[nameNew]$m' class='form-control input-sm input-w-auto' rel='tooltip' data-placement='right' title='$field[Comment]'>";
        				$tmp = array(0=>"No",1=>"Yes");
        				//null
        				if($field['Null']!="NO") { $tmp[2] = ""; }

        				foreach($tmp as $k=>$v) {
        					if(strlen(@$address[$field['name']])==0 && $k==2)	{ print "<option value='$k' selected='selected'>"._($v)."</option>"; }
        					elseif($k==@$address[$field['name']])				{ print "<option value='$k' selected='selected'>"._($v)."</option>"; }
        					else												{ print "<option value='$k'>"._($v)."</option>"; }
        				}
        				print "</select>";
        			}
        			//default - input field
        			else {
        				print ' <input type="text" class="ip_addr form-control input-sm" name="'. $field['nameNew'].$m .'" placeholder="'. $field['name'] .'" value="'. @$address[$field['name']]. '" size="30" '.$delete.' rel="tooltip" data-placement="right" title="'.$field['Comment'].'">'. "\n";
        			}

                    print " </td>";
        		}
    		}
    		//remove button
    		print 	"<td><a href='' class='btn btn-xs btn-danger resultRemove resultRemoveMac' data-target='result$m'><i class='fa fa-times'></i></a></td>";
    		print "</tr>";

    		$m++;
	}

	//submit
	print "<tr>";
	print "	<td colspan='$colspan'>";
	print "<div id='subnetScanAddResult'></div>";
	print "		<a href='' class='btn btn-sm btn-success pull-right' id='saveScanResults' data-script='scan-snmp-mac' data-subnetId='".$_POST['subnetId']."'><i class='fa fa-plus'></i> "._("Add discovered hosts")."</a>";
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

?>