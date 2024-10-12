<?php

/*
 * Discover new vlans with snmp
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
# check maintaneance mode
$User->check_maintaneance_mode ();
# perm check popup
$User->check_module_permissions ("vlan", User::ACCESS_RWA, true, true);
# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "scan", $POST->csrf_cookie) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# fake error
print "<div class='alert-danger hidden'></div>";

# scan disabled
if ($User->settings->enableSNMP!="1")           { $Result->show("danger", _("SNMP module disabled"), true); }
# admin check
if($User->is_admin()!==true) 	                { $Result->show("danger", _('Admin privileges required'), true); }

# set class
$Snmp = new phpipamSNMP ();

# domain Id must be int
if (!is_numeric($POST->domainId))            { $Result->show("danger", _("Invalid domain Id"), true); }
# fetch domain
$domain = $Tools->fetch_object ("vlanDomains", "id", $POST->domainId);
if ($domain===false)                            { $Result->show("danger", _("Invalid domain Id"), true); }

# get existing vlans
$existing_vlans = $Tools->fetch_multiple_objects ("vlans", "domainId", $domain->id, "vlanId");
if ($existing_vlans!==false) {
    foreach ($existing_vlans as $v) {
        $ex_vlans[$v->number] = $name;
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
if (!isset($scan_devices))                      { $Result->show("danger", _("No devices for SNMP VLAN query available"), true); }

// init result array
$new_vlans = array();

// ok, we have devices, connect to each device and do query
foreach ($scan_devices as $d) {
    // init
    $Snmp->set_snmp_device ($d);
    // fetch arp table
    try {
        $res = $Snmp->get_query("get_vlan_table");
        // remove those not in subnet
        if (is_array($res) && sizeof($res)>0) {
           // save for debug
           $debug[$d->hostname]["get_vlan_table"] = $res;
           // loop and save
           foreach ($res as $k=>$r) {
               if (!array_key_exists($k, $new_vlans) && !array_key_exists($k, $ex_vlans) ) {
                   $new_vlans[$k] = $r;
               }
           }
        }
     } catch (Exception $e) {
       // save for debug
       $debug[$d->hostname]["get_vlan_table"] = $res;
       $errors[] = $e->getMessage();
	}
}

# none and errors
if(sizeof($new_vlans)==0 && isset($errors)) {
    $Result->show("info", _("No VLANS found"), false);
    $Result->show("warning", implode("<hr>", $errors), false);
}
# none
elseif(sizeof($new_vlans)==0) 	                     { $Result->show("info", _("No VLANS found")."!", false); }
# ok
else {
    // fetch custom fields and check for required
    $required_fields = $Tools->fetch_custom_fields ('vlans');
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
	print "<form name='scan-snmp-vlan-form' class='scan-snmp-arp-form' id='scan-snmp-vlan-form'>";
	print "<table class='table table-striped table-top table-condensed'>";

	// titles
	print "<tr>";
	print "	<th>"._("Number")."</th>";
	print "	<th>"._("Name")."</th>";
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
	foreach ($new_vlans as $number=>$name ) {
        print "<tr class='result$m'>";
		//number
		print "<td>$number</td>";
		//name
		print "<td>";
		print "	<input type='text' class='form-control input-sm' name='name$m' value='$name'>";
		print "	<input type='hidden' name='number$m' value='$number'>";
		print "	<input type='hidden' name='domainId$m' value='".escape_input($POST->domainId)."'>";

		print "</td>";
		//description
		print "<td>";
		print "	<input type='text' class='form-control input-sm' name='description$m' value='$name'>";
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
	print " <div id='vlanScanAddResult'></div>";
	print "		<a href='' class='btn btn-sm btn-success pull-right' id='saveVlanScanResults' data-script='vlans-scan' data-subnetId='".escape_input($POST->subnetId)."'><i class='fa fa-plus'></i> "._("Add discovered VLANS")."</a>";
	print "	</td>";
	print "</tr>";

	print "</table>";
	print '<input type="hidden" name="csrf_cookie" value="'.escape_input($POST->csrf_cookie).'">';
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
print " Scan method: SNMP VLAN table<hr>";
print " Scanned devices: <br>";
foreach ($debug as $k=>$d) {
    print "&middot; ".$k."<br>";
}
print "</span>";
print "</div>";

# show debug?
if($POST->debug==1) 				{ print "<pre>"; print_r($debug); print "</pre>"; }
