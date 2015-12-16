<?php

/**
 * Script to edit / add / delete groups
 *************************************************/

# verify that user is logged in
$User->check_user_session();
?>

<script type="text/javascript">
$(document).ready(function() {
	/* bootstrap switch */
	var switch_options = {
	    onColor: 'default',
	    offColor: 'default',
	    size: "mini"
	};
	$(".input-switch").bootstrapSwitch(switch_options);
});
</script>


<!-- database settings -->
<form name="pdns" id="pdns-settings">
<table id="settings" class="table table-hover table-condensed table-auto">

<!-- site settings -->
<tr class="settings-title">
	<th colspan="3"><h4><?php print _('Database settings'); ?></h4><hr></th>
</tr>

<!-- host -->
<tr>
	<td><?php print _('Host'); ?></th>
	<td style="width:300px;">
		<input type="text" class="form-control input-sm" name="host" value="<?php print $pdns->host; ?>">
	</td>
</tr>
<!-- db -->
<tr>
	<td><?php print _('Database'); ?></th>
	<td>
		<input type="text" class="form-control input-sm" name="name" value="<?php print $pdns->name; ?>">
	</td>
</tr>
<!-- user -->
<tr>
	<td><?php print _('Username'); ?></th>
	<td>
		<input type="text" class="form-control input-sm" name="username" value="<?php print $pdns->username; ?>">
	</td>
</tr>
<!-- pass -->
<tr>
	<td><?php print _('Password'); ?></th>
	<td>
		<input type="password" class="form-control input-sm" name="password" value="<?php print $pdns->password; ?>">
	</td>
</tr>
<!-- port -->
<tr>
	<td><?php print _('Port'); ?></th>
	<td>
		<input type="text" class="form-control input-sm" name="port" value="<?php print $pdns->port; ?>">
	</td>
</tr>
<!-- autoserial -->
<tr>
	<td><?php print _('Autoserial'); ?></th>
	<td>
        <input type="checkbox" class="input-switch" value="Yes" name="autoserial" <?php if(@$pdns->autoserial == "Yes") print 'checked'; ?>>
	</td>
</tr>
<!-- submit -->
<tr>
	<td></td>
	<td style="text-align: right">
		<input type="submit" class="btn btn-default btn-sm" value="<?php print _("Save"); ?>">
	</td>
</tr>

</table>
</form>


<!-- save holder -->
<div class="settingsEdit"></div>

<!-- check -->
<div class="check" style="height:60px;">
	<?php
	if ($test==false)		{ $Result->show("danger alert-absolute", "Failed to connect to database:<hr> ".$PowerDNS->error); }
	else					{ $Result->show("success alert-absolute", "Database connection ok"); }
	?>
</div>