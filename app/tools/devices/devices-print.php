<script type="text/javascript">
/* fix for ajax-loading tooltips */
$('body').tooltip({ selector: '[rel=tooltip]' });
</script>

<?php

/**
 * Script to display devices
 *
 */

# verify that user is logged in
$User->check_user_session();
# filter devices or fetch print all?
$devices = $Tools->fetch_all_objects("devices", "hostname");
$device_types = $Tools->fetch_all_objects ("deviceTypes", "tid");

# get custom fields
$custom_fields = $Tools->fetch_custom_fields('devices');
# get hidden fields */
$hidden_fields = json_decode($User->settings->hiddenCustomFields, true);
$hidden_fields = is_array(@$hidden_fields['devices']) ? $hidden_fields['devices'] : array();
# title
print "<h4>"._('List of network devices')."</h4>";
print "<hr>";

# table
print '<table id="switchManagement" class="table sorted table-striped table-top">';

#headers
print "<thead>";
print '<tr>';
print "	<th><span rel='tooltip' data-container='body' title='"._('Sort by hostname')."'>"._('Name')."</span></th>";
print "	<th><span rel='tooltip' data-container='body' title='"._('Sort by IP address')."'>"._('IP address')."</span></th>";
print "	<th><span rel='tooltip' data-container='body' title='"._('Sort by description')."'>"._('Description').'</span></th>';
print "	<th style='color:#428bca'>"._('Number of hosts').'</th>';
print "	<th class='hidden-sm'>          <span rel='tooltip' data-container='body' title='"._('Sort by type')."'>". _('Type').'</span></th>';
print "	<th class='hidden-sm hidden-xs'><span rel='tooltip' data-container='body' title='"._('Sort by vendor')."'>"._('Vendor').'</span></th>';
print "	<th class='hidden-sm hidden-xs'><span rel='tooltip' data-container='body' title='"._('Sort by model')."'>"._('Model').'</span></th>';

if(sizeof(@$custom) > 0) {
	foreach($custom_fields as $field) {
		if(!in_array($field['name'], $hidden_fields)) {
			print "<th class='hidden-sm hidden-xs hidden-md'><span rel='tooltip' data-container='body' title='"._('Sort by')." $field[name]'>".$field['name']."</th>";
			$colspanCustom++;
		}
	}
}
print '	<th class="actions"></th>';
print '</tr>';
print "</thead>";

// no devices
if(sizeof(@$devices) == 0) {
	$colspan = 8 + $colspanCustom;
	print "<tr>";
	print "	<td colspan='$colspan'>".$Result->show('info', _('No results')."!", false, false, true)."</td>";
	print "</tr>";
}
// result
else {
	foreach ($devices as $device) {
	//cast
	$device = (array) $device;

	//count items
	$cnt = $Tools->count_database_objects("ipaddresses", "switch", $device['id']);

	// reindex types
	if (isset($device_types)) {
		foreach($device_types as $dt) {
			$device_types_indexed[$dt->tid] = $dt;
		}
	}

	//print details
	print '<tr>'. "\n";

	print "	<td><a href='".create_link("tools","devices","hosts",$device['id'])."'>". $device['hostname'] .'</a></td>'. "\n";
	print "	<td>". $device['ip_addr'] .'</td>'. "\n";
	print '	<td class="description">'. $device['description'] .'</td>'. "\n";
	print '	<td><strong>'. $cnt .'</strong> '._('Hosts').'</td>'. "\n";
	print '	<td class="hidden-sm">'. $device_types_indexed[$device['type']]->tname .'</td>'. "\n";
	print '	<td class="hidden-sm hidden-xs">'. $device['vendor'] .'</td>'. "\n";
	print '	<td class="hidden-sm hidden-xs">'. $device['model'] .'</td>'. "\n";

	//custom
	if(sizeof(@$custom) > 0) {
		foreach($custom_fields as $field) {
			if(!in_array($field['name'], $hidden_fields)) {
				print "<td class='hidden-sm hidden-xs hidden-md'>".$device[$field['name']]."</td>";
			}
		}
	}

	print '	<td class="actions"><a href="'.create_link("tools","devices","hosts",$device['id']).'" class="btn btn-sm btn-default"><i class="fa fa-angle-right"></i> '._('Show all hosts').'</a></td>';
	print '</tr>'. "\n";

	}

	# print for unspecified
	print '<tr class="unspecified">'. "\n";

    // count empty
	$cnt = $Tools->count_database_objects("ipaddresses", "switch", 0);


	print '	<td>'._('Device not specified').'</td>'. "\n";
	print '	<td></td>'. "\n";
	print '	<td></td>'. "\n";
	print '	<td><strong>'. $cnt .'</strong> '._('Hosts').'</td>'. "\n";
	print '	<td class="hidden-sm"></td>'. "\n";
	print '	<td class="hidden-sm hidden-xs"></td>'. "\n";
	print '	<td class="hidden-sm hidden-xs"></td>'. "\n";

	//custom
	if(sizeof(@$custom) > 0) {
		foreach($custom_fields as $field) {
			if(!in_array($field['name'], $hidden_fields)) {
				print "<td class='hidden-sm hidden-xs hidden-md'></td>";
			}
		}
	}
	print '	<td class="actions"><a href="'.create_link("tools","devices","hosts","0").'" class="btn btn-sm btn-default"><i class="fa fa-angle-right"></i> '._('Show all hosts').'</a></td>';
	print '</tr>'. "\n";
}

print '</table>';
?>