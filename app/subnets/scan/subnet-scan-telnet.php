<?php

/*
 * Discover new hosts with telnet scan
 *******************************/

# get ports
if(strlen($_POST['port'])==0) 	{ $Result->show("danger", _('Please enter ports to scan').'!', true); }

//verify ports
$pcheck = explode(";", str_replace(",",";",$_POST['port']));
foreach($pcheck as $p) {
	if(!is_numeric($p)) {
		$Result->show("danger", _("Invalid port")." ($p)", true);
	}
}
$_POST['port'] = str_replace(";",",",$_POST['port']);


# invoke CLI with threading support
$cmd = $Scan->php_exec." ".dirname(__FILE__) . "/../../../functions/scan/subnet-scan-telnet-execute.php $_POST[subnetId] '$_POST[port]'";

# save result to $output
exec($cmd, $output, $retval);

# format result back to object
$script_result = json_decode($output[0]);



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
	foreach($script_result->values->alive as $ip=>$port) {
		//resolve?
		$hostname = $DNS->resolve_address ( $ip, null, true, false);

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
print "<div class='text-right' style='margin-top:7px;'><span class='muted'>Scan method: telnet</span></dov>";

# show debug?
if($_POST['debug']==1) 				{ print "<pre>"; print_r($output[0]); print "</pre>"; }

?>