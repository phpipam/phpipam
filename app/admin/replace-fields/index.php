<?php

/**
 *	Script to replace fields in IP address list
 ***********************************************/

# verify that user is logged in
$User->check_user_session();

# create csrf token
$csrf = $User->create_csrf_cookie ();
?>

<h4><?php print _('Search and replace fields in IP address list'); ?></h4>
<hr><br>

<form id="searchReplace">
<table class="table" style="width:auto">

	<tr>
		<td><?php print _('Select field to replace'); ?>:</td>
		<td>
		<select name="field" class="form-control input-sm input-w-auto">
			<option value="description"><?php print _('Description'); ?></option>
			<option value="dns_name"><?php print _('Hostname'); ?></option>
			<option value="owner"><?php print _('Owner'); ?></option>
			<option value="mac"><?php print _('MAC address'); ?></option>
			<option value="switch"><?php print _('Device'); ?></option>
			<option value="port"><?php print _('Port'); ?></option>
			<?php
			# fetch custom fields
			$custom = $Tools->fetch_custom_fields('ipaddresses');

			if(sizeof($custom) > 0) {
				foreach($custom as $myField) {
					print '<option value="'. $myField['name'] .'"> '. $myField['name'] .'</option>';
				}
			}
			?>
		</select>
		</td>
	</tr>

	<tr>
		<td><?php print _('Select search string'); ?></td>
		<td>
			<input type="text" name="search" class="form-control input-sm" placeholder="<?php print _('search string'); ?>">
			<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
		</td>
	</tr>

	<tr>
		<td><?php print _('Select replace string'); ?></td>
		<td>
			<input type="text" name="replace" class="form-control input-sm" placeholder="<?php print _('replace string'); ?>">
		</td>
	</tr>

	<tr class="th">
		<td></td>
		<td>
			<button class="btn btn-sm btn-default" id="searchReplaceSave"><i class="fa fa-check"></i> <?php print _('Replace'); ?></button>
		</td>
	</tr>

</table>
</form>


<!-- result -->
<div class="searchReplaceResult"></div>