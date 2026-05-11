<?php

/**
 * Set http method
 *****************/

# verify that user is logged in
$User->check_user_session();

# validate action
$Admin->validate_action();

# ID must be numeric */
if($POST->action!="add") {
	if(!is_numeric($POST->id))	{ $Result->show("danger", _("Invalid ID"), true, true); }

	# feth method settings
	$method_settings = $Admin->fetch_object ("usersAuthMethod", "id", $POST->id);
	$method_settings->params = db_json_decode($method_settings->params);
}
else {
	$method_settings = new StdClass ();
	# set default values
	$method_settings->params = new StdClass ();
	$method_settings->params->server_var = "PHP_AUTH_USER";
}

# set delete flag
$delete = $POST->action=="delete" ? "disabled" : "";
?>

<!-- header -->
<div class="pHeader"><?php print _('http settings'); ?></div>

<!-- content -->
<div class="pContent">

	<form id="editAuthMethod" name="editAuthMethod">
	<table class="editAuthMethod table table-noborder table-condensed">

	<!-- description -->
	<tr>
		<td><?php print _('Description'); ?></td>
		<td>
			<input type="text" name="description" class="form-control input-sm" value="<?php print @$method_settings->description; ?>" <?php print $delete; ?>>
			<input type="hidden" name="type" value="http">
			<input type="hidden" name="id" value="<?php print @$method_settings->id; ?>">
			<input type="hidden" name="action" value="<?php print escape_input($POST->action); ?>">
			<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
		</td>
		<td class="info2">
			<?php print _('Set name for authentication method'); ?>
		</td>
	</tr>

	<tr>
		<td colspan="3"><hr></td>
	</tr>

	<!-- server_var -->
	<tr>
		<td><?php print _('Server variable'); ?></td>
		<td>
			<select name="server_var" class="form-control input-sm input-w-auto">
			<?php
			$values = ["PHP_AUTH_USER", "AUTH_USER", 'LOGON_USER', "REMOTE_USER"];
			foreach($values as $v) {
				if($v==@$method_settings->params->server_var)	{ print "<option value='$v' selected=selected>$v</option>"; }
				else											{ print "<option value='$v'					 >$v</option>"; }
			}
			?>
			</select>
		</td>
		<td class="info2">
			<?php print _('Select $_SERVER variable to check'); ?>
		</td>
	</tr>

	<!-- prefix -->
	<tr>
		<td><?php print _('Prefix (Domain\)'); ?></td>
		<td>
			<input type="text" name="prefix" class="form-control input-sm" value="<?php print @$method_settings->params->prefix; ?>" <?php print $delete; ?>>
		</td>
		<td class="info2">
			<?php print _('Server variable must begin with prefix and it will be removed)'); ?>
		</td>
	</tr>

	</table>
	</form>
</div>


<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<button class='btn btn-sm btn-default submit_popup <?php if($POST->action=="delete") { print "btn-danger"; } else { print "btn-success"; } ?>' data-script="app/admin/authentication-methods/edit-result.php" data-result_div="editAuthMethodResult" data-form='editAuthMethod'>
			<i class="fa <?php if($POST->action=="add") { print "fa-plus"; } elseif ($POST->action=="delete") { print "fa-trash-o"; } else { print "fa-check"; } ?>"></i> <?php print $User->get_post_action(); ?>
		</button>	</div>

	<?php
	# check for matching users
	if($POST->action=="delete") {
		$users = $Admin->fetch_multiple_objects ("users", "authMethod", @$method_settings->id);
		if($users!==false) {
			$Result->show("warning", sizeof($users)._(" users have this method for logging in. They will be reset to local auth!"), false);
		}
	}
	?>

	<!-- Result -->
	<div id="editAuthMethodResult"></div>
</div>
