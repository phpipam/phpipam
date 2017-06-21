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
?>


<!-- display existing groups -->
<h4><?php print _('Group management'); ?></h4>
<hr><br>

<!-- Add new -->
<div class="btn-group">
	<button class='btn btn-sm btn-default editGroup' style="margin-bottom:10px;" data-action='add'><i class='fa fa-plus'></i> <?php print _('Create group'); ?></button>
	<?php if($auth_methods!==false) { ?>
	<button class='btn btn-sm btn-default adLookup'><i class='fa fa-search'> <?php print _('Search domain groups'); ?></i></button>
	<?php } ?>
</div>

<!-- table -->
<table id="userPrint1" class="table sorted table-striped table-top">

<!-- Headers -->
<thead>
<tr>
    <th><?php print _('Group name'); ?></th>
    <th><?php print _('Group description'); ?></th>
    <th><?php print _('Belonging users'); ?></th>
    <th><?php print _('Section permissions'); ?></th>
    <th colspan="2"></th>
</tr>
</thead>

<tbody>
<!-- admins -->
<tr>
	<td><strong><?php print _('Administrators'); ?></strong></td>
	<td><?php print _('Administrator level users'); ?></td>
	<td>
	<?php
	foreach($admins as $a) {
		print $a->real_name."<br>";
	}
	?>
	</td>
	<td><?php print _('All sections :'); ?> <span class="badge badge1 badge5">Read / Write</span></td>
	<td colspan="2"></td>
</tr>

<?php
/* print existing sections */
if($groups) {
	foreach ($groups as $g) {
		//cast
		$g = (array) $g;

		print '<tr>' . "\n";
		print '	<td><strong>' . $g['g_name'] . '</strong></td>'. "\n";
		print '	<td>' . $g['g_desc'] . '</td>'. "\n";
		# users in group
		print "	<td>";
		$u = $Admin->group_fetch_users($g['g_id']);
		if(sizeof($u)>0) {
			foreach($u as $name) {
				# get details
				$user = $Admin->fetch_object("users", "id", $name);
				print $user->real_name."<br>";
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


		# add/remove users
		print "	<td class='actions'>";
		print "	<div class='btn-group'>";
		print "		<button class='btn btn-xs btn-default addToGroup' 		data-groupid='$g[g_id]' data-action='add'    rel='tooltip' data-container='body'  title='"._('add users to this group')."'>   	<i class='fa fa-plus'></i></button>";
		print "		<button class='btn btn-xs btn-default removeFromGroup' 	data-groupid='$g[g_id]' data-action='remove' rel='tooltip' data-container='body'  title='"._('remove users from this group')."'><i class='fa fa-minus'></i></button>";
		print "	</div>";
		print "</td>";

		# edit, delete
		print "<td class='actions'>";
		print "	<div class='btn-group'>";
		print "		<button class='btn btn-xs btn-default editGroup'  		data-groupid='$g[g_id]' data-action='edit'   rel='tooltip' data-container='body'  title='"._('edit group details')."'>	<i class='fa fa-pencil'></i></button>";
		print "		<button class='btn btn-xs btn-default editGroup'  		data-groupid='$g[g_id]' data-action='delete' rel='tooltip' data-container='body'  title='"._('remove group')."'>		<i class='fa fa-times'></i></button>";
		print "	</div>";
		print "</td>";

		print '</tr>' . "\n";
	}
}

?>
</tbody>
</table>