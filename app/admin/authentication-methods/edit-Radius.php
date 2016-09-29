<?php

/**
 * Set Radius method
 *****************/

# verify that user is logged in
$User->check_user_session();

# validate action
$Admin->validate_action ($_POST['action'], true);

# ID must be numeric */
if($_POST['action']!="add") {
	if(!is_numeric($_POST['id']))	{ $Result->show("danger", _("Invalid ID"), true, true); }

	# feth method settings
	$method_settings = $Admin->fetch_object ("usersAuthMethod", "id", $_POST['id']);
	$method_settings->params = json_decode($method_settings->params);
}
else {
	$method_settings = new StdClass ();
	# set default values
   @$method_settings->params->hostname = "localhost";
	$method_settings->params->port = 1812;
	$method_settings->params->timeout = 2;

}

# set delete flag
$delete = $_POST['action']=="delete" ? "disabled" : "";
?>

<!-- header -->
<div class="pHeader"><?php print _('Radius connection settings'); ?></div>

<!-- content -->
<div class="pContent">

	<form id="editAuthMethod" name="editAuthMethod">
	<table class="editAuthMethod table table-noborder table-condensed">

	<!-- description -->
	<tr>
		<td><?php print _('Description'); ?></td>
		<td>
			<input type="text" name="description" class="form-control input-sm" value="<?php print @$method_settings->description; ?>" <?php print $delete; ?>>
		</td>
		<td class="base_dn info2">
			<?php print _('Set name for authentication method'); ?>
		</td>
	</tr>

	<tr>
		<td colspan="3"><hr></td>
	</tr>

	<!-- Server -->
	<tr>
		<td style="width:130px;"><?php print _('Radius server'); ?></td>
		<td style="width:250px;">
			<input type="text" name="hostname" class="form-control input-sm" value="<?php print @$method_settings->params->hostname; ?>" <?php print $delete; ?>>
			<input type="hidden" name="type" value="Radius">
			<input type="hidden" name="id" value="<?php print @$method_settings->id; ?>">
			<input type="hidden" name="action" value="<?php print @$_POST['action']; ?>">
			<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
		</td>
		<td class="info2"><?php print _('Enter Radius server'); ?>
		</td>
	</tr>

	<!-- secret -->
	<tr>
		<td><?php print _('Secret'); ?></td>
		<td>
			<input type="password" name="secret" class="form-control input-sm" value="<?php print @$method_settings->params->secret; ?>" <?php print $delete; ?>>
		</td>
		<td class="base_dn info2">
			<?php print _('Enter radius secret'); ?>
		</td>
	</tr>

	<!-- port -->
	<tr>
		<td><?php print _('Port'); ?></td>
		<td>
			<input type="text" name="port" class="form-control input-sm" value="<?php print @$method_settings->params->port; ?>" <?php print $delete; ?>>
		</td>
		<td class="base_dn info2">
			<?php print _('Enter radius port (default 1812)'); ?>
		</td>
	</tr>

	<!-- port -->
	<tr>
		<td><?php print _('Suffix'); ?></td>
		<td>
			<input type="text" name="suffix" class="form-control input-sm" value="<?php print @$method_settings->params->suffix; ?>" <?php print $delete; ?>>
		</td>
		<td class="base_dn info2">
			<?php print _('Enter suffix'); ?>
		</td>
	</tr>

	<!-- timeout -->
	<tr>
		<td><?php print _('Timeout'); ?></td>
		<td>
			<select name="timeout" class="form-control input-w-auto">
			<?php
			$values = array(1,2,3,5,10);
			foreach($values as $v) {
				if($v==@$method_settings->params->timeout)	{ print "<option value='$v' selected=selected>$v</option>"; }
				else										{ print "<option value='$v'					 >$v</option>"; }
			}
			?>
			</select>
		</td>
		<td class="base_dn info2">
			<?php print _('Set timeout in seconds'); ?>
		</td>
	</tr>

	</table>
	</form>

	<?php
	# check for socket support !
	if(!in_array("sockets", get_loaded_extensions())) {
		$Log->write( "Radius login", "php Socket extension missing!", 2 );
		$Result->show("danger", _("php Socket extension missing!"), false);
	}
	?>
</div>


<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<button class="btn btn-sm btn-default <?php if($_POST['action']=="delete") { print "btn-danger"; } else { print "btn-success"; } ?>" id="editAuthMethodSubmit"><i class="fa <?php if($_POST['action']=="add") { print "fa-plus"; } else if ($_POST['action']=="delete") { print "fa-trash-o"; } else { print "fa-check"; } ?>"></i> <?php print ucwords(_($_POST['action'])); ?></button>
	</div>

	<?php
	# check for mathing users
	if($_POST['action']=="delete") {
		$users = $Admin->fetch_multiple_objects ("users", "authMethod", @$method_settings->id);
		if($users!==false) {
			$Result->show("warning", sizeof($users)._(" users have this method for logging in. They will be reset to local auth!"), false);
		}
	}
	?>

	<!-- Result -->
	<div class="editAuthMethodResult"></div>
</div>