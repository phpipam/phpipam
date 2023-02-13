<tr>
	<td colspan="2"><h4><?php print _('Account details'); ?></h4><hr></td>
</tr>

<tr>
	<td><?php print _('Real Name'); ?></td>
	<td><strong><?php print $user->real_name; ?></strong></td>
</tr>

<tr>
	<td><?php print _('Status'); ?></td>
	<td><strong><?php print $user->disabled=="Yes" ? "<span class='badge badge1 badge5 alert-danger'>"._("Disabled")."</span>" : "<span class='badge badge1 badge5 alert-success'>"._("Enabled")."</span>" ?></strong></td>
</tr>
<tr>
	<td><?php print _('Username'); ?></td>
	<td><?php print $user->username; ?></td>
</tr>
<tr>
	<td><?php print _('E-mail'); ?></td>
	<td><?php print $user->email; ?></td>
</tr>
<tr>
	<td><?php print _('Language'); ?></td>
	<td><?php print $language->l_name; ?></td>
</tr>
<tr>
	<td></td>
	<td>
	<div class='btn-group'>
		<button class='btn btn-xs btn-default open_popup' data-script='app/admin/users/edit.php' data-class='700' data-action='edit' data-id='<?php print $user->id; ?>'><i class='fa fa-pencil'></i></button>
		<button class='btn btn-xs btn-default open_popup' data-script='app/admin/users/edit.php' data-class='700' data-action='delete' data-id='<?php print $user->id; ?>'><i class='fa fa-times'></i></button>
	</div>
	</td>
</tr>




<tr>
	<td colspan="2"><h4><?php print _('Custom fields'); ?></h4><hr></td>
</tr>

<?php
# custom subnet fields
if(sizeof($custom_fields) > 0) {
	foreach($custom_fields as $key=>$field) {
		$user->{$key} = str_replace("\n", "<br>",$user->{$key});
		print "<tr>";
		print "	<td>$key</td>";
		print "	<td>";
		//no length
		if(is_blank($user->{$key})) {
			print "/";
		}
		//booleans
		elseif($field['type']=="tinyint(1)")	{
			if($user->{$key} == "0")		{ print _("No"); }
			elseif($user->{$key} == "1")	{ print _("Yes"); }
		}
		else {
			print $user->{$key};
		}
		print "	</td>";
		print "</tr>";
		}
}
else {
	print "<tr>";
	print "	<td colspan='2'>";
	$Result->show ("muted", _("No custom fields"), false);
	print "	</td>";
	print "</tr>";
}
?>