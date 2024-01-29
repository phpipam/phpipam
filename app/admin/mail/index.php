<?php

/**
 *	Mail settings
 **************************/

# verify that user is logged in
$User->check_user_session();

# fetch mail settings
$mail_settings = $Admin->fetch_object("settingsMail", "id", 1);

# create csrf token
$csrf = $User->Crypto->csrf_cookie ("create", "mail");
?>

<!-- title -->
<h4>phpIPAM <?php print _('Mail settings'); ?></h4>
<hr>
<br>

<div class="panel panel-default" style="width:auto;position:absolute;border: 1px solid rgba(255, 255, 255, 0.1) !important;padding-bottom:0px !important">

<form name="mailsettings" id="mailsettings">
<table id="mailsettingstbl" class="table table-condensed table-top table-auto" style="margin-bottom:0px;">

	<!-- Server settings -->
	<tr class="settings-title">
		<th colspan="3"><h4><?php print _('Mail server type'); ?></h4></th>
	</tr>

	<!-- Server type -->
	<tr>
		<td><?php print _('Server type'); ?></th>
		<td>
			<select name="mtype" class="form-control input-sm input-w-auto" id="mtype">
				<option value="localhost"><?php print _("Localhost"); ?></option>
				<option value="smtp" <?php if($mail_settings->mtype=="smtp") print "selected='selected'"; ?>><?php print _("SMTP"); ?></option>
			</select>
			<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
		</td>
		<td class="info2"><?php print _('Select server type for sending mail messages'); ?></td>
	</tr>

	<!-- smtp -->
	<tbody id="smtp" <?php if($mail_settings->mtype=="localhost") print "style='display:none;'"; ?>>

	<!-- Server settings -->
	<tr class="settings-title">
		<th colspan="3"><h4><?php print _('SMTP settings'); ?></h4></th>
	</tr>
	<!-- Server -->
	<tr>
		<td><?php print _('Server address'); ?></th>
		<td>
			<input type="text" name="mserver" class='smtp form-control input-sm' value="<?php print $mail_settings->mserver; ?>">
		</td>
		<td class="info2"><?php print _('Set SMTP server address'); ?></td>
	</tr>

	<!-- Port -->
	<tr>
		<td><?php print _('Port'); ?></th>
		<td>
			<input type="text" name="mport" class='smtp form-control input-sm' value="<?php print $mail_settings->mport; ?>">
		</td>
		<td class="info2"><?php print _('Set SMTP server port'); ?> (25, 465 or 587)</td>
	</tr>

	<!-- tls -->
	<tr>
		<td><?php print _('Security'); ?></th>
		<td>
			<select name="msecure" class="smtp form-control input-sm input-w-auto">
				<option value="none"><?php print _('None'); ?></option>
				<option value="ssl" <?php if($mail_settings->msecure=="ssl") print "selected='selected'"; ?>><?php print _('SSL'); ?></option>
				<option value="tls" <?php if($mail_settings->msecure=="tls") print "selected='selected'"; ?>><?php print _('TLS'); ?></option>
			</select>
		</td>
		<td class="info2"><?php print _('Select cryptographic security protocol'); ?></td>
	</tr>

	<!-- Server auth -->
	<tr>
		<td><?php print _('Server authentication'); ?></th>
		<td>
			<select name="mauth" class="smtp form-control input-sm input-w-auto">
				<option value="no"><?php print _('No'); ?></option>
				<option value="yes" <?php if($mail_settings->mauth=="yes") print "selected='selected'"; ?>><?php print _('Yes'); ?></option>
			</select>
		</td>
		<td class="info2"><?php print _('Select yes if authentication is required'); ?></td>
	</tr>

	<!-- Username -->
	<tr>
		<td><?php print _('Username'); ?></th>
		<td>
			<input type="text" name="muser" maxlength="254" class='smtp form-control input-sm' value="<?php print $mail_settings->muser; ?>">
		</td>
		<td class="info2"><?php print _('Set username for SMTP authentication'); ?></td>
	</tr>

	<!-- Password -->
	<tr>
		<td><?php print _('Password'); ?></th>
		<td>
			<input type="password" name="mpass" maxlength="128" class='smtp form-control input-sm' value="<?php print $mail_settings->mpass; ?>">
		</td>
		<td class="info2"><?php print _('Set password for SMTP authentication'); ?></td>
	</tr>

	</tbody>



	<!-- Sender settings -->
	<tr class="settings-title">
		<th colspan="3"><h4><?php print _('Mail sender settings'); ?></h4></th>
	</tr>

	<!-- Admin name -->
	<tr>
		<td class="title"><?php print _('Sender name'); ?></td>
		<td>
			<input type="text" size="50" class="form-control input-sm" name="mAdminName" value="<?php print $mail_settings->mAdminName; ?>">
		</td>
		<td class="info2">
			<?php print _('Set administrator name to display when sending mails and for contact info'); ?>
		</td>
	</tr>

	<!-- Admin mail -->
	<tr>
		<td class="title"><?php print _('Admin mail'); ?></td>
		<td>
			<input type="text" size="50" class="form-control input-sm" name="mAdminMail" value="<?php print $mail_settings->mAdminMail; ?>">
		</td>
		<td class="info2">
			<?php print _('Set administrator e-mail to display when sending mails and for contact info'); ?>
		</td>
	</tr>


	<!-- test -->
	<tr class="th">
		<td class="title"></td>
		<td class="submit" style="padding-top:30px;">
		<div class="btn-group pull-right">
			<a class='btn btn-sm btn-default submit_popup' data-script="app/admin/mail/test-mail.php" data-result_div="settingsMailEdit" data-form='mailsettings' data-noreload='true'><i class="icon icon-gray icon-envelope"></i> <?php print _('Send test email'); ?></a>
			<input type="submit" class="btn btn-default btn-success btn-sm submit_popup" data-script="app/admin/mail/edit.php" data-result_div="settingsMailEdit" data-form='mailsettings' value="<?php print _("Save"); ?>">
		</div>
		</td>
		<td></td>
	</tr>

</table>
</form>
</div>

<!-- Result -->
<div id="settingsMailEdit"></div>
