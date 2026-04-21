<tr>
	<td colspan="2"><h4><?php print _('Mail settings'); ?></h4><hr></td>
</tr>
<tr>
	<td><?php print _('Mail notifications'); ?></td>
	<td><?php print $user->role == "Administrator" ? _($user->mailNotify) : _("No"); ?></td>
</tr>
<tr>
	<td><?php print _('Changelog notifications'); ?></td>
	<td><?php print $user->role == "Administrator" ? _($user->mailChangelog) : _("No"); ?></td>
</tr>