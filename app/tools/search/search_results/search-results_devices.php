<?php

/*
 * Script to display search results
 **********************************/

# verify that user is logged in
$User->check_user_session();

# get all custom fields
$custom_device_fields = $GET->devices=="on"     ? $Tools->fetch_custom_fields ("devices") : array();
$hidden_device_fields = is_array(@$hidden_fields['devices']) ? $hidden_fields['devices'] : array();

# search devices
$result_devices = $Tools->search_devices($searchTerm, $custom_device_fields);

$device_types = $Tools->fetch_all_objects ("deviceTypes", "tname");
$type_hash = [];
foreach($device_types as $t){  $type_hash[$t->tid] = $t->tname; }
?>

<br>
<h4><?php print _('Search results (Devices)');?>:</h4>
<hr>

<table class="searchTable table sorted table-striped table-condensed table-top" data-cookie-id-table="search_vlan">

<!-- headers -->
<thead>
<tr id="searchHeader">
	<th><?php print _('Hostname');?></th>
	<th><?php print _('Description');?></th>
	<th><?php print _('Type');?></th>
	<?php
	if(sizeof($custom_device_fields) > 0) {
		foreach($custom_device_fields as $field) {
			if(!in_array($field['name'], $hidden_device_fields)) {
				print "	<th class='hidden-xs hidden-sm'>".$Tools->print_custom_field_name ($field['name'])."</th>";
			}
		}
	}
	?>
	<th></th>
</tr>
</thead>

<tbody>
<?php
if(sizeof($result_devices) > 0) {
	# print devices
	foreach($result_devices as $device) {
		# cast
		$device = (array) $device;

		print '<tr class="nolink">' . "\n";
		print ' <td><dd><a class="btn btn-xs btn-default" href="'.create_link("tools","devices",$device['id']).'">'. $device['hostname'] .'</a></dd></td>' . "\n";
		print ' <td><dd>'. $device['description'] .'</dd></td>' . "\n";
		print ' <td><dd>'. $type_hash[$device['type']] .'</dd></td>' . "\n";
		# custom fields
		if(sizeof($custom_device_fields) > 0) {
			foreach($custom_device_fields as $field) {
				if(!in_array($field['name'], $hidden_device_fields)) {
					// what are we creating links to??
					$device[$field['name']] = $Tools->create_links ($device[$field['name']], $field['type']);
					print "	<td class='hidden-xs hidden-sm'>";
					$Tools->print_custom_field ($field['type'], $device[$field['name']]);
					print "</td>";
				}
			}
		}
		// actions
		print "<td class='actions'>";
		$links = [];
		if($User->get_module_permissions ("devices")>=User::ACCESS_R) {
			$links[] = ["type"=>"header", "text"=>_("View")];
			$links[] = ["type"=>"link", "text"=>_("Show Device"), "href"=>create_link("tools", "devices", $device['id']), "icon"=>"eye", "visible"=>"dropdown"];
			$links[] = ["type"=>"divider"];
		}
		if($User->get_module_permissions ("devices")>=User::ACCESS_RW) {
			$links[] = ["type"=>"header", "text"=>_("Manage")];
			$links[] = ["type"=>"link", "text"=>_("Edit Device"), "href"=>"", "class"=>"open_popup", "dataparams"=>" data-script='app/admin/devices/edit.php' data-action='edit' data-switchid='{$device['id']}'", "icon"=>"pencil"];
		}
		if($User->get_module_permissions ("devices")>=User::ACCESS_RWA) {
			$links[] = ["type"=>"link", "text"=>_("Delete Device"), "href"=>"", "class"=>"open_popup", "dataparams"=>" data-script='app/admin/devices/edit.php' data-action='delete' data-switchid='{$device['id']}'", "icon"=>"times"];
		}
		// print links
		print $User->print_actions($User->user->compress_actions, $links);
		print "</td>";
		print '</tr>'. "\n";
	}
}
?>
</tbody>
</table>
<?php
if(sizeof($result_devices) == 0) {
	$Result->show("info", _("No results"), false);
}
