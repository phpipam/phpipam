<?php

/*
 * Discover new hosts with ping
 *******************************/

# verify that user is logged in
$User->check_user_session();

# create csrf token
$csrf = $User->csrf_cookie ("create", "scan");

# invoke CLI with threading support
$cmd = $Scan->php_exec." ".dirname(__FILE__) . '/../../../functions/scan/subnet-scan-icmp-execute.php'." 'discovery' ".$_POST['subnetId'];

# save result to $output
exec($cmd, $output, $retval);

# format result back to object
$script_result = json_decode($output[0]);

# if method is fping we need to check against existing hosts because it produces list of all ips !
if ($User->settings->scanPingType=="fping" && isset($script_result->values->alive)) {
	// fetch all hosts to be scanned
	$to_scan_hosts = $Scan->prepare_addresses_to_scan ("discovery", $_POST['subnetId']);
	// loop check
	foreach($script_result->values->alive as $rk=>$result) {
		if(!in_array($Subnets->transform_address($result, "decimal"), $to_scan_hosts)) {
			unset($script_result->values->alive[$rk]);
		}
	}
	// null
	if (sizeof($script_result->values->alive)==0) {
		unset($script_result->values->alive);
	}
	//rekey
	else {
		$script_result->values->alive = array_values($script_result->values->alive);
	}
}

//title
print "<h5>"._('Scan results').":</h5><hr>";

# json error
if(json_last_error()!=0)						{ $Result->show("danger", "Invalid JSON response"." - ".$Result->json_error_decode(json_last_error()), false); }
# die if error
elseif($retval!=0) 								{ $Result->show("danger", "Error executing scan! Error code - $retval", false); }
# error?
elseif($script_result->status===1)				{ $Result->show("danger", $script_result->error, false); }
# empty
elseif(!isset($script_result->values->alive)) 	{ $Result->show("danger", _("No alive host found")."!", false); }
# ok
else {
	// fetch subnet and set nsid
	$subnet = $Subnets->fetch_subnet ("id", $_POST['subnetId']);
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

	//form
	print "<form name='".$_POST['type']."-form' class='".$_POST['type']."-form'>";
	print "<table class='table table-striped table-top table-condensed'>";

	// titles
	print "<tr>";
	print "	<th>"._("IP")."</th>";
	print "	<th>"._("Description")."</th>";
	print "	<th>"._("Hostname")."</th>";
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
	foreach($script_result->values->alive as $ip) {
		//resolve?
		$hostname = $DNS->resolve_address($ip, false, true, $nsid);

		print "<tr class='result$m'>";
		//ip
		print "<td>".$Subnets->transform_to_dotted($ip)."</td>";
		//description
		print "<td>";
		print "	<input type='text' class='form-control input-sm' name='description$m'>";
		print "	<input type='hidden' name='ip$m' value=".$Subnets->transform_to_dotted($ip).">";
		print " <input type='hidden' name='csrf_cookie' value='$csrf'>";
		print "</td>";
		//hostname
		print "<td>";
		print "	<input type='text' class='form-control input-sm' name='dns_name$m' value='".@$hostname['name']."'>";
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

    // calculate colspan
	$colspan = 4 + sizeof(@$required_fields);

	//result
	print "<tr>";
	print "	<td colspan='$colspan'>";
	print "<div id='subnetScanAddResult'></div>";
	print "	</td>";
	print "</tr>";

	//submit
	print "<tr>";
	print "	<td colspan='$colspan'>";
	print "		<a href='' class='btn btn-sm btn-success pull-right' id='saveScanResults' data-script='".$_POST['type']."' data-subnetId='".$_POST['subnetId']."'><i class='fa fa-plus'></i> "._("Add discovered hosts")."</a>";
	print "	</td>";
	print "</tr>";

	print "</table>";
	print "</form>";
}
//print scan method
print "<div class='text-right' style='margin-top:7px;'><span class='muted'>Scan method: ".$Scan->settings->scanPingType."</span></dov>";

# show debug?
if($_POST['debug']==1) 				{ print "<pre>"; print_r($output[0]); print "</pre>"; }

?>