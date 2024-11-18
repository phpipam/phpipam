<?php

/**
 * Script to edit / add / delete groups
 *************************************************/

# verify that user is logged in
$User->check_user_session();

# fetch all APIs
$groups = $Admin->fetch_all_objects("userGroups", "g_name");

# fetch all admin users
$admins = $Admin->fetch_multiple_objects("users", "role", "Administrator");

# fetch all auth methods
$auth_methods = $Admin->fetch_all_objects ("usersAuthMethod");
if ($auth_methods!==false) {
	foreach ($auth_methods as $k=>$m) {
		if (!($m->type=="AD" || $m->type=="LDAP")) {
			unset($auth_methods[$k]);
		}
	}
	// none
	if (sizeof($auth_methods)==0) {
		$auth_methods = false;
	}
}

# fetch custom fields
$custom = $Tools->fetch_custom_fields('userGroups');

/* check customfields */
$ffields = db_json_decode($User->settings->hiddenCustomFields, true);
$ffields = is_array(@$ffields['userGroups']) ? $ffields['userGroups'] : array();

$colspanCustom = 0;
?>


<!-- display existing groups -->
<h4><?php print _('Group management'); ?></h4>
<hr><br>

<!-- Add new -->
<div class="btn-group">
	<button class='btn btn-sm btn-default open_popup' data-script='app/admin/groups/edit-group.php' data-class='700' data-action='add'><i class='fa fa-plus'></i> <?php print _('Create group'); ?></button>
	<?php if($auth_methods!==false) { ?>
	<button class='btn btn-sm btn-default adLookup'><i class='fa fa-search'></i> <?php print _('Search domain groups'); ?></button>
	<?php } ?>
</div>

<!-- table -->
<table id="userPrint" class="table sorted table-striped table-top" data-cookie-id-table="admin_groups">

<!-- Headers -->
<thead>
<tr>
    <th><?php print _('Group'); ?></th>
    <th><?php print _('Belonging users'); ?></th>
    <th><?php print _('Section permissions'); ?></th>
	<?php
	if(sizeof(@$custom) > 0) {
		foreach($custom as $field) {
			if(!in_array($field['name'], $ffields)) {
				$colspanCustom++;
				print "<th>".$Tools->print_custom_field_name ($field['name'])."</th>";
			}
		}
	}
	?>
    <th></th>
    <th></th>
</tr>
</thead>

<tbody>
<!-- admins -->
<tr>
	<td>
		<span class='badge badge1 badge-white'><?php print _('Administrators'); ?></span><br>
		<span class="muted"><?php print _('Administrator level users'); ?></span>
	</td>
	<td>
	<?php
	foreach($admins as $user) {
			print '<img src="css/images/userVader.png" alt="Standard user icon" rel="tooltip" title="" data-original-title="User"> '."<a href='".create_link("administration","users",$user->id)."'>".$user->real_name."</a><br>";
	}
	?>
	</td>
	<td><?php print _('All sections:'); ?> <span class="badge badge1 badge5"><?php print _("Read / Write"); ?></span></td>
	<td colspan="<?php print 2+$colspanCustom; ?>"></td>
</tr>

<?php
/* print existing sections */
if($groups) {
	foreach ($groups as $g) {
		//cast
		$g = (array) $g;

		print '<tr>' . "\n";
		print '	<td>';
		print '		<span class="badge badge1 badge-white">' . $g['g_name'] . '</span><br>'. "\n";
		print '		<span class="muted">' . $g['g_desc'] . '</span></td>'. "\n";
		# users in group
		print "	<td>";
		$u = $Admin->group_fetch_users($g['g_id']);
		if(sizeof($u)>0) {
			foreach($u as $name) {
				# get details
				$user = $Admin->fetch_object("users", "id", $name);
				print '<img src="css/images/userTrooper.png" alt="Standard user icon" rel="tooltip" title="" data-original-title="User"> '."<a href='".create_link("administration","users",$user->id)."'>".$user->real_name."</a><br>";
			}
		} else {
			print "<span class='text-muted'>"._("No users")."</span>";
		}
		print "</td>";

		# section permissions
		print "	<td>";
		$permissions = $Sections->get_group_section_permissions ($g['g_id']);
		if(sizeof($permissions)>0) {
			foreach($permissions as $sec=>$perm) {
				# reformat permissions
				$perm = $Subnets->parse_permissions($perm);
				print $sec." : <span class='badge badge1 badge5'>".$perm."</span><br>";
			}
		}
		print "</td>";

		# custom
		if(sizeof($custom) > 0) {
			foreach($custom as $field) {
				if(!in_array($field['name'], $ffields)) {
					print "<td class='hidden-xs hidden-sm hidden-md'>";
					$Tools->print_custom_field ($field['type'], $g[$field['name']]);
					print "</td>";
				}
			}
		}

		# add/remove users
		print "	<td class='actions'>";
		print "	<div class='btn-group'>";
		print "		<a class='btn btn-xs btn-default open_popup' data-script='app/admin/groups/add-users.php' data-class='700' data-action='add' data-g_id='$g[g_id]' rel='tooltip' data-container='body'  title='"._('add users to this group')."'><i class='fa fa-plus'></i></a>";
		print "		<a class='btn btn-xs btn-default open_popup' data-script='app/admin/groups/remove-users.php' data-class='700' data-action='remove' data-g_id='$g[g_id]' rel='tooltip' data-container='body'  title='"._('remove users from this group')."'><i class='fa fa-minus'></i></a>";
		print "	</div>";
		print "</td>";

		# edit, delete
		print "<td class='actions'>";
		print "	<div class='btn-group'>";
		print "		<a class='btn btn-xs btn-default open_popup' data-script='app/admin/groups/edit-group.php' data-class='700' data-action='edit' data-id='$g[g_id]'><i class='fa fa-pencil'></i></a>";
		print "		<a class='btn btn-xs btn-default open_popup' data-script='app/admin/groups/edit-group.php' data-class='700' data-action='delete' data-id='$g[g_id]'><i class='fa fa-times'></i></a>";
		print "	</div>";
		print "</td>";

		print '</tr>' . "\n";
	}
}

?>
</tbody>
</table>
