<?php

/**
 * Script to remove devices from group
 *************************************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Tools	 	= new Tools ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();
# verify that user has permission to module
$User->check_module_permissions ("devices", User::ACCESS_R, true, false);


# id must be numeric
if(!is_numeric($POST->g_id))		{ $Result->show("danger", _("Invalid ID"), true, true); }

# get group details
$group   = $Tools->fetch_object("deviceGroups", "id", $POST->g_id);
# devices in group - array of ids
$group_members = $Tools->fetch_multiple_objects("device_to_group", "g_id", $POST->g_id, 'g_id');
$group_members = ($group_members !== false) && isset($group_members) ? (array)$group_members : [];
?>


<!-- header -->
<div class="pHeader"><?php print _('Remove devices from group'); ?> <b><i> <?php print $group->name; ?></i></b></div>


<!-- content -->
<div class="pContent">

	<?php if(sizeof($group_members) > 0) { ?>

	<form id="groupRemoveDevices" name="groupRemoveDevices">
	<table class="groupEdit table table-condensed table-top">

	<tr>
		<th>
			<input type="hidden" name="g_id" value="<?php print escape_input($POST->g_id); ?>">
		</th>
		<th><?php print _('Name'); ?></th>
		<th><?php print _('IP address'); ?></th>
		<th><?php print _('Description'); ?></th>
	</tr>

	<?php
	# show group members
	foreach($group_members as $k => $m) {
		# get user details
		$d = (array)$Tools->fetch_object("devices", "id", $m->d_id);

		print "<tr>";

		print "	<td>";
		print "	<input type='checkbox' name='device$d[id]'>";
		print "	</td>";

		print "	<td>$d[hostname]</td>";
		print "	<td>$d[ip_addr]</td>";
		print "	<td>$d[description]</td>";

		print "</tr>";
	}
	?>

    </table>
    </form>

    <?php } else { $Result->show("info", _('No devices in this group'), false);  } ?>
</div>


<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<?php if(sizeof($group_members) > 0) { ?>
		<button class='btn btn-sm btn-success submit_popup' data-script="app/admin/device-groups/remove-devices-result.php" data-result_div="groupRemoveDevicesResult" data-form='groupRemoveDevices'><?php print _('Remove selected devices'); ?></button>
		<?php } ?>
	</div>

	<!-- Result -->
	<div id="groupRemoveDevicesResult"></div>
</div>
