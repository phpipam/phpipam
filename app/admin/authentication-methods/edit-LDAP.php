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
   @$method_settings->params->domain_controllers = "ldap1.domain.local;ldap2.domain.local";
	$method_settings->params->base_dn = "CN=Users,CN=Company,DC=domain,DC=local";
	$method_settings->params->account_suffix = "";
	$method_settings->params->ad_port = 389;
}

# set delete flag
$delete = $_POST['action']=="delete" ? "disabled" : "";
?>

<!-- header -->
<div class="pHeader"><?php print _('LDAP connection settings'); ?></div>

<script>
	$( "select#ldap_security" ).change(function () {
		if ($(this).val() === "ssl" ) {
			$('input#ad_port').val("636")
		} else {
			$('input#ad_port').val("389")
		}
	});
</script>

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
		<td style="width:130px;"><?php print _('LDAP servers'); ?></td>
		<td style="width:250px;">
			<input type="text" name="domain_controllers" class="form-control input-sm" value="<?php print @$method_settings->params->domain_controllers; ?>" <?php print $delete; ?>>
			<input type="hidden" name="type" value="LDAP">
			<input type="hidden" name="id" value="<?php print @$method_settings->id; ?>">
			<input type="hidden" name="action" value="<?php print @$_POST['action']; ?>">
			<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
		</td>
		<td class="info2"><?php print _('LDAP hosts, separated by a semicolon (;)'); ?>
		</td>
	</tr>

	<!-- BaseDN -->
	<tr>
		<td><?php print _('Base DN'); ?></td>
		<td>
			<input type="text" name="base_dn" class="form-control input-sm" value="<?php print @$method_settings->params->base_dn; ?>" <?php print $delete; ?>>
		</td>
		<td class="base_dn info2">
			<?php print _('Base DN for your directory'); ?>
		</td>
	</tr>

	<!-- UsersDN -->
	<tr>
		<td><?php print _('Users DN'); ?></td>
		<td>
			<input type="text" name="users_base_dn" class="form-control input-sm" value="<?php print @$method_settings->params->users_base_dn; ?>" <?php print $delete; ?>>
		</td>
		<td class="users_base_dn info2">
			<?php print _('Base DN for your users, if different from the base DN above.'); ?>
		</td>
	</tr>

	<!-- UIDAttr -->
	<tr>
		<td><?php print _('UID Attribute'); ?></td>
		<td>
			<input type="text" name="uid_attr" class="form-control input-sm" value="<?php print @$method_settings->params->uid_attr; ?>" <?php print $delete; ?>>
		</td>
		<td class="uid_attr_dn info2">
			<?php print _('LDAP uid naming attribute for users, e.g. "uid" or "cn"'); ?>
		</td>
	</tr>

	<!-- TLS -->
	<tr>
		<td><?php print _('LDAP Security Type'); ?></td>
		<td>
			<select id='ldap_security' name="ldap_security" class="form-control input-sm input-w-auto" <?php print $delete; ?>>
				<option name="ldap_security" class="form-control input-sm input-w-auto" value="tls" <?php if(@$method_settings->params->ldap_security == 'tls') { print 'selected'; } ?>>TLS</option>
				<option name="ldap_security" class="form-control input-sm input-w-auto" value="ssl" <?php if(@$method_settings->params->ldap_security == 'ssl') { print 'selected'; } ?>>SSL</option>
				<option name="ldap_security" class="form-control input-sm input-w-auto" value="none" <?php if(@$method_settings->params->ldap_security == 'none') { print 'selected'; } ?>>None</option>
			</select>
		</td>
		<td class="info2">
			<?php print _('SSL, TLS, or None (default: TLS)'); ?>
		</td>
	</tr>

	<!-- LDAP port -->
	<tr>
		<td><?php print _('LDAP port'); ?></td>
		<td>
			<input type="text" id="ad_port" name="ad_port" class="form-control input-sm input-w-100" value="<?php print @$method_settings->params->ad_port; ?>" <?php print $delete; ?>>
		</td>
		<td class="port info2">
			<?php print _('Listening port for your LDAP service. TLS/unencrypted Default: 389 <br /> SSL Default: 636'); ?>
		</td>
	</tr>

	<tr>
		<td colspan="3"><hr></td>
	</tr>
	<!-- Username -->
	<tr>
		<td><?php print _('Bind user'); ?></td>
		<td>
			<input type="text" name="adminUsername" class="form-control input-sm" style="margin-bottom:5px;" placeholder="<?php print _('Username'); ?>" value="<?php print @$method_settings->params->adminUsername; ?>" <?php print $delete; ?>>
		</td>
		<td class="info2">
			<?php print _('User DN to bind as for search operations (optional). '); ?>
		</td>
	</tr>

	<!-- Username -->
	<tr>
		<td><?php print _('Bind password'); ?></td>
		<td>
			<input type="password" name="adminPassword" class="form-control input-sm" placeholder="<?php print _('Password'); ?>" value="<?php print @$method_settings->params->adminPassword; ?>" <?php print $delete; ?>>
		</td>
		<td class="info2">
			<?php print _('Password for the bind account (only required if bind user is set).'); ?>
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
