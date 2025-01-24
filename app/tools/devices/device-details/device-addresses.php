<?php

/**
 * Script to display devices
 */

# verify that user is logged in
$User->check_user_session();
# perm check
$User->check_module_permissions ("devices", User::ACCESS_R, true, false);

# check
is_numeric($GET->subnetId) ? : $Result->show("danger", _("Invalid ID"), true);

# fetch device
$device = (array) $Tools->fetch_object ("devices", "id", $GET->subnetId);

# get custom fields
$custom_fields = $Tools->fetch_custom_fields('devices');
# fetch all addresses on switch
$addresses     = $Tools->fetch_multiple_objects("ipaddresses", "switch", $device['id']);
if ($addresses===false) { $addresses = array(); }

# set selected address fields array
$selected_ip_fields = $Tools->explode_filtered(";", $User->settings->IPfilter);

# title - hosts
print "<h4>"._("Belonging addresses")."</h4><hr>";

# Hosts table
print "<table id='switchMainTable' class='devices table sorted table-striped table-top table-condensed' data-cookie-id-table='device_addresses'>";

# headers
print "<thead>";
print "<tr>";
print "	<th>"._('IP address')."</th>";
if(in_array("port", $selected_ip_fields)) {
print "	<th>"._('Port')."</th>";
}
print "	<th>"._('Subnet')."</th>";
print "	<th>"._('Description')."</th>";
print "	<th></th>";
print "	<th class='hidden-xs'>"._('Hostname')."</th>";
print "	<th class='hidden-xs hidden-sm'>"._('Owner')."</th>";
print "</tr>";
print "</thead>";

# IP addresses
$ipcnt = 0;
print "<tbody>";
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
			if(in_array("port", $selected_ip_fields)) {
			print "	<td class='port'>$ip[port]</td>";
			}
			print "	<td class='subnet'><a href='".create_link("subnets",$section['id'],$subnet['id'])."'>$subnet[ip]/$subnet[mask]</a> <span class='text-muted'>($subnet[description])</span></td>";
			print "	<td class='description'>$ip[description]</td>";

			# print info button for hover
			print "<td class='note'>";
			if(!empty($ip['note'])) {
				$ip['note'] = str_replace("\n", "<br>",$ip['note']);
				print "	<i class='fa fa-comment-o' rel='tooltip' title='$ip[note]'></i>";
			}
			print "</td>";

			print "	<td class='dns hidden-xs'>$ip[hostname]</td>";
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

print "</tbody>";
print "</table>";			# end table
print "</td>";

print "</tr>";
print "</table>";