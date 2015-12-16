<?php

/**
 * Script to display devices
 */

# verify that user is logged in
$User->check_user_session();

# check
is_numeric($_GET['sPage']) ? : $Result->show("danger", _("Invalid ID"), true);

# fetch device
$device = (array) $Tools->fetch_device (null,$_GET['sPage']);
# get custom fields
$custom_fields = $Tools->fetch_custom_fields('devices');
# fetch all addresses on switch
$addresses     = $Tools->fetch_device_addresses($device['id']);

# print
if($_GET['sPage']!=0 && sizeof($device)>0) {

	# set type
	$device_type = $Tools->fetch_device_type(null, $device['type']);

	# title
	print "<h4>"._('Device details')."</h4>";
	print "<hr>";

	# device details
	print "<table class='ipaddress_subnet table-condensed table-full'>";
	print '<tr>';
	print "	<th>". _('Name').'</a></th>';
	print "	<td>$device[hostname]</td>";
	print "</tr>";
	print '<tr>';
	print "	<th>". _('IP address').'</th>';
	print "	<td>$device[ip_addr]</td>";
	print "</tr>";
	print '<tr>';
	print "	<th>". _('Description').'</th>';
	print "	<td>$device[description]</td>";
	print "</tr>";
	print '<tr>';
	print "	<th>". _('Number of hosts').'</th>';
	print "	<td>".sizeof($addresses)."</td>";
	print "</tr>";
	print '<tr>';
	print "	<th>". _('Type').'</th>';
	print "	<td>$device_type->tname</td>";
	print "</tr>";
	print '<tr>';
	print "	<th>". _('Vendor').'</th>';
	print "	<td>$device[vendor]</td>";
	print "</tr>";
	print '<tr>';
	print "	<th>". _('Model').'</th>';
	print "	<td>$device[model]</td>";
	print "</tr>";
	print '<tr>';
	print "	<th>". _('SW version').'</th>';
	print "	<td>$device[version]</td>";
	print "</tr>";
	print '<tr>';
	print "	<th>". _('Sections').':</th>';
	print "	<td>";
	if(strlen($device['hostname'])>0) {
		$section_ids = explode(";", $device['sections']);
		foreach($section_ids as $k=>$id) {
			$section = $Sections->fetch_section(null, $id);
			$section_print[$k]  = "&middot; ".$section->name;
			$section_print[$k] .= strlen($section->description)>0 ? " <span class='text-muted'>($section->description)</span>" : "";
		}
		print implode("<br>", $section_print);
	}
	print "</td>";
	print "</tr>";

	print "<tr>";
	print "	<td colspan='2'><hr></td>";
	print "</tr>";

	if(sizeof($custom_fields) > 0) {
		foreach($custom_fields as $field) {

			# fix for boolean
			if($field['type']=="tinyint(1)" || $field['type']=="boolean") {
				if($device[$field['name']]=="0")		{ $device[$field['name']] = "false"; }
				elseif($device[$field['name']]=="1")	{ $device[$field['name']] = "true"; }
				else									{ $device[$field['name']] = ""; }
			}

			# create links
			$device[$field['name']] = $Result->create_links ($device[$field['name']]);

			print "<tr>";
			print "<th>$field[name]</th>";
			print "<td>".$device[$field['name']]."</d>";
			print "</tr>";
		}

		print "<tr>";
		print "	<td colspan='2'><hr></td>";
		print "</tr>";
	}

	print "<tr>";
	print "	<td></td>";

	if($User->isadmin) {
		print "	<td class='actions'>";
		print "	<div class='btn-group'>";
		print "		<button class='btn btn-xs btn-default editSwitch' data-action='edit'   data-switchid='".$device['id']."'><i class='fa fa-gray fa-pencil'></i></button>";
		print "		<button class='btn btn-xs btn-default editSwitch' data-action='delete' data-switchid='".$device['id']."'><i class='fa fa-gray fa-times'></i></button>";
		print "	</div>";
		print " </td>";
	}
	else {
		print "	<td class='small actions'>";
		print "	<div class='btn-group'>";
		print "		<button class='btn btn-xs btn-default disabled'><i class='fa fa-gray fa-pencil'></i></button>";
		print "		<button class='btn btn-xs btn-default disabled'><i class='fa fa-gray fa-times'></i></button>";
		print "	</div>";
		print " </td>";
	}
	print "</tr>";


	print "</table>";
}


# format device
if(empty($device['hostname'])) {
	$device['hostname'] = _('Device not specified');
}
else 										{
	if(empty($device['hostname'])) 				{ $device['hostname'] = "Unspecified";}
}






# title - subnets
print "<h4 style='margin-top:40px;'>"._("Belonging subnets")."</h4><hr>";

//fetch
$subnets = $Tools->fetch_multiple_objects ("subnets", "device", $device['id']);

# Hosts table
print "<table id='switchMainTable' class='devices table table-striped table-top table-condensed'>";

# headers
print "<tr>";
print "	<th>"._('Section')."</th>";
print "	<th>"._('Subnet')."</th>";
print "	<th>"._('Description')."</th>";
print "	<th>"._('VLAN')."</th>";
print "</tr>";

// loop
$ipcnt = 0;
if ($subnets !== false ) {
	// loop
	foreach ($subnets as $s) {
		// permission check
		$subnet_permission  = $Subnets->check_permission($User->user, $s->id);

		if($subnet_permission>0) {
			# fetch section
			$section = (array) $Sections->fetch_section (null, $s->sectionId);
			$vlan	 = $Tools->fetch_object ("vlans", 'vlanId', $s->vlanId);

			# print
			print "<tr>";
			print "	<td class='ip'><a href='".create_link("subnets",$section['id'])."'>".$section['description']."</a></td>";
			print "	<td class='ip'><a href='".create_link("subnets",$section['id'],$s->id)."'>".$Subnets->transform_to_dotted($s->subnet)."/".$s->mask."</a></td>";
			print "	<td class='port'>".$s->description."</td>";
			print "	<td class='description'>".@$vlan->number ." ".@$vlan->description."</td>";

			// add count
			$ipcnt++;
		}
	}
}

# empty
if($ipcnt == 0) {
print "<tr class='alert text-info'>";
print "	<td colspan='8'>"._('No subnets belong to this device')."!</td>";
print "</tr>";
}

print "</table>";			# end table






# title - hosts
print "<h4 style='margin-top:40px;'>"._("Belonging hosts")."</h4><hr>";

# Hosts table
print "<table id='switchMainTable' class='devices table table-striped table-top table-condensed'>";

# headers
print "<tr>";
print "	<th>"._('IP address')."</th>";
print "	<th>"._('Port')."</th>";
print "	<th>"._('Subnet')."</th>";
print "	<th colspan='2'>"._('Description')."</th>";
print "	<th class='hidden-xs'>"._('Hostname')."</th>";
print "	<th class='hidden-xs hidden-sm'>"._('Owner')."</th>";
print "</tr>";

# IP addresses
$ipcnt = 0;
if(sizeof($addresses) > 0) {
	foreach ($addresses as $ip) {
		# cast
		$ip = (array) $ip;

		# check permission
		$subnet_permission  = $Subnets->check_permission($User->user, $ip['subnetId']);

		if($subnet_permission>0) {
			# get subnet and section details for belonging IP
			$subnet  = (array) $Subnets->fetch_subnet(null, $ip['subnetId']);
			$section = (array) $Sections->fetch_section (null, $subnet['sectionId']);

			# print
			print "<tr>";
			print "	<td class='ip'><a href='".create_link("subnets",$section['id'],$subnet['id'],"address-details",$ip['id'])."'>".$Subnets->transform_to_dotted($ip['ip_addr'])."</a></td>";
			print "	<td class='port'>$ip[port]</td>";
			print "	<td class='subnet'><a href='".create_link("subnets",$section['id'],$subnet['id'])."'>$subnet[ip]/$subnet[mask]</a> <span class='text-muted'>($subnet[description])</span></td>";
			print "	<td class='description'>$ip[description]</td>";

			# print info button for hover
			print "<td class='note'>";
			if(!empty($ip['note'])) {
				$ip['note'] = str_replace("\n", "<br>",$ip['note']);
				print "	<i class='fa fa-comment-o' rel='tooltip' title='$ip[note]'></i>";
			}
			print "</td>";

			print "	<td class='dns hidden-xs'>$ip[dns_name]</td>";
			print "	<td class='owner hidden-xs hidden-sm'>$ip[owner]</td>";
			print "</tr>";

			$ipcnt++;
		}
	}
}

# empty
if($ipcnt == 0) {
print "<tr class='alert text-info'>";
print "	<td colspan='8'>"._('No hosts belonging to this device')."!</td>";
print "</tr>";
}

print "</table>";			# end table
?>