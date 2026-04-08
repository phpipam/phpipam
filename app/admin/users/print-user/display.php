<tr>
	<td colspan="2"><h4><?php print _('Display settings'); ?></h4><hr></td>
</tr>
<tr>
	<td><?php print _('Theme'); ?></td>
	<td><?php print $user->theme=="" ? _("Default") : $user->theme ?></td>
</tr>
<tr>
	<td><?php print _('Compress override'); ?></td>
	<td><?php print $user->compressOverride==1 ? _("Yes") : _("No") ?></td>
</tr>
<tr>
	<td><?php print _('Hide free range'); ?></td>
	<td><?php print $user->hideFreeRange==1 ? _("Yes") : _("No") ?></td>
</tr>
<tr>
	<td><?php print _('Menu type'); ?></td>
	<td><?php print $user->menuType; ?></td>
</tr>