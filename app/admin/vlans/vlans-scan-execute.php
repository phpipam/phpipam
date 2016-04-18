<?php

/*
 * Discover new vlans with snmp
 *******************************/

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Tools	 	= new Tools ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# fake error
print "<div class='alert-danger hidden'></div>";

# scan disabled
if ($User->settings->enableSNMP!="1")           { $Result->show("danger", _("SNMP module disbled"), true); }
# admin check
if($User->is_admin()!==true) 	                { $Result->show("danger", _('Admin privileges required'), true); }

# set class
$Snmp = new phpipamSNMP ();

# domain Id must be int
if (!is_numeric($_POST['domainId']))            { $Result->show("danger", _("Invalid domain Id"), true); }
# fetch domain
$domain = $Tools->fetch_object ("vlanDomains", "id", $_POST['domainId']);
if ($domain===false)                            { $Result->show("danger", _("Invalid domain Id"), true); }

# get existing vlans
$existing_vlans = $Tools->fetch_multiple_objects ("vlans", "domainId", $domain->id, "vlanId");
if ($existing_vlans!==false) {
    foreach ($existing_vlans as $v) {
        $ex_vlans[$v->number] = $name;
    }
}

// no errors
error_reporting(E_ERROR);

# set devices
foreach ($_POST as $k=>$p) {
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
        if (sizeof($res)>0) {
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
            print "<th>"._($field['name'])."</th>";
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
		print "	<input type='hidden' name='domainId$m' value='$_POST[domainId]'>";

		print "</td>";
		//description
		print "<td>";
		print "	<input type='text' class='form-control input-sm' name='description$m' value='$name'>";
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
		print 	"<td><a href='' class='btn btn-xs btn-danger resultRemove' data-target='result$m'><i class='fa fa-times'></i></a></td>";
		print "</tr>";

		$m++;
	}

	//submit
	print "<tr>";
	print "	<td colspan='$colspan'>";
	print " <div id='vlanScanAddResult'></div>";
	print "		<a href='' class='btn btn-sm btn-success pull-right' id='saveVlanScanResults' data-script='vlans-scan' data-subnetId='".$_POST['subnetId']."'><i class='fa fa-plus'></i> "._("Add discovered VLANS")."</a>";
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
print " Scan method: SNMP VLAN table<hr>";
print " Scanned devices: <br>";
foreach ($debug as $k=>$d) {
    print "&middot; ".$k."<br>";
}
print "</span>";
print "</div>";

# show debug?
if($_POST['debug']==1) 				{ print "<pre>"; print_r($debug); print "</pre>"; }
?>