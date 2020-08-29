<tr>
	<td colspan="2"><h4><?php print _('Telegram settings'); ?></h4><hr></td>
</tr>
<tr>
	<td><?php print _('Telegram user id'); ?></td>
        <td><?php if ($user->telegramId) { ?><a href="tg://user?id=<?php print $user->telegramId; ?>"><?php print $user->telegramId; ?></a><?php } else { print _("No"); } ?></td>
</tr>
<tr>
	<td><?php print _('Telegram notifications'); ?></td>
	<td><?php print $user->role == "Administrator" ? _($user->telegramNotify) : _("No"); ?></td>
</tr>
<tr>
	<td><?php print _('Changelog Telegram notifications'); ?></td>
	<td><?php print $user->role == "Administrator" ? _($user->telegramChangelog) : _("No"); ?></td>
</tr>