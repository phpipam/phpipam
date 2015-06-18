<?php

/**
 * Set LDAP method
 *****************/

# verify that user is logged in
$User->check_user_session();

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
   @$method_settings->params->domain_controllers = "dc1.domain.local;dc2.domain.local";
	$method_settings->params->base_dn = "CN=Users,CN=Company,DC=domain,DC=local";
	$method_settings->params->account_suffix = "@domain.local";
	$method_settings->params->ad_port = 389;
}

# set delete flag
$delete = $_POST['action']=="delete" ? "disabled" : "";
?>

<!-- header -->
<div class="pHeader"><?php print _('OpenLDAP connection settings'); ?></div>

<!-- content -->
<div class="pContent">

	<?php
	# make sure LDAP is supported !
	if (!in_array("ldap", get_loaded_extensions())) 	{ $Result->show("danger", _("ldap extension not enabled in php")."!<hr>", false); }
	?>

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

	<!-- DC -->
	<tr>
		<td style="width:130px;"><?php print _('OpenLDAP servers'); ?></td>
		<td style="width:250px;">
			<input type="text" name="domain_controllers" class="form-control input-sm" value="<?php print @$method_settings->params->domain_controllers; ?>" <?php print $delete; ?>>
			<input type="hidden" name="type" value="AD">
			<input type="hidden" name="id" value="<?php print @$method_settings->id; ?>">
			<input type="hidden" name="action" value="<?php print @$_POST['action']; ?>">
		</td>
		<td class="info2"><?php print _('Enter domain controllers, separated by ;'); ?>
		</td>
	</tr>

	<!-- BasedN -->
	<tr>
		<td><?php print _('Base DN'); ?></td>
		<td>
			<input type="text" name="base_dn" class="form-control input-sm" value="<?php print @$method_settings->params->base_dn; ?>" <?php print $delete; ?>>
		</td>
		<td class="base_dn info2">
			<?php print _('Enter base DN for LDAP'); ?>
		</td>
	</tr>

	<!-- SSL -->
	<tr>
		<td><?php print _('Use SSL'); ?></td>
		<td>
			<select name="use_ssl" class="form-control input-sm input-w-auto" <?php print $delete; ?>>
				<option value="0" <?php if(@$method_settings->params->use_ssl == 0) { print 'selected'; } ?>><?php print _('false'); ?></option>
				<option value="1" <?php if(@$method_settings->params->use_ssl == 1) { print 'selected'; } ?>><?php print _('true'); ?></option>
			</select>
		</td>
		<td class="info2">
			<?php print _('Use SSL (LDAPS), your server needs to be setup (default: false)'); ?><br>
		</td>
	</tr>

	<!-- TLS -->
	<tr>
		<td><?php print _('Use TLS'); ?></td>
		<td>
			<select name="use_tls" class="form-control input-sm input-w-auto" <?php print $delete; ?>>
				<option value="0" <?php if(@$method_settings->params->use_tls == 0) { print 'selected'; } ?>><?php print _('false'); ?></option>
				<option value="1" <?php if(@$method_settings->params->use_tls == 1) { print 'selected'; } ?>><?php print _('true'); ?></option>
			</select>
		</td>
		<td class="info2">
			<?php print _('If you wish to use TLS you should ensure that useSSL is set to false and vice-versa (default: false)'); ?>
		</td>
	</tr>

	<!-- AD port -->
	<tr>
		<td><?php print _('AD port'); ?></td>
		<td>
			<input type="text" name="ad_port" class="form-control input-sm input-w-100" value="<?php print @$method_settings->params->ad_port; ?>" <?php print $delete; ?>>
		</td>
		<td class="port info2">
			<?php print _('The default port for LDAP non-SSL connections'); ?>
		</td>
	</tr>

	</table>
	</form>
</div>


<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<button class="btn btn-sm btn-default <?php if($_POST['action']=="delete") { print "btn-danger"; } else { print "btn-success"; } ?>" id="editAuthMethodSubmit"><i class="fa <?php if($_POST['action']=="add") { print "fa-plus"; } else if ($_POST['action']=="delete") { print "fa-trash-o"; } else { print "fa-check"; } ?>"></i> <?php print ucwords(_($_POST['action'])); ?></button>
	</div>

	<?php
	if($_POST['action']=="delete") {
		# check for mathing users
		$users = $Admin->fetch_multiple_objects ("users", "authMethod", @$method_settings->id);
		if($users!==false) {
			$Result->show("warning", sizeof($users)._(" users have this method for logging in. They will be reset to local auth!"), false);
		}
	}
	?>

	<!-- Result -->
	<div class="editAuthMethodResult"></div>
</div>