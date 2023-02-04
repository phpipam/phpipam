<?php

# Check we have been included and not called directly
require( dirname(__FILE__) . '/../../../functions/include-only.php' );

/*
 * Update alive status of all hosts in subnet
 ***************************/

# validate subnetId and type
if(!is_numeric($_POST['subnetId']))                        { $Result->show("danger", "Invalid subnet Id", true); die(); }

# invoke CLI with threading support
$cmd = $Scan->php_exec." ".dirname(__FILE__) . '/../../../functions/scan/subnet-scan-icmp-execute.php'." 'update' ".$_POST['subnetId'];

# save result to $output
exec($cmd, $output, $retval);

# format result back to object
$output = array_values(array_filter($output));
$script_result = pf_json_decode($output[0]);

# json error
if(json_last_error() !== JSON_ERROR_NONE)
	$Result->show("danger", "Invalid JSON response"." - ".$Scan->json_error_decode(json_last_error())." - ".escape_input($output[0]), true);

# set blank values
if (!isset($script_result->values->alive) || is_null($script_result->values->alive) )	{ $script_result->values->alive = array(); }
if (!isset($script_result->values->dead)  || is_null($script_result->values->dead) )	{ $script_result->values->dead = array(); }

# if method is fping we need to check against existing hosts because it produces list of all ips !
if ($User->settings->scanPingType=="fping" && isset($script_result->values->alive)) {
	// fetch all hosts to be scanned
	$all_subnet_hosts = $Scan->prepare_addresses_to_scan ("update", $_POST['subnetId']);
	// loop check
	foreach ($all_subnet_hosts as $k=>$h) {
		// alive ?
		if (sizeof($script_result->values->alive)>0) {
			if (!in_array($h, $script_result->values->alive)) {
				$script_result->values->dead[] = $h;
			}
		}
		else {
			$script_result->values->dead = $all_subnet_hosts;
		}
	}

	// null
	if (sizeof($script_result->values->dead)==0)  {
		unset($script_result->values->dead); }
	else	{
		$script_result->values->dead = array_values($script_result->values->dead);
	}

	if (sizeof($script_result->values->alive)==0) {
		unset($script_result->values->alive);
	}
	//rekey
	else {
		$script_result->values->alive = array_values($script_result->values->alive);
	}
}

# recode to same array with statuses
$m=0;
if($script_result->status==0) {
	//loop types (dead, alive, error)
	if(!empty($script_result->values)) {
		foreach($script_result->values as $k=>$r) {
			//loop addresses in type
			foreach($r as $ip) {
				# get details
				$ipdet = (array) $Addresses->fetch_address_multiple_criteria ($ip, $_POST['subnetId']);

				# format output
				$res[$ip]['id']          = $ipdet['id'];;
				$res[$ip]['ip_addr']     = $ip;
				$res[$ip]['description'] = $ipdet['description'];
				$res[$ip]['hostname']    = $ipdet['hostname'];
				$res[$ip]['state']       = $ipdet['state'];
				$res[$ip]['lastSeen']    = $ipdet['lastSeen'];

				//online
				if($k=="alive")	{
					$res[$ip]['status'] = "Online";
					$res[$ip]['code']=0;
					//update alive time
					if(isset($ipdet['id'])) {
						$Scan->ping_update_lastseen($ipdet['id']);
					}
				}
				//offline
				elseif($k=="dead")	{
					$res[$ip]['status'] = "Offline";
					$res[$ip]['code']=1;
				}
				//excluded
				elseif($k=="excluded")	{
					$res[$ip]['status'] = "Excluded form check";
					$res[$ip]['code']=100;
				}
				else {
					$res[$ip]['status'] = "Error";
					$res[$ip]['code']=2;
				}
				$m++;
			}
		}
	}
}
?>


<h5><?php print _('Scan results');?>:</h5>
<hr>

<?php
# die if error
if($retval!=0) 								{ $Result->show("danger", "Error executing scan! Error code - $retval", false); }
# error?
elseif($script_result->status===1)				{ $Result->show("danger", $script_result->error, false); }
# empty
elseif(!isset($script_result->values)) 			{ $Result->show("info", _("Subnet is empty")."!", false); }
# no ip addresses - error in script
elseif(!isset($res)) 							{ $Result->show("info", _("Error")."!", false); }
# ok
else {
	# order by IP address
	ksort($res);

	//table
	print "<table class='table table-condensed table-top'>";

	//headers
	print "<tr>";
	print "	<th>"._('IP')."</th>";
	print "	<th>"._('Description')."</th>";
	print "	<th>"._('status')."</th>";
	print "	<th>"._('hostname')."</th>";
	print "</tr>";

	//loop
	foreach($res as $r) {
		//set class
		if($r['code']==0)		{ $class='success'; }
		elseif($r['code']==100)	{ $class='warning'; }
		else					{ $class='danger'; }

		print "<tr class='$class'>";
		print "	<td>".$Subnets->transform_to_dotted($r['ip_addr'])."</td>";
		print "	<td>".$r['description']."</td>";
		print "	<td>"._("$r[status]")."</td>";
		print "	<td>".$r['hostname']."</td>";

		print "</tr>";

		# update ipTag
		if ($User->settings->updateTags==1 && $Subnets->address_types[$r['state']]['updateTag']==1) {
			// online
			if ($r['code']==0 && $r['state']!=2) {
				$Scan->update_address_tag ($r['id'], 2, $r['state'], date("Y-m-d H:i:s"));
			}
			// offline
			elseif( ($r['code']==1 || $r['code']==2) && $r['state']!=1) {
				$Scan->update_address_tag ($r['id'], 1, $r['state'], $r['lastSeen']);
			}
		}

	}
	print "</table>";
}
//print scan method
print "<div class='text-right' style='margin-top:7px;'><span class='muted'>Scan method: ".$Scan->settings->scanPingType."</span></dov>";

# show debug?
if($_POST['debug']==1) 				{ print "<pre>"; print_r($output[0]); print "</pre>"; }