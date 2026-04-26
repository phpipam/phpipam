<tr>
	<td colspan="2"><h4><?php print _('Module permissions'); ?></h4><hr></td>
</tr>

<tr>
	<td colspan="2">
		<?php
		$user = (array) $user;
		include(__DIR__."/../print_module_permissions.php");
		$user = (object) $user;
		?>
	</td>
</tr>