<?php

/**
 * 2FA settings
 *************/

# verify that user is logged in
$User->check_user_session();

# create csrf token
$csrf = $User->Crypto->csrf_cookie ("create", "2fa");
?>




<!-- title -->
<h4><?php print _('2FA authentication'); ?></h4>
<hr><br>

<article>
<form name="2fa" id="2fa">
<table id="2fa" class="table table-hover table-condensed table-noborder table-auto table-top">

<!-- Status-->
<tr>
	<td><?php print _('2FA provider'); ?></td>
	<td>
		<select name="2fa_provider" class="form-control input-sm input-w-auto">
			<option value="none"><?php print _("Disabled"); ?></option>
			<option value="Google_Authenticator" <?php if($User->settings->{'2fa_provider'}=="Google_Authenticator") { print "selected"; } ?>><?php print _("Google Authenticator"); ?></option>
		</select>
		<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
	</td>
	<td class="info2"><?php print _('Current 2FA provider'); ?></td>
</tr>

<!-- Name-->
<tr>
	<td><?php print _('2FA name'); ?></td>
	<td>
		<input type="text" class="form-control input-sm" name="2fa_name" value="<?php print @$User->settings->{'2fa_name'}; ?>">
	</td>
	<td class="info2"><?php print _('Name for 2fa application that will be displayed'); ?></td>
</tr>

<!-- Length-->
<tr>
	<td><?php print _('2FA length'); ?></td>
	<td>
		<input type="text" class="form-control input-sm" name="2fa_length" value="<?php print @$User->settings->{'2fa_length'}; ?>">
	</td>
	<td class="info2"><?php print _('Length of 2FA secret (26 to 32)'); ?></td>
</tr>

<!-- Length-->
<tr>
	<td><?php print _('2FA user change'); ?></td>
	<td>
		<input type="checkbox" class="input-switch" value="1" name="2fa_userchange" <?php if(@$User->settings->{'2fa_userchange'} == 1) print 'checked'; ?>>
	</td>
	<td class="info2"><?php print _('Can users change 2fa settings for their account'); ?></td>
</tr>

<!-- Force all users -->
<tr>
	<td><?php print _('Apply to all users'); ?></td>
	<td>
		<input type="checkbox" class="input-sm" name="2fa_force" value="On">
	</td>
	<td class="info2"><?php print _('Force all users to use 2fa on next login or disable 2fa for all users'); ?>.</td>
</tr>

<!-- Submit -->
<tr class="th">
	<td class="title"></td>
	<td class="submit">
		<input type="submit" class="btn btn-default btn-success btn-sm submit_popup" data-script="app/admin/2fa/save.php" data-result_div="2faEdit" data-form='2fa' value="<?php print _("Save"); ?>">
	</td>
	<td></td>
</tr>

</table>
</form>
</article>

<div id="2faEdit"></div>




<h4 style='margin-top:50px;'><?php print _('2FA users'); ?></h4>
<hr><br>
<?php
if (($User->settings->{'2fa_provider'}!="none")) {
	$twofa_users = $Admin->fetch_all_objects ("users", "real_name");
}
else {
	$twofa_users = false;
}

if (($User->settings->{'2fa_provider'}=="none")) {
	$Result->show ('info', _("2fa is disabled"), false);
}
elseif ($twofa_users == false) {
	$Result->show ('info', _("No users have 2fa enabled"), false);
}
else {
	$html   = [];
	$html[] = "<table class='table table-condensed table-top table-auto1 sorted' data-cookie-id-table='admin_2fa'>";
	$html[] = "<thead>";
	$html[] = "	<tr>";
	$html[] = "		<th>"._("Real name")."</th>";
	$html[] = "		<th>"._("Username")."</th>";
	$html[] = "		<th>"._("2fa status")."</th>";
	$html[] = "		<th></th>";
	$html[] = "	</tr>";
	$html[] = "</thead>";
	// users
	$html[] = "<tbody>";
	foreach ($twofa_users as $u) {

		$btn1_class = "open_popup";
		$btn2_class = "open_popup";
		$btn3_class = "open_popup";

		// status
		$status = "";
		if ($u->{'2fa'}==0)						{ $status = "<span class='badge badge1 severity2'>Disabled</span>";					$btn2_class = "disabled"; $btn3_class = "disabled"; }
		elseif (is_blank($u->{'2fa_secret'}))	{ $status = "<span class='badge badge1 severity1'>Enabled, not activated</span>";	$btn1_class = "disabled"; $btn2_class = "disabled"; }
		else 									{ $status = "<span class='badge badge1 severity0'>Enabled</span>";					$btn1_class = "disabled"; }

		$html[] = "<tr>";
		$html[] = "		<td><a class='btn btn-xs btn-default'>$u->real_name</a></td>";
		$html[] = "		<td>$u->username</td>";
		$html[] = "		<td>$status</td>";
		$html[] = "		<td class='actions'>";
		$html[] = "		<div class='btn-group'>";
		$html[] = "			<button class='btn btn-xs btn-default $btn1_class' data-script='app/admin/2fa/edit_user.php' data-class='700' data-action='activate'   data-id='$u->id' rel='tooltip' title='"._('Enable')."'><i class='fa fa-check'></i></button>";
		$html[] = "			<button class='btn btn-xs btn-default $btn2_class' data-script='app/admin/2fa/edit_user.php' data-class='700' data-action='remove_secret' data-id='$u->id' rel='tooltip' title='"._('Reset secret')."'><i class='fa fa-minus'></i></button>";
		$html[] = "			<button class='btn btn-xs btn-default $btn3_class' data-script='app/admin/2fa/edit_user.php' data-class='700' data-action='deactivate' data-id='$u->id' rel='tooltip' title='"._('Disable')."'><i class='fa fa-times'></i></button>";
		$html[] = "		</div>";
		$html[] = "		</td>";
		$html[] = "	</tr>";
	}
	$html[] = "</tbody>";
	$html[] = "</table>";

	// print
	print implode("\n", $html);
}
?>
<article>
<div style="margin-left: 40px; padding-left: 10px; border-left: 1px solid #58606b">
<?php print _("2FA status legend"); ?>:<br>
	<span class='badge badge1 severity0'><?php print _("Enabled"); ?></span> <?php print _("2fa is enabled"); ?><br>
	<span class='badge badge1 severity2'><?php print _("Disabled"); ?></span> <?php print _("2fa is disabled"); ?><br>
	<span class='badge badge1 severity1'><?php print _("Enabled, not activated"); ?></span> <?php print _("2fa is enabled, but secret is not set. User will be given new secret upon first login");?>.<br>
</div>
</article>


<article style='margin-top:50px;' class="text-muted">
<?php print _("phpIPAM supports two-factor-authentication to add additional security layer for user authentication."); ?>
<br>
<?php print _("After user successfully logs in it will be presented with additional screen to enter code from your preferred authenticator application."); ?>
<br><br>
<?php print _("Authenticator apps are available on following links based on your OS:"); ?>
<ul>
	<li> <a href='https://itunes.apple.com/us/app/google-authenticator/id388497605?mt=8' target="_self">Google Authenticator - Apple iOS</a></li>
	<li> <a href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2" target="_self">Google Authenticator - Android</a></li>
	<li> <a href='https://itunes.apple.com/us/app/microsoft-authenticator/id983156458?mt=8' target="_self">Microsoft Authenticator - Apple iOS</a></li>
	<li> <a href="https://play.google.com/store/apps/details?id=com.azure.authenticator" target="_self">Microsoft Authenticator - Android</a></li>
</ul>
<?php print _("You can also use any other OTP provider."); ?>
</article>





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
