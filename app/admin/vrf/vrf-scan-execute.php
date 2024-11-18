<?php

/*
 * Discover new vrfs with snmp
 *******************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# Don't corrupt output with php errors!
disable_php_errors();

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database, false);
$Tools	 	= new Tools ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();
# perm check
$User->check_module_permissions ("vrf", User::ACCESS_RWA, true, false);

# fake error
print "<div class='alert-danger hidden'></div>";

# scan disabled
if ($User->settings->enableSNMP!="1")           { $Result->show("danger", _("SNMP module disabled"), true); }
# admin check
if($User->is_admin()!==true) 	                { $Result->show("danger", _('Admin privileges required'), true); }

# set class
$Snmp = new phpipamSNMP ();

# get existing vrfs
$existing_vrfs = $Tools->fetch_all_objects ("vrf", "vrfId");
if ($existing_vrfs!==false) {
    foreach ($existing_vrfs as $v) {
        $ex_vrfs[$v->name] = $v->rd;
    }
}

# set devices
foreach ($POST as $k=>$p) {
    if (strpos($k, "device-")!==false) {
        # fetch device
        $device = $Tools->fetch_object ("devices", "id", str_replace("device-", "", $k));
        if ($device !== false) {
            $scan_devices[] = $device;
        }
    }
}

// if none set die
if (!isset($scan_devices))                      { $Result->show("danger", _("No devices for SNMP VRF query available"), true); }

// init result array
$new_vrfs = array();

// ok, we have devices, connect to each device and do query
foreach ($scan_devices as $d) {
    // init
    $Snmp->set_snmp_device ($d);
    // fetch arp table
    try {
        $res = $Snmp->get_query("get_vrf_table");
        // remove those not in subnet
        if (is_array($res) && sizeof($res)>0) {
           // save for debug
           $debug[$d->hostname]["get_vrf_table"] = $res;
           // loop and save
           foreach ($res as $k=>$r) {
               if (!array_key_exists($k, $new_vrfs) && !array_key_exists($k, $ex_vrfs) ) {
                   $new_vrfs[$k] = $r;
               }
           }
        }
     } catch (Exception $e) {
       // save for debug
       $debug[$d->hostname]["get_vrf_table"] = $res;
       $errors[] = $e->getMessage();
	}
}

# none and errors
if(sizeof($new_vrfs)==0 && isset($errors)) {
    $Result->show("info", _("No vrfs found"), false);
    $Result->show("warning", implode("<hr>", $errors), false);
}
# none
elseif(sizeof($new_vrfs)==0) 	                     { $Result->show("info", _("No vrfs found")."!", false); }
# ok
else {
    // fetch custom fields and check for required
    $required_fields = $Tools->fetch_custom_fields ('vrf');
    if($required_fields!==false) {
        foreach ($required_fields as $k=>$f) {
            if ($f['Null']!="NO") {
                unset($required_fields[$k]);
            }
        }
    }

    // calculate colspan
	$colspan = 4 + sizeof(@$required_fields);


	//form
	print "<form name='scan-snmp-vrf-form' class='scan-snmp-arp-form' id='scan-snmp-vrf-form'>";
	print "<table class='table table-striped table-top table-condensed'>";

	// titles
	print "<tr>";
	print "	<th>"._("Name")."</th>";
	print "	<th>"._("RD")."</th>";
	print "	<th>"._("Description")."</th>";
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
	foreach ($new_vrfs as $name=>$data ) {
        print "<tr class='result$m'>";
		//name
		print "<td>";
		print "$name<input type='hidden' name='name$m' value='$name'>";
		print "</td>";
		//rd
		print "<td>";
		print "	<input type='text' class='form-control input-sm' name='rd$m' value='".$data['rd']."'>";
		print "</td>";
		//description
		print "<td>";
		print "	<input type='text' class='form-control input-sm' name='description$m' value='".$data['descr']."'>";
		print "</td>";
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

	//submit
	print "<tr>";
	print "	<td colspan='$colspan'>";
	print " <div id='vrfScanAddResult'></div>";
	print "		<a href='' class='btn btn-sm btn-success pull-right' id='saveVrfScanResults'><i class='fa fa-plus'></i> "._("Add discovered vrfs")."</a>";
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
print " Scan method: SNMP VRF table<hr>";
print " Scanned devices: <br>";
foreach ($debug as $k=>$d) {
    print "&middot; ".$k."<br>";
}
print "</span>";
print "</div>";

# show debug?
if($POST->debug==1) 				{ print "<pre>"; print_r($debug); print "</pre>"; }
