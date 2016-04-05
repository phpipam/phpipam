<?php

/**
 * Script to edit / add / delete groups
 *************************************************/

# verify that user is logged in
$User->check_user_session();

# create csrf token
$csrf = $User->csrf_cookie ("create", "pdns_defaults");

?>
<!-- database settings -->
<form name="pdns" id="pdns-defaults">
<table id="settings" class="table table-hover table-condensed table-auto">

<!-- site settings -->
<tr class="settings-title">
	<th colspan="3"><h4><?php print _('Default value settings'); ?></h4><hr></th>
</tr>

<!-- ns -->
<tr>
	<td><?php print _('Name servers'); ?></th>
	<td style="width:300px;">
		<input type="text" class="form-control input-sm" name="ns" value="<?php print $pdns->ns; ?>">
		<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
	</td>
	<td>
		<span class="text-muted"><?php print _("Enter name servers, separate multiple with ;"); ?></span>
	</td>
</tr>
</tr>

<!-- mail -->
<tr>
	<td><?php print _('Hostmaster'); ?></th>
	<td>
		<input type="text" class="form-control input-sm" name="hostmaster" value="<?php print $pdns->hostmaster; ?>">
	</td>
	<td>
		<span class="text-muted"><?php print _("Enter default hostmaster for domain"); ?></span>
	</td>
</tr>

<!-- default PTR domain -->
<tr>
	<td><?php print _('Default PTR domain'); ?></th>
	<td>
		<input type="text" class="form-control input-sm" name="def_ptr_domain" placeholder="Not used" value="<?php print $pdns->def_ptr_domain; ?>">
	</td>
	<td>
		<span class="text-muted"><?php print _("Default PTR domain if no valid hostname for PTR is provided"); ?></span>
	</td>
</tr>

<!-- refresh -->
<tr>
	<td><?php print _('Refresh'); ?></th>
	<td>
		<select name="refresh" class="form-control input-w-auto input-sm" <?php print $readonly; ?>>
		<?php
		// loop
		foreach($PowerDNS->ttl as $k=>$ttl) {
			// active
			if ($k == @$pdns->refresh)	{ $selected = "selected"; }
			else						{ $selected = ""; }
			// print
			print "<option value='$k' $selected>$ttl ($k)</option>";
		}
		?>
		</select>
	</td>
	<td>
		<span class="text-muted"><?php print _("How often a secondary will poll the primary server to see if the serial number for the zone has increased."); ?></span>
	</td>
</tr>
<!-- retry -->
<tr>
	<td><?php print _('Retry'); ?></th>
	<td>
		<select name="retry" class="form-control input-w-auto input-sm" <?php print $readonly; ?>>
		<?php
		// loop
		foreach($PowerDNS->ttl as $k=>$ttl) {
			// active
			if ($k == @$pdns->retry)	{ $selected = "selected"; }
			else						{ $selected = ""; }
			// print
			print "<option value='$k' $selected>$ttl ($k)</option>";
		}
		?>
		</select>
	</td>
	<td>
		<span class="text-muted"><?php print _("If a secondary was unable to contact the primary at the last refresh, wait the retry value before trying again."); ?></span>
	</td>
</tr>
<!-- expire -->
<tr>
	<td><?php print _('Expire'); ?></th>
	<td>
		<select name="expire" class="form-control input-w-auto input-sm" <?php print $readonly; ?>>
		<?php
		// loop
		foreach($PowerDNS->ttl as $k=>$ttl) {
			// active
			if ($k == @$pdns->expire)	{ $selected = "selected"; }
			else						{ $selected = ""; }
			// print
			print "<option value='$k' $selected>$ttl ($k)</option>";
		}
		?>
		</select>
	</td>
	<td>
		<span class="text-muted"><?php print _("How long a secondary will still treat its copy of the zone data as valid if it can't contact the primary."); ?></span>
	</td>
</tr>
<!-- NXDOMAIN -->
<tr>
	<td><?php print _('NXDOMAIN TTL'); ?></th>
	<td>
		<select name="nxdomain_ttl" class="form-control input-w-auto input-sm" <?php print $readonly; ?>>
		<?php
		// loop
		foreach($PowerDNS->ttl as $k=>$ttl) {
			// active
			if ($k == @$pdns->nxdomain_ttl)	{ $selected = "selected"; }
			else							{ $selected = ""; }
			// print
			print "<option value='$k' $selected>$ttl ($k)</option>";
		}
		?>
		</select>
	</td>
	<td>
		<span class="text-muted"><?php print _("negative caching time - the time a NAME ERROR = NXDOMAIN result may be cached by any resolver"); ?></span>
	</td>
</tr>
<!-- TTL -->
<tr>
	<td><?php print _('Default TTL'); ?></th>
	<td>
		<select name="ttl" class="form-control input-w-auto input-sm" <?php print $readonly; ?>>
		<?php
		// loop
		foreach($PowerDNS->ttl as $k=>$ttl) {
			// active
			if ($k == @$pdns->ttl)	{ $selected = "selected"; }
			else					{ $selected = ""; }
			// print
			print "<option value='$k' $selected>$ttl ($k)</option>";
		}
		?>
		</select>	</td>
	<td>
		<span class="text-muted"><?php print _("Default TTL for domain records"); ?></span>
	</td>
</tr>
<!-- submit -->
<tr>
	<td></td>
	<td style="text-align: right">
		<input type="submit" class="btn btn-success btn-sm" value="<?php print _("Save"); ?>">
	</td>
</tr>

</table>
</form>


<!-- save holder -->
<div class="settingsEdit"></div>