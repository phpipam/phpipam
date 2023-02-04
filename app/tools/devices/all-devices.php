<script>
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
# perm check
$User->check_module_permissions ("devices", User::ACCESS_R, true, false);

# filter devices or fetch print all?
$devices = $Tools->fetch_all_objects("devices", "hostname");
if($User->settings->enableRACK=="1") {
	# rack object
	$Racks = new phpipam_rack ($Database);
	$Racks->add_rack_start_print($devices);
}
$device_types = $Tools->fetch_all_objects ("deviceTypes", "tid");

# get custom device fields
$custom_fields = (array) $Tools->fetch_custom_fields('devices');

# set hidden fields
$hidden_fields = pf_json_decode($User->settings->hiddenCustomFields, true);
$hidden_fields = is_array(@$hidden_fields['devices']) ? $hidden_fields['devices'] : array();

# size of custom fields
$csize = sizeof($custom_fields) - sizeof($hidden_fields);

// filter flag
$filter = false;

// reindex types
if (isset($device_types)) {
	foreach($device_types as $dt) {
		$device_types_indexed[$dt->tid] = $dt;
	}
}

# strip tags - XSS
$_GET = $User->strip_input_tags ($_GET);

# title
print "<h4>"._('List of devices')."</h4>";
print "<hr>";

# print link to manage
print "<div class='btn-group'>";
	//administer
	if($User->get_module_permissions ("devices")>=User::ACCESS_RW) {
		print "<button class='btn btn-sm btn-default btn-success open_popup' data-script='app/admin/devices/edit.php' data-class='500' data-action='add' data-switchid='' style='margin-bottom:10px;'><i class='fa fa-plus'></i> "._('Add device')."</button>"; }
	//admin
	if($User->is_admin(false))
	print "<a href='".create_link("administration", "device-types")."' class='btn btn-sm btn-default'><i class='fa fa-tablet'></i> "._('Manage device types')."</a>";
print "</div>";

# filter
include_once ("all-devices-filter.php");

# table
print '<table id="switchManagement" class="table sorted sortable table-striped table-top" data-cookie-id-table="devices_all">';

$colspanCustom = sizeof($custom_fields) - sizeof($hidden_fields);;

#headers
print "<thead>";
print '<tr>';
print "	<th>"._('Name')."</th>";
print "	<th>"._('IP address')."</th>";
print "	<th>"._('Description').'</th>';
if($User->settings->enableRACK=="1" && $User->get_module_permissions ("racks")>=User::ACCESS_R) {
print '	<th>'._('Rack').'</th>';
$colspanCustom++;
}
if($User->settings->enableLocations=="1" && $User->get_module_permissions ("locations")>=User::ACCESS_R) {
print "	<th>"._('Location').'</th>';
$colspanCustom++;
}
print "	<th style='color:#428bca'>"._('Number of hosts').'</th>';
print "	<th class='hidden-sm'>". _('Type').'</th>';

if(sizeof(@$custom_fields) > 0) {
	foreach($custom_fields as $field) {
		if(!in_array($field['name'], $hidden_fields)) {
			print "	<th class='hidden-xs hidden-sm hidden-md'>".$Tools->print_custom_field_name ($field['name'])."</th>";
		}
	}
}

if($User->get_module_permissions ("devices")>=User::ACCESS_RW)
print '	<th class="actions"></th>';
print '</tr>';
print "</thead>";

print "<tbody>";
// no devices
if($devices===false) {
	$colspan = 6 + $colspanCustom;
	print "<tr>";
	print "	<td colspan='$colspan'>".$Result->show('info', _('No results')."!", false, false, true)."</td>";
	print "</tr>";
}
// result
else {
	$cnt_ips     = $Tools->count_all_database_objects("ipaddresses","switch");
	$cnt_subnets = $Tools->count_all_database_objects("subnets","device");

	foreach ($devices as $device) {
		//cast
		$device = (array) $device;

		//count items
		$cnt1 = isset($cnt_ips[$device['id']])     ?  $cnt_ips[$device['id']]     : 0;
		$cnt2 = isset($cnt_subnets[$device['id']]) ?  $cnt_subnets[$device['id']] : 0;
		$cnt = $cnt1 + $cnt2;

		// print details
		print '<tr>'. "\n";

		print "	<td><a class='btn btn-xs btn-default' href='".create_link("tools","devices",$device['id'])."'><i class='fa fa-desktop prefix'></i> ". $device['hostname'] .'</a></td>'. "\n";
		print "	<td>". $device['ip_addr'] .'</td>'. "\n";
		print '	<td class="description">'. $device['description'] .'</td>'. "\n";
		// rack
	    if($User->settings->enableRACK=="1" && $User->get_module_permissions ("racks")>=User::ACCESS_R) {
	        print "<td>";
	        # rack
	        $rack = $Racks->fetch_rack_details ($device['rack']);
	        if (is_object($rack)) {
	            print "<a href='".create_link("tools", "racks", $rack->id)."'>".$rack->name."</a><br>";
	            print "<span class='badge badge1 badge5'>"._('Position').": $device[rack_start_print], "._("Size").": $device[rack_size] U</span>";
	        }
	        print "</td>";
	    }
	    // location
		if($User->settings->enableLocations=="1" && $User->get_module_permissions ("locations")>=User::ACCESS_R) {
			print "<td>";
			// Only show nameservers if defined for subnet
    		if(!empty($device['location']) && $device['location']!=0) {
    			# fetch recursive nameserver details
    			$location2 = $Tools->fetch_object("locations", "id", $device['location']);
                if($location2!==false) {
                    print "<a href='".create_link("tools", "locations", $device['location'])."'>$location2->name</a>";
                }
    		}
			print "</td>";
		}
		print '	<td><span class="badge badge1 badge5">'. $cnt .'</span> '._('Objects').'</td>'. "\n";
		print '	<td class="hidden-sm">'. $device_types_indexed[$device['type']]->tname .'</td>'. "\n";

        //custom fields - no subnets
        if(sizeof(@$custom_fields) > 0) {
	   		foreach($custom_fields as $field) {
		   		# hidden
		   		if(!in_array($field['name'], $hidden_fields)) {
					print "<td class='hidden-xs hidden-sm hidden-md'>";
					$Tools->print_custom_field ($field['type'], $device[$field['name']]);
					print "</td>";
				}
	    	}
	    }

		# actions
		if($User->get_module_permissions ("devices")>=User::ACCESS_RW) {
            // links
            print "<td class='actions'>";
            $links = [];
            $links[] = ["type"=>"header", "text"=>_("Manage device")];
            $links[] = ["type"=>"link", "text"=>_("Edit device"), "href"=>"", "class"=>"open_popup", "dataparams"=>" data-script='app/admin/devices/edit.php' data-class='500' data-action='edit' data-switchId='$device[id]'", "icon"=>"pencil"];

            if($User->get_module_permissions ("devices")>=User::ACCESS_RWA) {
	            $links[] = ["type"=>"link", "text"=>_("Delete device"), "href"=>"", "class"=>"open_popup", "dataparams"=>" data-script='app/admin/devices/edit.php' data-class='500' data-action='delete' data-switchId='$device[id]'", "icon"=>"times"];
	            $links[] = ["type"=>"divider"];
            }
			if($User->settings->enableSNMP=="1" && $User->is_admin(false)) {
	            $links[] = ["type"=>"header", "text"=>_("SNMP")];
	            $links[] = ["type"=>"link", "text"=>_("Manage SNMP"), "href"=>"", "class"=>"open_popup", "dataparams"=>"  data-script='app/admin/devices/edit-snmp.php' data-class='500' data-action='edit' data-switchId='$device[id]''", "icon"=>"cogs"];
			}
            // print links
            print $User->print_actions($User->user->compress_actions, $links);
            print "</td>";

		}

		print '</tr>'. "\n";
	}

	# print for unspecified
	if (!$filter) {
		print '<tr class="unspecified">'. "\n";

	    // count empty
		$cnt1 = (isset($cnt_ips[""]) ? $cnt_ips[""] : 0)         + (isset($cnt_ips[0]) ? $cnt_ips[0] : 0);
		$cnt2 = (isset($cnt_subnets[""]) ? $cnt_subnets[""] : 0) + (isset($cnt_subnets[0]) ? $cnt_subnets[0] : 0);
		$cnt = $cnt1 + $cnt2;


		print '	<td>'._('Device not specified').'</td>'. "\n";
		print '	<td></td>'. "\n";
		print '	<td></td>'. "\n";
		if($User->settings->enableRACK=="1" && $User->get_module_permissions ("racks")>=User::ACCESS_R) {
		print '	<td></td>'. "\n";
		}
		if($User->settings->enableSNMP=="1" && $User->is_admin(false)) {
		print '	<td></td>'. "\n";
		}
		print '	<td><span class="badge badge1 badge5">'. $cnt .'</span> '._('Objects').'</td>'. "\n";
		print '	<td class="hidden-sm"></td>'. "\n";

		print ' <td colspan="'.$csize.'"></td>';

		if($User->get_module_permissions ("devices")>=User::ACCESS_RW)
		print '	<td class="actions"></td>';
		print '</tr>'. "\n";
	}
}
print "</tbody>";
print '</table>';