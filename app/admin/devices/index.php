<?php

/**
 * Script to print devices
 ***************************/

# verify that user is logged in
$User->check_user_session();

# rack object
$Racks      = new phpipam_rack ($Database);

# fetch all Devices
$devices = $Admin->fetch_all_objects("devices", "hostname");

# fetch all Device types and reindex
$device_types = $Admin->fetch_all_objects("deviceTypes", "tid");
if ($device_types !== false) {
	foreach ($device_types as $dt) {
		$device_types_i[$dt->tid] = $dt;
	}
}

# fetch custom fields
$custom = $Tools->fetch_custom_fields('devices');

# get hidden fields
$hidden_custom_fields = json_decode($User->settings->hiddenCustomFields, true);
$hidden_custom_fields = is_array(@$hidden_custom_fields['devices']) ? $hidden_custom_fields['devices'] : array();
?>

<h4><?php print _('Device management'); ?></h4>
<hr>
<div class="btn-group">
	<button class='btn btn-sm btn-default editSwitch' data-action='add'   data-switchid='' style='margin-bottom:10px;'><i class='fa fa-plus'></i> <?php print _('Add device'); ?></button>
	<a href="<?php print create_link("administration", "device-types"); ?>" class="btn btn-sm btn-default"><i class="fa fa-tablet"></i> <?php print _('Manage device types'); ?></a>
</div>

<?php
/* first check if they exist! */
if($devices===false) {
	$Result->show("warning", _('No devices configured').'!', false);
}
/* Print them out */
else {

	print '<table id="switchManagement" class="table table-striped sorted table-td-top" data-cookie-id-table="admin_devices">';

	# headers
	print "<thead>";
	print '<tr>';
	print '	<th>'._('Name').'</th>';
	print '	<th>'._('IP address').'</th>';
	print '	<th>'._('Type').'</th>';
	print '	<th>'._('Description').'</th>';
    if($User->settings->enableSNMP=="1")
	print '	<th>'._('SNMP').'</th>';
    if($User->settings->enableRACK=="1")
	print '	<th>'._('Rack').'</th>';
	print '	<th><i class="icon-gray icon-info-sign" rel="tooltip" title="'._('Shows in which sections device will be visible for selection').'"></i> '._('Sections').'</th>';
	if(sizeof($custom) > 0) {
		foreach($custom as $field) {
			if(!in_array($field['name'], $hidden_custom_fields)) {
				print "<th class='hidden-xs hidden-sm hidden-md'>".$Tools->print_custom_field_name ($field['name'])."</th>";
			}
		}
	}
	print '	<th class="actions"></th>';
	print '</tr>';
    print "</thead>";

    print "<tbody>";
	# loop through devices
	foreach ($devices as $device) {
		//cast
		$device = (array) $device;

		//print details
		print '<tr>'. "\n";

		print '	<td><a class="btn btn-xs btn-default" href="'.create_link("tools","devices",$device['id']).'"><i class="fa fa-desktop prefix"></i> '. $device['hostname'] .'</a></td>'. "\n";
		print '	<td>'. $device['ip_addr'] .'</td>'. "\n";
		print '	<td>'. @$device_types_i[$device['type']]->tname .'</td>'. "\n";
		print '	<td class="description">'. $device['description'] .'</td>'. "\n";

		// SNMP
		if($User->settings->enableSNMP=="1") {
    		print "<td>";
    		// not set
    		if ($device['snmp_version']==0 || strlen($device['snmp_version'])==0) {
        		print "<span class='text-muted'>"._("Disabled")."</span>";
    		}
    		else {
                print _("Version").": $device[snmp_version]<br>";
                print _("Community").": $device[snmp_community]<br>";
    		}
    		print "</td>";
		}

		// rack
        if($User->settings->enableRACK=="1") {
            print "<td>";
            # rack
            $rack = $Racks->fetch_rack_details ($device['rack']);
            if ($rack!==false) {
                print "<a href='".create_link("administration", "racks", $rack->id)."'>".$rack->name."</a><br>";
                print "<span class='badge badge1 badge5'>"._('Position').": $device[rack_start], "._("Size").": $device[rack_size] U</span>";
            }
            print "</td>";
        }

		//sections
		print '	<td class="sections">';
			$temp = explode(";",$device['sections']);
			if( (sizeof($temp) > 0) && (!empty($temp[0])) ) {
			foreach($temp as $line) {
				$section = $Sections->fetch_section(null, $line);
				if(!empty($section)) {
				print '<div class="switchSections text-muted">'. $section->name .'</div>'. "\n";
				}
			}
			}

		print '	</td>'. "\n";

		//custom
		if(sizeof($custom) > 0) {
			foreach($custom as $field) {
				if(!in_array($field['name'], $hidden_custom_fields)) {
					print "<td class='hidden-xs hidden-sm hidden-md'>";
					$Tools->print_custom_field ($field['type'], $device[$field['name']]);
					print "</td>";
				}
			}
		}

		print '	<td class="actions">'. "\n";
		print "	<div class='btn-group'>";
		print "		<button class='btn btn-xs btn-default editSwitch' data-action='edit'   data-switchid='$device[id]' rel='tooltip' title='"._('Edit')."'><i class='fa fa-pencil'></i></button>";
		if($User->settings->enableSNMP=="1")
		print "		<button class='btn btn-xs btn-default editSwitchSNMP' data-action='edit' data-switchid='$device[id]' rel='tooltip' title='Manage SNMP'><i class='fa fa-cogs'></i></button>";
		print "		<button class='btn btn-xs btn-default editSwitch' data-action='delete' data-switchid='$device[id]' rel='tooltip' title='"._('Delete')."'><i class='fa fa-times'></i></button>";
		print "	</div>";
		print '	</td>'. "\n";

		print '</tr>'. "\n";

	}
	print "</tbody>";
	print '</table>';
}
?>

<!-- edit result holder -->
<div class="switchManagementEdit"></div>
