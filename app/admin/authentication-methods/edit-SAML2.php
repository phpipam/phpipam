<?php

/**
 * Set SAML2 method
 *****************/

# verify that user is logged in
$User->check_user_session();

# validate action
$Admin->validate_action ($_POST['action'], true);

# ID must be numeric */
if($_POST['action']!="add") {
	if(!is_numeric($_POST['id']))	{ $Result->show("danger", _("Invalid ID"), true, true); }

	# fetch method settings
	$method_settings = $Admin->fetch_object ("usersAuthMethod", "id", $_POST['id']);
	$method_settings->params = json_decode($method_settings->params);
}
else {
	$method_settings = new StdClass ();
	$method_settings->params = new StdClass ();
	# set default values
	$method_settings->params->idpissuer = "";
	$method_settings->params->idplogin = "";
	$method_settings->params->idplogout = "";
	$method_settings->params->idpcertfingerprint = "";
	$method_settings->params->idpcertalgorithm = "sha1";
	$method_settings->params->idpx509cert = "";
	//$method_settings->params->timeout = 2;
}

# set delete flag
$delete = $_POST['action']=="delete" ? "disabled" : "";
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
<!-- header -->
<div class="pHeader"><?php print _('SAML2 connection settings'); ?></div>

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

<!-- SSL -->
	<tr>
		<td><?php print _('Use advanced settings'); ?></td>
		<td>
			<input type="checkbox" class="input-switch" value="1" name="advanced" <?php if(@$method_settings->params->advanced == 1) print 'checked'; ?> >
		</td>
		<td class="info2">
			<?php print _('Use Onelogin php-saml settings.php configuration'); ?><br>
		</td>
	</tr>
	<tr>
		<td colspan="3"><hr></td>
	</tr>

	<!-- Idp issuer -->
	<tr>
		<td><?php print _('IDP issuer'); ?></td>
		<td>
			<input type="text" name="idpissuer" class="form-control input-sm" value="<?php print @$method_settings->params->idpissuer; ?>" <?php print $delete; ?>>
			<input type="hidden" name="type" value="SAML2">
			<input type="hidden" name="id" value="<?php print @$method_settings->id; ?>">
			<input type="hidden" name="action" value="<?php print @$_POST['action']; ?>">
			<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
		</td>
		<td class="base_dn info2">
			<?php print _('Enter idp issuer'); ?>
		</td>
	</tr>

	<!-- Idp login -->
	<tr>
		<td><?php print _('IDP login url'); ?></td>
		<td>
			<input type="text" name="idplogin" class="form-control input-sm" value="<?php print @$method_settings->params->idplogin; ?>" <?php print $delete; ?>>
		</td>
		<td class="base_dn info2">
			<?php print _('Enter IDP login url'); ?>
		</td>
	</tr>
	<!-- Idp logout -->
	<tr>
		<td><?php print _('IDP logout url'); ?></td>
		<td>
			<input type="text" name="idplogout" class="form-control input-sm" value="<?php print @$method_settings->params->idplogout; ?>" <?php print $delete; ?>>
		</td>
		<td class="base_dn info2">
			<?php print _('Enter IDP logout url'); ?>
		</td>
	</tr>
	<tr>
		<td colspan="3"><hr></td>
	</tr>
	<!-- Idp cert fingerprint -->
	<tr>
		<td><?php print _('IDP cert fingerprint'); ?></td>
		<td>
			<input type="text" name="idpcertfingerprint" class="form-control input-sm" value="<?php print @$method_settings->params->idpcertfingerprint; ?>" <?php print $delete; ?>>
		</td>
		<td class="base_dn info2">
			<?php print _('Enter IDP X509 certificate fingerprint'); ?>
		</td>
	</tr>
	<!-- Idp cert algorithm -->
	<tr>
		<td><?php print _('IDP cert algorithm'); ?></td>
		<td>
			<select name="idpcertalgorithm" class="form-control input-w-auto">
			<?php
			$values = array("sha1","sha256", "sha384", "sha512");
			foreach($values as $v) {
				if($v==@$method_settings->params->idpcertalgorithm)	{ print "<option value='$v' selected=selected>$v</option>"; }
				else										{ print "<option value='$v'					 >$v</option>"; }
			}
			?>
			</select>
		</td>
		<td class="base_dn info2">
			<?php print _('Enter IDP X509 certificate algorithm'); ?>
		</td>
	</tr>
	<!-- Idp cert x509 -->
	<tr>
 		<td><?php print _('IDP X509 certificate'); ?></td>
		<td>
			<input type="text" name="idpx509cert" class="form-control input-sm" value="<?php print @$method_settings->params->idpx509cert; ?>" <?php print $delete; ?>>
		</td>
		<td class="base_dn info2">
			<?php print _('Enter IDP X509 certificate'); ?>
		</td>
	</tr>
	<tr>
		<td colspan="3"><hr></td>
	</tr>
	<!-- Username attribute -->
	<tr>
		<td><?php print _('SAML username attribute'); ?></td>
		<td>
			<input type="text" class="form-control input-sm" name="UserNameAttr" value="<?php print @$method_settings->params->UserNameAttr; ?>">
		</td>
		<td class="base_dn info2">
			<?php print _('Extract username from SAML attribute').'<br>'._('blank=use NameId'); ?>
		</td>
	</tr>
	<!-- Map to local users-->
	<tr>
		<td><?php print _('SAML mapped user'); ?></td>
		<td>
		<input type="text" class="form-control input-sm" name="MappedUser" value="<?php print @$method_settings->params->MappedUser; ?>">
		</td>
		<td class="base_dn info2">
			<?php print _('Map all SAML users to a single local account. e.g. admin').'<br>'._('blank=disabled'); ?>
		</td>
	</tr>

	</table>
	</form>

	<?php
	$error = php_feature_missing(["xml","date","zlib","openssl","gettext","dom","mcrypt"]);
	if (is_string($error)) {
		$Log->write("SAML2 login", $error, 2);
		$Result->show("danger", $error, false);
	}
	?>
</div>


<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<button class='btn btn-sm btn-default submit_popup <?php if($_POST['action']=="delete") { print "btn-danger"; } else { print "btn-success"; } ?>' data-script="app/admin/authentication-methods/edit-result.php" data-result_div="editAuthMethodResult" data-form='editAuthMethod'>
			<i class="fa <?php if($_POST['action']=="add") { print "fa-plus"; } else if ($_POST['action']=="delete") { print "fa-trash-o"; } else { print "fa-check"; } ?>"></i> <?php print ucwords(_($_POST['action'])); ?>
		</button>
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
	<div id="editAuthMethodResult"></div>
</div>
