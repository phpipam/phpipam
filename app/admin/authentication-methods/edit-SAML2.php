<?php

/**
 * Set SAML2 method
 *****************/

# verify that user is logged in
$User->check_user_session();

$version = db_json_decode(@file_get_contents(dirname(__FILE__).'/../../../functions/php-saml/src/Saml2/version.json'), true);
$version = @$version['php-saml']['version'];

if ($version < 3.4) {
	$Result->show("danger", _('php-saml library missing, please update submodules'), true, true);
}

# validate action
$Admin->validate_action();

# ID must be numeric */
if($POST->action!="add") {
	if(!is_numeric($POST->id))	{ $Result->show("danger", _("Invalid ID"), true, true); }

	# fetch method settings
	$method_settings = $Admin->fetch_object ("usersAuthMethod", "id", $POST->id);
	$method_settings->params = db_json_decode($method_settings->params);
}
else {
	$method_settings = new StdClass ();
	# set default values
	$method_settings->params = new StdClass ();
	$method_settings->params->clientId = $User->createURL().create_link();
	$method_settings->params->strict = "1";
	$method_settings->params->idpissuer = "";
	$method_settings->params->idplogin = "";
	$method_settings->params->idplogout = "";
	$method_settings->params->idpx509cert = "";
	$method_settings->params->spsignauthn = "1";
	$method_settings->params->spx509cert = "";
	$method_settings->params->spx509key = "";
	$method_settings->params->samluserfield = "";
	$method_settings->params->debugprotocol = 0;
	//$method_settings->params->timeout = 2;
}

# set delete flag
$is_disabled = $POST->action=="delete" ? "disabled" : "";
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
<div class="pHeader"><?php print _('SAML2 connection settings').$User->print_doc_link('Authentication/SAML2.md'); ?></div>

<!-- content -->
<div class="pContent">

	<form id="editAuthMethod" name="editAuthMethod">
	<table class="editAuthMethod table table-noborder table-condensed">

	<!-- description -->
	<tr>
		<td><?php print _('Description'); ?></td>
		<td>
			<input type="text" name="description" class="form-control input-sm" value="<?php print @$method_settings->description; ?>" <?php print $is_disabled; ?> >
		</td>
		<td class="base_dn info2">
			<?php print _('Set name for authentication method'); ?>
		</td>
	</tr>

	<tr>
		<td colspan="3"><hr></td>
	</tr>

	<!-- Advanced Settings -->
	<tr>
		<td><?php print _('Enable JIT'); ?></td>
		<td>
			<input type="checkbox" class="input-switch" value="1" name="jit" <?php if(@$method_settings->params->jit == 1) print 'checked'; ?>  <?php print $is_disabled; ?> >
		</td>
		<td class="info2">
			<?php print _('Provision new users automatically'); ?><br>
		</td>
	</tr>
	<tr>
		<td><?php print _('Use advanced settings'); ?></td>
		<td>
			<input type="checkbox" class="input-switch" value="1" name="advanced" <?php if(@$method_settings->params->advanced == 1) print 'checked'; ?>  <?php print $is_disabled; ?> >
		</td>
		<td class="info2">
			<?php print _('Use Onelogin php-saml settings.php configuration'); ?><br>
		</td>
	</tr>

	<tr>
		<td colspan="3"><hr></td>
	</tr>

	<!-- ClientID -->
	<?php
	// If not set use prior default value for clientId
	if (!isset($method_settings->params->clientId)) $method_settings->params->clientId = $User->createURL();
	?>
	<tr>
		<td><?php print _('Client ID'); ?></td>
		<td>
			<input type="text" name="clientId" class="form-control input-sm" value="<?php print @$method_settings->params->clientId; ?>" <?php print $is_disabled; ?> >
		</td>
		<td class="base_dn info2">
			<?php print _('Enter unique client entity ID'); ?>
		</td>
	</tr>

	<!-- Strict mode -->
	<tr>
		<td><?php print _('Strict mode'); ?></td>
		<td>
			<input type="checkbox" class="input-switch" value="1" name="strict" <?php if(@$method_settings->params->strict == 1) print 'checked'; ?>  <?php print $is_disabled; ?> >
		</td>
		<td class="info2">
			<?php print _('Enable Onelogin php-saml strict mode').'<br>'._('Requires pretty links and mod_rewrite'); ?><br>
		</td>
	</tr>

	<!-- Idp issuer -->
	<tr>
		<td><?php print _('IDP issuer'); ?></td>
		<td>
			<input type="text" name="idpissuer" class="form-control input-sm" value="<?php print @$method_settings->params->idpissuer; ?>" <?php print $is_disabled; ?> >
			<input type="hidden" name="type" value="SAML2">
			<input type="hidden" name="id" value="<?php print @$method_settings->id; ?>">
			<input type="hidden" name="action" value="<?php print escape_input($POST->action); ?>">
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
			<input type="text" name="idplogin" class="form-control input-sm" value="<?php print @$method_settings->params->idplogin; ?>" <?php print $is_disabled; ?> >
		</td>
		<td class="base_dn info2">
			<?php print _('Enter IDP login url'); ?>
		</td>
	</tr>

	<!-- Idp logout -->
	<tr>
		<td><?php print _('IDP logout url'); ?></td>
		<td>
			<input type="text" name="idplogout" class="form-control input-sm" value="<?php print @$method_settings->params->idplogout; ?>" <?php print $is_disabled; ?> >
		</td>
		<td class="base_dn info2">
			<?php print _('Enter IDP logout url'); ?>
		</td>
	</tr>

	<tr>
		<td colspan="3"><hr></td>
	</tr>

	<!-- Idp X.509 public cert -->
	<tr>
 		<td><?php print _('IDP X.509 public cert'); ?></td>
		<td>
			<input type="text" name="idpx509cert" class="form-control input-sm" value="<?php print @$method_settings->params->idpx509cert; ?>" <?php print $is_disabled; ?> >
		</td>
		<td class="base_dn info2">
			<?php print _('Enter IDP X.509 public certificate'); ?>
		</td>
	</tr>

	<!-- Sign Authn request -->
	<tr>
		<td><?php print _('Sign Authn requests'); ?></td>
		<td>
			<input type="checkbox" class="input-switch" value="1" name="spsignauthn" <?php if(filter_var(@$method_settings->params->spsignauthn, FILTER_VALIDATE_BOOLEAN)) print 'checked'; ?>  <?php print $is_disabled; ?> >
		</td>
		<td class="info2">
			<?php print _('Sign Authn requests'); ?><br>
		</td>
	</tr>

	<!-- SP X.509 cert -->
	<tr>
		<td><?php print _('Authn X.509 signing cert'); ?></td>
		<td>
			<input type="text" name="spx509cert" class="form-control input-sm" value="<?php print @$method_settings->params->spx509cert; ?>" <?php print $is_disabled; ?> >
		</td>
		<td class="base_dn info2">
			<?php print _('Enter SP (Client) X.509 certificate'); ?>
		</td>
	</tr>

	<!-- SP X.509 key -->
	<tr>
 		<td><?php print _('Authn X.509 signing cert key'); ?></td>
		<td>
			<input type="text" name="spx509key" class="form-control input-sm" value="<?php print @$method_settings->params->spx509key; ?>" <?php print $is_disabled; ?> >
		</td>
		<td class="base_dn info2">
			<?php print _('Enter SP (Client) X.509 certificate key'); ?>
		</td>
	</tr>

	<tr>
		<td colspan="3"><hr></td>
	</tr>

	<!-- Username attribute -->
	<tr>
		<td><?php print _('SAML username attribute'); ?></td>
		<td>
			<input type="text" class="form-control input-sm" name="UserNameAttr" value="<?php print @$method_settings->params->UserNameAttr; ?>"  <?php print $is_disabled; ?> >
		</td>
		<td class="base_dn info2">
			<?php print _('Extract username from SAML attribute').'<br>'._('blank=use NameId'); ?>
		</td>
	</tr>

	<!-- Map to local users-->
	<tr>
		<td><?php print _('SAML mapped user'); ?></td>
		<td>
		<input type="text" class="form-control input-sm" name="MappedUser" value="<?php print @$method_settings->params->MappedUser; ?>"  <?php print $is_disabled; ?> >
		</td>
		<td class="base_dn info2">
			<?php print _('Map all SAML users to a single local account. e.g. admin').'<br>'._('blank=disabled'); ?>
		</td>
	</tr>

	<tr>
		<td colspan="3"><hr></td>
	</tr>

	<!-- Debug SAML protocol -->
	<tr>
		<td><?php print _('Debugging'); ?></td>
		<td>
			<input type="checkbox" class="input-switch" value="1" name="debugprotocol" <?php if(filter_var(@$method_settings->params->debugprotocol, FILTER_VALIDATE_BOOLEAN)) print 'checked'; ?>  <?php print $is_disabled; ?> >
		</td>
		<td class="info2">
			<?php print _("Enable protocol debugging")." ("._("not for production use").")"; ?><br>
		</td>
	</tr>

	</table>
	</form>

	<?php
	$error = php_feature_missing(["xml","date","zlib","openssl","gettext","dom"]);
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
		<button class='btn btn-sm btn-default submit_popup <?php if($POST->action=="delete") { print "btn-danger"; } else { print "btn-success"; } ?>' data-script="app/admin/authentication-methods/edit-result.php" data-result_div="editAuthMethodResult" data-form='editAuthMethod'>
			<i class="fa <?php if($POST->action=="add") { print "fa-plus"; } elseif ($POST->action=="delete") { print "fa-trash-o"; } else { print "fa-check"; } ?>"></i> <?php print $User->get_post_action(); ?>
		</button>
	</div>

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
