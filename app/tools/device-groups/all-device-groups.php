<script>
/* fix for ajax-loading tooltips */
$('body').tooltip({ selector: '[rel=tooltip]' });
</script>

<?php

/**
 * Script to display device groups
 *
 */

# verify that user is logged in
$User->check_user_session();
# verify that user has permission to module
$User->check_module_permissions ("devices", User::ACCESS_R, true, false);

# filter devices or fetch print all?
$device_groups = $Tools->fetch_all_objects("deviceGroups", "name");

// filter flag
$filter = false;
?>

<h4> <?php print _('Device group management') ?></h4>
<hr><br>

<!-- Add new -->
<div class='btn-group'>
	<button class='btn btn-sm btn-default open_popup' data-script='app/admin/device-groups/edit-group.php' data-class='700' data-action='add'><i class='fa fa-plus'></i> <?php print _('Create device group') ?> </button>
</div>

<!-- table -->
<table id='deviceGroupManagement' class='table sorted table-striped table-top' data-cookie-id-table='device_groups'>

<!-- Headers -->
<thead>
 <tr>
  <th> <?php print _('Device group') ?> </th>
  <th> <?php print _('Belonging devices') ?> </th>
  <th> <?php print _('Description') ?> </th>
  <th></th>
  <th></th>
 </tr>
</thead>

<tbody>

<?php

// no device groups available
if($device_groups === false) {
	$colspan = 5;
	print "<tr>";
	print "	<td colspan='$colspan'>".$Result->show('info', _('No results')."!", false, false, true)."</td>";
	print "</tr>";
}
// result
else {
	foreach ($device_groups as $device_group) {
		//cast
		$device_group = (array) $device_group;

		// print details
		print '<tr>';		

		print " <td><span class='badge badge1 badge-white'>" . $device_group['name'] . "</span></td>";

		# filter devices or fetch print all?
		$device_group_memberships = $Tools->fetch_multiple_objects("device_to_group", "g_id", $device_group['id'], 'g_id');
		
		print " <td>";
		if ($device_group_memberships === false) {
			print "<span class='text-muted'>"._("No devices")."</span>";
		} else {
			//cast
			$device_group_memberships = (array) $device_group_memberships;

			foreach ($device_group_memberships as $k => $device_group_membership) {
				$device = $Tools->fetch_object("devices", "id", $device_group_membership->d_id);

				print "<a class='btn btn-xs btn-default' href='".create_link("tools", "devices", $device->id)."'><i class='fa fa-desktop prefix'></i> ". $device->hostname .'</a><br>';
			}
		}
		print " </td>";

		print "<td class='description'>" . $device_group['desc'] ."</td>";

		# actions
		if($User->get_module_permissions ("devices") >= User::ACCESS_RW) {
			# add/remove users
			print "	<td class='actions'>";
			print "	<div class='btn-group'>";
			print "		<a class='btn btn-xs btn-default open_popup' data-script='app/admin/device-groups/add-devices.php'    data-class='700' data-action='add'    data-g_id='$device_group[id]' rel='tooltip' data-container='body'  title='" . _('add device to this group')      . "'><i class='fa fa-plus' ></i></a>";
			print "		<a class='btn btn-xs btn-default open_popup' data-script='app/admin/device-groups/remove-devices.php' data-class='700' data-action='remove' data-g_id='$device_group[id]' rel='tooltip' data-container='body'  title='" . _('remove device from this group') . "'><i class='fa fa-minus'></i></a>";
			print "	</div>";
			print "</td>";

			# edit, delete
			print "<td class='actions'>";
			print "	<div class='btn-group'>";
			print "		<a class='btn btn-xs btn-default open_popup' data-script='app/admin/device-groups/edit-group.php' data-class='700' data-action='edit'   data-g_id='$device_group[id]'><i class='fa fa-pencil'></i></a>";
			print "		<a class='btn btn-xs btn-default open_popup' data-script='app/admin/device-groups/edit-group.php' data-class='700' data-action='delete' data-g_id='$device_group[id]'><i class='fa fa-times' ></i></a>";
			print "	</div>";
			print "</td>";
		} else {
			print "<td></td><td></td>";
		}

		print "</tr>";
	}
}
print "</tbody>";
print '</table>';