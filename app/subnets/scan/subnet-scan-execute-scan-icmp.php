<?php

# Check we have been included and not called directly
require( dirname(__FILE__) . '/../../../functions/include-only.php' );

# check if site is demo
$User->is_demo();

/*
 * Discover new hosts with ping
 *******************************/

# validate subnetId and type
if(!is_numeric($POST->subnetId))                        { $Result->show("danger", "Invalid subnet Id", true); die(); }
if(!preg_match('/[^A-Za-z0-9-]*$/', $POST->type))       { $Result->show("danger", "Invalid scan type", true); die(); }


# invoke CLI with threading support
$cmd = $Scan->php_exec." ".dirname(__FILE__) . '/../../../functions/scan/subnet-scan-icmp-execute.php'." 'discovery' ".$POST->subnetId;

# save result to $output
exec($cmd, $output, $retval);

# format result back to object
$output = array_values(array_filter($output));
$script_result = db_json_decode($output[0]);

# json error
if(json_last_error() !== JSON_ERROR_NONE)
	$Result->show("danger", "Invalid JSON response"." - ".$Scan->json_error_decode(json_last_error())." - ".escape_input($output[0]), true);

# if method is fping we need to check against existing hosts because it produces list of all ips !
if ($User->settings->scanPingType=="fping" && isset($script_result->values->alive)) {
	// fetch all hosts to be scanned
	$to_scan_hosts = $Scan->prepare_addresses_to_scan ("discovery", $POST->subnetId);
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

# die if error
if($retval!==0) 							{ $Result->show("danger", "Error executing scan! Error code - $retval", false); }
# error?
elseif($script_result->status===1)				{ $Result->show("danger", $script_result->error, false); }
# empty
elseif(!isset($script_result->values->alive)) 	{ $Result->show("info", _("0 new hosts discovered")."!", false); }
# ok
else {
	// fetch subnet and set nsid
	$subnet = $Subnets->fetch_subnet ("id", $POST->subnetId);
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
	print "<form name='".escape_input($POST->type)."-form' class='".escape_input($POST->type)."-form'>";
	print "<input type='hidden' name='csrf_cookie' value='$csrf'>";
	print "<table class='table table-striped table-top table-condensed'>";

	// titles
	print "<tr>";
	print "	<th>"._("IP")."</th>";
	print "	<th>"._("Description")."</th>";
	print "	<th>"._("Hostname")."</th>";
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
		print "</td>";
		//hostname
		print "<td>";
		print "	<input type='text' class='form-control input-sm' name='hostname$m' value='".@$hostname['name']."'>";
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
	print "		<a href='' class='btn btn-sm btn-success pull-right' id='saveScanResults' data-script='".escape_input($POST->type)."' data-subnetId='".escape_input($POST->subnetId)."'><i class='fa fa-plus'></i> "._("Add discovered hosts")."</a>";
	print "	</td>";
	print "</tr>";

	print "</table>";
	print "</form>";
}
//print scan method
print "<div class='text-right' style='margin-top:7px;'><span class='muted'>Scan method: ".$Scan->settings->scanPingType."</span></dov>";

# show debug?
if($POST->debug==1) 				{ print "<pre>"; print_r($output[0]); print "</pre>"; }