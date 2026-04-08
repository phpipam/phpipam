<tr>
	<td colspan="2"><h4><?php print _('Authentication settings'); ?></h4><hr></td>
</tr>

<tr>
	<td><?php print _('Role'); ?></td>
	<td><?php print $user->role; ?></td>
</tr>
<tr>
	<td><?php print _('Authentication'); ?></td>
	<td>
	<?php
	if($auth_details===false) 	{ print "<span class='text-muted'>No auth method</span>"; }
	else 					 	{ print $auth_details->type." <span class='text-muted'>(".$auth_details->description.")</span>"; }
	?>
	</td>
</tr>
<tr>
	<td><?php print _('Last login'); ?></td>
	<td><?php print !is_blank($user->lastLogin) ? $user->lastLogin : "<span class='text-muted'>"._("Never")."</span>"; ?></td>
</tr>
<tr>
	<td><?php print _('Last activity'); ?></td>
	<td><?php print !is_blank($user->lastActivity) ? $user->lastActivity : "<span class='text-muted'>"._("Never")."</span>"; ?></td>
</tr>
<tr>
	<td><?php print _('Groups'); ?></td>
	<td>
	<?php
	if($user->role == "Administrator") {
	print _('All groups');
	}
	else {
		$groups = db_json_decode($user->groups, true);
		$gr = $Admin->groups_parse($groups);
		if(sizeof($gr)>0) {
			foreach($gr as $group) {
				print $group['g_name']."<br>";
			}
		}
		else {
			print "<span class='text-muted'>No groups</span>";
		}
	}
	?>
	</td>
</tr>
<tr>
	<td><?php print _('Password change required'); ?></td>
	<td><?php print $user->passChange; ?></td>
</tr>