<?php

/**
 * Script to display IP address info and history
 ***********************************************/

# verify that user is logged in
$User->check_user_session();

# powerdns class
$PowerDNS = new PowerDNS ($Database);

# checks
if(!is_numeric($_GET['subnetId']))		{ $Result->show("danger", _("Invalid ID"), true); }
if(!is_numeric($_GET['section']))		{ $Result->show("danger", _("Invalid ID"), true); }
if(!is_numeric($_GET['ipaddrid']))		{ $Result->show("danger", _("Invalid ID"), true); }

# get IP a nd subnet details
$address = (array) $Addresses-> fetch_address(null, $_GET['ipaddrid']);
$subnet  = (array) $Subnets->fetch_subnet(null, $address['subnetId']);

# fetch all custom fields
$custom_fields = $Tools->fetch_custom_fields ('ipaddresses');
# set hidden custom fields
$hidden_cfields = json_decode($User->settings->hiddenCustomFields, true);
$hidden_cfields = is_array($hidden_cfields['ipaddresses']) ? $hidden_cfields['ipaddresses'] : array();

# set selected address fields array
$selected_ip_fields = $User->settings->IPfilter;
$selected_ip_fields = explode(";", $selected_ip_fields);																			//format to array
$selected_ip_fields_size = in_array('state', $selected_ip_fields) ? (sizeof($selected_ip_fields)-1) : sizeof($selected_ip_fields);	//set size of selected fields
if($selected_ip_fields_size==1 && strlen($selected_ip_fields[0])==0) { $selected_ip_fields_size = 0; }								//fix for 0


# set ping statuses
$statuses = explode(";", $User->settings->pingStatus);

# permissions
$subnet_permission  = $Subnets->check_permission($User->user, $subnet['id']);
$section_permission = $Sections->check_permission ($User->user, $subnet['sectionId']);

# checks
if(sizeof($subnet)==0) 					{ $Result->show("danger", _('Subnet does not exist'), true); }									//subnet doesnt exist
if($subnet_permission == 0)				{ $Result->show("danger", _('You do not have permission to access this network'), true); }		//not allowed to access

 # resolve dns name
$DNS = new DNS ($Database);
$resolve = $DNS->resolve_address($address['ip_addr'], $address['dns_name'], false, $subnet['nameserverId']);

# reformat empty fields
$address = $Addresses->reformat_empty_array_fields($address, "<span class='text-muted'>/</span>");

#header
print "<h4>"._('IP address details')."</h4><hr>";

# back
print "<a class='btn btn-default btn-sm btn-default' href='".create_link("subnets",$subnet['sectionId'],$subnet['id'])."'><i class='fa fa-chevron-left'></i> "._('Back to subnet')."</a>";

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
	$Subnets->print_breadcrumbs ($Sections, $Subnets, $_GET, $Addresses);
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
	$resolve1['name'] = strlen($resolve['name'])==0 ? "<span class='text-muted'>/</span>" : $resolve['name'];

	print "<tr>";
	print "	<th>"._('Hostname')."</th>";
	print "	<td>$resolve1[name]</td>";
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
		$device = (array) $Tools->fetch_device(null, $address['switch']);
		$device = $Addresses->reformat_empty_array_fields($device, "");
		print "	<td>".@$device['hostname']." ".@$device['description']."</td>";
	} else {
		print "	<td>$address[switch]</td>";
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
	elseif($tDiff < $statuses[0])							{ $seen_status = "success";		$seen_text = _("Device is alive")."<br>"._("Last seen").": ".$address['lastSeen']; }
	elseif($tDiff < $statuses[1])							{ $seen_status = "warning"; 	$seen_text = _("Device warning")."<br>"._("Last seen").": ".$address['lastSeen']; }
	elseif($tDiff > $statuses[1])							{ $seen_status = "error"; 		$seen_text = _("Device is offline")."<br>"._("Last seen").": ".$address['lastSeen'];}
	elseif($address['lastSeen'] == "0000-00-00 00:00:00") 	{ $seen_status = "neutral"; 	$seen_text = _("Device is offline")."<br>"._("Last seen").": "._("Never");}
	else													{ $seen_status = "neutral"; 	$seen_text = _("Device status unknown");}

	print "	<th>"._('Availability')."<br><span class='status status-ip status-$seen_status' style='pull-right'></span></th>";
	print "	<td>";
	print "$seen_text";

	print "	</td>";
	print "</tr>";


	# search for DNS records
	if($User->settings->enablePowerDNS==1 && $subnet['DNSrecords']==1 ) {
		$records = $PowerDNS->search_records ("name", $address['dns_name'], 'name', true);
		$ptr	 = $PowerDNS->fetch_record ($address['PTR']);
		if ($records !== false || $ptr!==false) {

			print "<tr><td colspan='2'><hr></tr>";
			print "<tr>";
			print "<th>"._('DNS records')."</th>";
			print "<td>";
			if($records!==false) {
				foreach ($records as $r) {
					print "<span class='badge badge1 badge3'>$r->type</span> $r->content <br>";
				}
			}
			if($ptr!==false) {
					print "<span class='badge badge1 badge3'>$ptr->type</span> $ptr->name <br>";
			}
			print "</td>";
			print "</tr>";
			print "<tr><td colspan='2'><hr></tr>";
		}
	}


	# custom device fields
	if(sizeof($custom_fields) > 0) {
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
				print $Result->create_links($address[$key]);
			}
			print "	</td>";
			print "</tr>";
			}
		}
	}

	# check for temporary shares!
	if($User->settings->tempShare==1) {
		foreach(json_decode($User->settings->tempAccess) as $s) {
			if($s->type=="ipaddresses" && $s->id==$address['id']) {
				if(time()<$s->validity) {
					$active_shares[] = $s;
				}
				else {
					$expired_shares[] = $s;
				}
			}
		}
		if(sizeof(@$active_shares)>0) {
			# divider
			print "<tr>";
			print "	<th colspan='2'><hr></th>";
			print "</tr>";
			# print
			print "<tr>";
			print "<th>"._("Active shares").":</th>";
			print "<td>";
			$m=1;
			foreach($active_shares as $s) {
				print "<button class='btn btn-xs btn-default removeSharedTemp' data-code='$s->code' ><i class='fa fa-times'></i></button> <a href='".create_link("temp_share",$s->code)."'>Share $m</a> ("._("Expires")." ".date("Y-m-d H:i:s", $s->validity).")<br>";
				$m++;
			}
			print "<td>";
			print "</tr>";
		}
		if(sizeof(@$expired_shares)>0) {
			# divider
			print "<tr>";
			print "	<th><hr></th>";
			print "	<td></td>";
			print "</tr>";
			# print
			print "<tr>";
			print "<th>"._("Expired shares").":</th>";
			print "<td>";
			$m=1;
			foreach($expired_shares as $s) {
				print "<button class='btn btn-xs btn-danger removeSharedTemp' data-code='$s->code' ><i class='fa fa-times'></i></button> <a href='".create_link("temp_share",$s->code)."'>Share $m</a> ("._("Expired")." ".date("Y-m-d H:i:s", $s->validity).")<br>";
				$m++;
			}
			print "<td>";
			print "</tr>";
		}
	}


	# actions
	print "<tr>";
	print "	<td colspan='2'><hr></td>";
	print "</tr>";
	print "<tr>";
	print "	<th>"._('Actions')."</th>";

	print "<td class='btn-actions'>";
	print "	<div class='btn-toolbar'>";
	print "	<div class='btn-group'>";
	# write permitted
	if( $subnet_permission > 1) {
		if(@$address['class']=="range-dhcp")
		{
			print "		<a class='edit_ipaddress   btn btn-default btn-xs modIPaddr' data-action='edit'   data-subnetId='".$address['subnetId']."' data-id='".$address['id']."' data-stopIP='".$address['stopIP']."' href='#' 		   rel='tooltip' data-container='body' title='"._('Edit IP address details')."'>	<i class='fa fa-gray fa-pencil'>  </i></a>";
			print "		<a class='				   btn btn-default btn-xs disabled' href='#'>																																													<i class='fa fa-gray fa-cogs'> </i></a>";
			print "		<a class='				   btn btn-default btn-xs disabled' href='#'>																																													<i class='fa fa-gray fa-search'></i></a>";
			print "		<a class='				   btn btn-default btn-xs disabled' href='#'>																																													<i class='fa fa-gray fa-envelope-o'></i></a>";
			print "		<a class='delete_ipaddress btn btn-default btn-xs modIPaddr' data-action='delete' data-subnetId='".$address['subnetId']."' data-id='".$address['id']."' href='#' id2='$address[ip]' rel='tooltip' data-container='body' title='"._('Delete IP address')."'>		<i class='fa fa-gray fa-times'>  </i></a>";
		}
		else
		{
			print "		<a class='edit_ipaddress   btn btn-default btn-xs modIPaddr' data-action='edit'   data-subnetId='".$address['subnetId']."' data-id='".$address['id']."' href='#' 											   rel='tooltip' data-container='body' title='"._('Edit IP address details')."'>				<i class='fa fa-gray fa-pencil'></i></a>";
			print "		<a class='ping_ipaddress   btn btn-default btn-xs' data-subnetId='".$address['subnetId']."' data-id='".$address['id']."' href='#' 						   													rel='tooltip' data-container='body' title='"._('Check availability')."'>							<i class='fa fa-gray fa-cogs'></i></a>";
			print "		<a class='search_ipaddress btn btn-default btn-xs         "; if(strlen($resolve['name']) == 0) { print "disabled"; } print "' href='".create_link("tools","search",$resolve['name'])."' "; if(strlen($resolve['name']) != 0)   { print "rel='tooltip' data-container='body' title='"._('Search same hostnames in db')."'"; } print ">	<i class='fa fa-gray fa-search'></i></a>";
			print "		<a class='mail_ipaddress   btn btn-default btn-xs          ' href='#' data-id='".$address['id']."' rel='tooltip' data-container='body' title='"._('Send mail notification')."'>																																<i class='fa fa-gray fa-envelope-o'></i></a>";
			print "		<a class='delete_ipaddress btn btn-default btn-xs modIPaddr' data-action='delete' data-subnetId='".$address['subnetId']."' data-id='".$address['id']."' href='#' id2='$address[ip]' rel='tooltip' data-container='body' title='"._('Delete IP address')."'>													<i class='fa fa-gray fa-times'></i></a>";
			//share
			if($User->settings->tempShare==1) {
			print "		<a class='shareTemp btn btn-xs btn-default'  data-container='body' rel='tooltip' title='"._('Temporary share address')."' data-id='$address[id]' data-type='ipaddresses'>		<i class='fa fa-share-alt'></i></a>";
			}
		}
	}
	# write not permitted
	else {
		if(@$address['class']=="range-dhcp")
		{
			print "		<a class='edit_ipaddress   btn btn-default btn-xs disabled' rel='tooltip' data-container='body' title='"._('Edit IP address details (disabled)')."'>	<i class='fa fa-gray fa-pencil'>  </i></a>";
			print "		<a class='				   btn btn-default btn-xs disabled' href='#'>																<i class='fa fa-gray fa-retweet'> </i></a>";
			print "		<a class='				   btn btn-default btn-xs disabled' href='#'>																<i class='fa fa-gray fa-search'></i></a>";
			print "		<a class='				   btn btn-default btn-xs disabled' href='#'>																<i class='fa fa-gray fa-envelope'></i></a>";
			print "		<a class='delete_ipaddress btn btn-default btn-xs disabled' rel='tooltip' data-container='body' title='"._('Delete IP address (disabled)')."'>			<i class='fa fa-gray fa-times'>  </i></a>";
		}
		else
		{
			print "		<a class='edit_ipaddress   btn btn-default btn-xs disabled' rel='tooltip' data-container='body' title='"._('Edit IP address details (disabled)')."'>							<i class='fa fa-gray fa-pencil'>  </i></a>";
			print "		<a class='				   btn btn-default btn-xs disabled'  data-id='".$address['id']."' href='#' rel='tooltip' data-container='body' title='"._('Check availability')."'>		<i class='fa fa-gray fa-retweet'>  </i></a>";
			print "		<a class='search_ipaddress btn btn-default btn-xs         "; if(strlen($resolve['name']) == 0) { print "disabled"; } print "' href='".create_link("tools","search",$resolve['name'])."' "; if(strlen($resolve['name']) != 0) { print "rel='tooltip' data-container='body' title='"._('Search same hostnames in db')."'"; } print ">	<i class='fa fa-gray fa-search'></i></a>";
			print "		<a class='mail_ipaddress   btn btn-default btn-xs          ' href='#' data-id='".$address['id']."' rel='tooltip' data-container='body' title='"._('Send mail notification')."'>		<i class='fa fa-gray fa-envelope'></i></a>";
			print "		<a class='delete_ipaddress btn btn-default btn-xs disabled' rel='tooltip' data-container='body' title='"._('Delete IP address (disabled)')."'>				<i class='fa fa-gray fa-times'>  </i></a>";
		}
	}

	print "	</div>";
	print "	</div>";
	print "</td>";

	print "</tr>";


	print "</table>";

	# changelog
	include("address-changelog.php");
}
# not exisitng
else {
	$Result->show("danger", _("IP address not existing in database")."!", true);
}
?>
