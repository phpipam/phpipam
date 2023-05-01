<?php

/**
 * Script to display IP address info
 ***********************************************/

# get IP and subnet details (for subnet request)
if(!isset($address)) {
	$address = (array) $Addresses-> fetch_address(null, $_GET['subnetId']);
	$subnet  = (array) $Subnets->fetch_subnet(null, $address['subnetId']);
}

# fetch all custom fields
$custom_fields = $Tools->fetch_custom_fields ('ipaddresses');

# set selected address fields array
$selected_ip_fields = $settings->IPfilter;
$selected_ip_fields = explode(";", $selected_ip_fields);																			//format to array
$selected_ip_fields_size = in_array('state', $selected_ip_fields) ? (sizeof($selected_ip_fields)-1) : sizeof($selected_ip_fields);	//set size of selected fields
if($selected_ip_fields_size==1 && strlen($selected_ip_fields[0])==0) { $selected_ip_fields_size = 0; }								//fix for 0


# set ping statuses
$statuses = explode(";", $settings->pingStatus);

# checks
if(sizeof($subnet)==0) 					{ $Result->show("danger", _('Subnet does not exist'), true); }									//subnet doesnt exist

 # resolve dns name
$DNS = new DNS ($Database);
$resolve = $DNS->resolve_address($address['ip_addr'], $address['hostname'], false, $subnet['nameserverId']);

# reformat empty fields
$address = $Addresses->reformat_empty_array_fields($address, "<span class='text-muted'>/</span>");

#header
print "<h4>"._('IP address details')."</h4><hr>";

# back
if(@$temp_objects[$_GET['section']]->type=="subnets") {
print "<a class='btn btn-default btn-sm btn-default' href='".create_link("temp_share",$_GET['section'])."'><i class='fa fa-chevron-left'></i> "._('Back to subnet')."</a>";
}

# check if it exists, otherwise print error
if(sizeof($address)>1) {

	# table - details
	print "<table class='ipaddress_subnet table table-noborder table-condensed' style='margin-top:10px;'>";

	# ip
	print "<tr>";
	print "	<th>"._('IP address')."</th>";
	print "	<td><strong>$address[ip]</strong></td>";
	print "</tr>";

	# description
	print "<tr>";
	print "	<th>"._('Description')."</th>";
	print "	<td>$address[description]</td>";
	print "</tr>";

	# hierarchy
	print "<tr>";
	print "	<th>"._('Hierarchy')."</th>";
	print "	<td>";
	$Sections->print_breadcrumbs($Sections, $Subnets, array("page"=>"subnets", "section"=>$subnet['sectionId'], "subnetId"=>$subnet['id'], "ipaddrid"=>$address['id']), $Addresses);
	print "</td>";
	print "</tr>";

	# subnet
	print "<tr>";
	print "	<th>"._('Subnet')."</th>";
	print "	<td>$subnet[ip]/$subnet[mask] ($subnet[description])</td>";
	print "</tr>";

	# state
	print "<tr>";
	print "	<th>"._('IP status')."</th>";
	print "	<td>";

	if ($address['state'] == "0") 	  { $stateClass = _("Offline"); }
	else if ($address['state'] == "2") { $stateClass = _("Reserved"); }
	else if ($address['state'] == "3") { $stateClass = _("DHCP"); }
	else						  { $stateClass = _("Online"); }

	print $Addresses->address_type_index_to_type ($address['state']);
	print $Addresses->address_type_format_tag ($address['state']);

	print "	</td>";
	print "</tr>";

	# hostname
	print "<tr>";
	print "	<th>"._('Hostname')."</th>";
	print "	<td>$resolve[name]</td>";
	print "</tr>";

	# mac
	if(in_array('owner', $selected_ip_fields)) {
	print "<tr>";
	print "	<th>"._('Owner')."</th>";
	print "	<td>$address[owner]</td>";
	print "</tr>";
	}

	# mac
	if(in_array('mac', $selected_ip_fields)) {
	print "<tr>";
	print "	<th>"._('MAC address')."</th>";
	print "	<td>$address[mac]</td>";
	print "</tr>";
	}

	# note
	if(in_array('note', $selected_ip_fields)) {
	print "<tr>";
	print "	<th>"._('Note')."</th>";
	print "	<td>$address[note]</td>";
	print "</tr>";
	}

	# switch
	if(in_array('switch', $selected_ip_fields)) {
	print "<tr>";
	print "	<th>"._('Device')."</th>";
	if(strlen($address['switch'])>0) {
		# get device
		$device = $Tools->fetch_object("devices", "id", $address['switch']);
		if($device!==false) {
			$device = (array) $device;
			$device = $Addresses->reformat_empty_array_fields($device, "");
			print "	<td>".@$device['hostname']." ".@$device['description']."</td>";
		}
		else {
			print " <td><span class='text-muted'>/</span></td>";
		}
	} else {
		print "	<td><span class='text-muted'>/</span></td>";
	}
	print "</tr>";
	}

	# port
	if(in_array('port', $selected_ip_fields)) {
	print "<tr>";
	print "	<th>"._('Port')."</th>";
	print "	<td>$address[port]</td>";
	print "</tr>";
	}

	# last edited
	print "<tr>";
	print "	<th>"._('Last edited')."</th>";
	if(strlen($address['editDate'])>1) {
		print "	<td>$address[editDate]</td>";
	} else {
		print "	<td>"._('Never')."</td>";
	}
	print "</tr>";


	# availability
	print "<tr>";
	print "	<td colspan='2'><hr></td>";
	print "</tr>";
	print "<tr>";

	# calculate
	$tDiff = time() - strtotime($address['lastSeen']);
	if($address['excludePing']==1)		 					{ $seen_status = ""; 			$seen_text = ""; }
	elseif($address['lastSeen'] == "0000-00-00 00:00:00") 	{ $seen_status = "neutral"; 	$seen_text = _("Device is offline")."<br>"._("Last seen").": "._("Never");}
	elseif($address['lastSeen'] == "1970-01-01 00:00:01") 	{ $seen_status = "neutral"; 	$seen_text = _("Device is offline")."<br>"._("Last seen").": "._("Never");}
	elseif($tDiff < $statuses[0])							{ $seen_status = "success";		$seen_text = _("Device is alive")."<br>"._("Last seen").": ".$address['lastSeen']; }
	elseif($tDiff < $statuses[1])							{ $seen_status = "warning"; 	$seen_text = _("Device warning")."<br>"._("Last seen").": ".$address['lastSeen']; }
	elseif($tDiff > $statuses[1])							{ $seen_status = "error"; 		$seen_text = _("Device is offline")."<br>"._("Last seen").": ".$address['lastSeen'];}
	else													{ $seen_status = "neutral"; 	$seen_text = _("Device status unknown");}

	print "	<th>"._('Availability')."<br><span class='status status-ip status-$seen_status' style='pull-right'></span></th>";
	print "	<td>";
	print "$seen_text";

	print "	</td>";
	print "</tr>";

	# custom fields
	if(sizeof($custom_fields) > 0) {
		print "<tr>";
		print "	<td colsapn='2'><hr></td>";
		print "</tr>";

		foreach($custom_fields as $key=>$field) {
			if(strlen($address[$key])>0) {
			$address[$key] = str_replace(array("\n", "\r\n"), "<br>",$address[$key]);
			print "<tr>";
			print "	<th>$key</th>";
			print "	<td>";
			#booleans
			if($field['type']=="tinyint(1)")	{
				if($address[$key] == 0)		{ print _("No"); }
				elseif($address[$key] == 1)	{ print _("Yes"); }
			}
			else {
				print $address[$key];
			}
			print "	</td>";
			print "</tr>";
			}
		}
	}

	print "</tr>";
	print "</table>";
}
# not exisitng
else {
	$Result->show("danger", _("IP address not existing in database")."!", true);
}