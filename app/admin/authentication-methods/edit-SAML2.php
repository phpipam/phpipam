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
	//$method_settings->params->timeout = 2;
}

# set delete flag
$delete = $_POST['action']=="delete" ? "disabled" : "";
?>

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
			<select name="advanced" class="form-control input-sm input-w-auto" <?php print $delete; ?>>
				<option value="0" <?php if(@$method_settings->params->advanced == 0) { print 'selected'; } ?>><?php print _('false'); ?></option>
				<option value="1" <?php if(@$method_settings->params->advanced == 1) { print 'selected'; } ?>><?php print _('true'); ?></option>
			</select>
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

	</table>
	</form>

	<?php
	# check for support
	if(!in_array("xml", get_loaded_extensions())) {
		$Log->write( "SAML2 login", "php xml extension missing!", 2 );
		$Result->show("danger", _("php XML extension missing!"), false);
	}
	if(!in_array("date", get_loaded_extensions())) {
		$Log->write( "SAML2 login", "php date extension missing!", 2 );
		$Result->show("danger", _("php Date extension missing!"), false);
	}
	if(!in_array("zlib", get_loaded_extensions())) {
		$Log->write( "SAML2 login", "php zlib extension missing!", 2 );
		$Result->show("danger", _("php zlib extension missing!"), false);
	}
	if(!in_array("openssl", get_loaded_extensions())) {
		$Log->write( "SAML2 login", "php openssl extension missing!", 2 );
		$Result->show("danger", _("php openssl extension missing!"), false);
	}
	if(!in_array("mcrypt", get_loaded_extensions())) {
		$Log->write( "SAML2 login", "php mcrypt extension missing!", 2 );
		$Result->show("danger", _("php mcrypt extension missing!"), false);
	}
	if(!in_array("gettext", get_loaded_extensions())) {
		$Log->write( "SAML2 login", "php gettext extension missing!", 2 );
		$Result->show("danger", _("php gettext extension missing!"), false);
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