<?php

/**
 * Script to edit / add / delete groups
 *************************************************/

# verify that user is logged in
$User->check_user_session();

# create csrf token
$csrf = $User->Crypto->csrf_cookie ("create", "pdns_settings");

# if hostname is array implode to ; separated values
if(is_array($pdns->host)) { $pdns->host = implode(";", $pdns->host); }
?>

<script>
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
	<th colspan="3"><h4><?php print _('Database settings'); ?></h4></th>
</tr>

<!-- host -->
<tr>
	<td><?php print _('Host'); ?></th>
	<td style="width:300px;">
		<input type="text" class="form-control input-sm" name="host" value="<?php print $pdns->host; ?>">
		<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
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
		<input type="submit" class="btn btn-default btn-sm submit_popup" data-script="app/admin/powerDNS/settings-save.php" data-result_div="settingsEdit" data-form='pdns-settings' value="<?php print _("Save"); ?>">
	</td>
</tr>

</table>
</form>


<!-- save holder -->
<div id="settingsEdit"></div>

<!-- check -->
<div class="check" style="height:60px;width:auto;position:absolute;">
	<hr>
	<?php
	// multiple databases
	if(strpos($pdns->host, ";")!==false) {
		// multiple ?
		if(isset($PowerDNS->db_check_error)) {
			foreach ($PowerDNS->db_check_error as $err) {
				$Result->show("warning", $err);
			}
			// none
			if ($PowerDNS->active_db===false) {
				$Result->show("danger", "All database connections failed", false);
			}
			// print active
			else {
				// set to array
				$active_db = explode(";", $PowerDNS->db_settings->host);
				// print
				$Result->show("success ", "Database connection ok".". "._("Active database").": ".$active_db[$PowerDNS->active_db]);
			}
		}
		// none selected
		elseif ($PowerDNS->active_db===false) {
			$Result->show("danger", "All database connections failed",false);
		}
		// else
		else {
			print "<div class='clearfix'></div>";
			$Result->show("success ", "All database connections ok");
		}
	}
	else {
		// connection to selected database
		if ($test==false)		{ $Result->show("danger ", "Failed to connect to database:<hr> ".$PowerDNS->error, false); }
		else					{ $Result->show("success ", "Database connection ok"."."); }
	}

	?>
</div>