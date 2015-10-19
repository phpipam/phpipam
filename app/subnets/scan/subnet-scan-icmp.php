<?php

/*
 * Discover new hosts with ping
 *******************************/

# verify that user is logged in
$User->check_user_session();

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

	//form
	print "<form name='".$_POST['type']."-form' class='".$_POST['type']."-form'>";
	print "<table class='table table-striped table-top table-condensed'>";

	// titles
	print "<tr>";
	print "	<th>"._("IP")."</th>";
	print "	<th>"._("Description")."</th>";
	print "	<th>"._("Hostname")."</th>";
	print "	<th></th>";
	print "</tr>";

	// alive
	$m=0;
	foreach($script_result->values->alive as $ip) {
		//resolve?
		$hostname = $DNS->resolve_address($ip, false, false, null);

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
		print "	<input type='text' class='form-control input-sm' name='dns_name$m' value='".@$hostname['name']."'>";
		print "</td>";
		//remove button
		print 	"<td><a href='' class='btn btn-xs btn-danger resultRemove' data-target='result$m'><i class='fa fa-times'></i></a></td>";
		print "</tr>";

		$m++;
	}

	//result
	print "<tr>";
	print "	<td colspan='4'>";
	print "<div id='subnetScanAddResult'></div>";
	print "	</td>";
	print "</tr>";

	//submit
	print "<tr>";
	print "	<td colspan='4'>";
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