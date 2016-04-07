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
$selected_ip_fields = explode(";", $selected_ip_fields);

// no errors
error_reporting(E_ERROR);

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
if (!isset($permitted_devices))                 { $Result->show("danger", _("No devices for SNMP ARP query available"), true); }

// ok, we have devices, connect to each device and do query
foreach ($permitted_devices as $d) {
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
                   $found[$d->id][] = $r;
               }
           }
        }
        // get interfaces
        $res = $Snmp->get_query("get_interfaces_ip");
        // remove those not in subnet
        if (sizeof($res)>0) {
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
       $debug[$d->hostname][$q] = $res;
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
            print "<th>"._($field['name'])."</th>";
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
    		print " <input type='hidden' name='csrf_cookie' value='$csrf'>";
    		print "</td>";
    		// mac
    		print "<td>";
    		print "	<input type='text' class='form-control input-sm' name='mac$m' value='$ip[mac]'>";
    		print "</td>";
    		//hostname
    		print "<td>";
    		print "	<input type='text' class='form-control input-sm' name='dns_name$m' value='".@$hostname['name']."'>";
    		print "</td>";
    		// port
    		if(in_array('port', $selected_ip_fields)) {
    		print "<td>";
    		print "	<input type='text' class='form-control input-sm' name='port$m' value='".@$ip['port']."'>";
    		print "</td>";
    		}
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

?>