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

<?php

/**
 * Usermenu - user can change password and email
 */

# verify that user is logged in
$User->check_user_session();

# fetch all languages
$langs = $User->fetch_langs();

// passkeys
if ($User->settings->dbversion >= 40 && $User->settings->{'passkeys'}=="1") {
	// get user passkeys
	$user_passkeys = $User->get_user_passkeys($User->user->id);
	// set passkey_only flag
	$passkey_only = sizeof($user_passkeys)>0 && $User->user->passkey_only=="1" ? true : false;
}
?>

<!-- test -->
<h4><?php print _('Account details'); ?></h4>
<hr>
<span class="info2"><?php print _("Change details for your account"); ?></span>
<br><br>



<form id="userModSelf">
<table id="userModSelf" class="table table-condensed">

<!-- real name -->
<tr>
    <td><?php print _('Real name'); ?></td>
    <td>
        <input type="text" class="form-control input-sm" name="real_name" value="<?php print $User->user->real_name; ?>">
    </td>
    <td class="info2"><?php print _('Display name'); ?></td>
</tr>

<!-- username -->
<tr>
    <td><?php print _('E-mail'); ?></td>
    <td>
        <input type="email" class="form-control input-sm"  name="email" value="<?php print $User->user->email; ?>" autocomplete="off">
    </td>
    <td class="info2"><?php print _('Email address'); ?></td>
</tr>

<?php
# show pass only to local users!
if($User->user->authMethod == 1) {
?>
<!-- password -->
<tr>
    <td><?php print _('Password'); ?></td>
    <td>
        <input type="password" class="userPass form-control input-sm" name="password1">
    </td style="white-space:nowrap">
    <td class="info2"><?php print _('Password'); ?> <button id="randomPassSelf" class="btn btn-xs btn-default"><i class="fa fa-gray fa-random"></i></button><span id="userRandomPass" style="padding-left:15px;"></span></td>
</tr>

<!-- password repeat -->
<tr>
    <td><?php print _('Password'); ?> (<?php print _('repeat'); ?>)</td>
    <td>
        <input type="password" class="userPass form-control input-sm" name="password2">
    </td>
    <td class="info2"><?php print _('Re-type password'); ?></td>
</tr>
<?php } ?>


<?php if ($User->settings->dbversion >= 40 && $User->settings->{'passkeys'}=="1") { ?>
<!-- passkey login only -->
<tr>
    <td><?php print _('Passkey login only'); ?></td>
    <td>
		<input type="checkbox" value="1" class="input-switch" name="passkey_only" <?php if($User->user->passkey_only == "1") print 'checked'; ?>>
    </td>
    <td class="info2"><?php print _('Select to only allow account login with passkey'); ?>
    	<?php if(sizeof($user_passkeys)==0 && $User->user->passkey_only=="1") { print "<br><span class='text-warning'>". _("You can login to your account with normal authentication method only untill you create passkeys.")."</span>"; } ?>
    </td>
</tr>
<?php } ?>


<!-- select theme -->
<tr>
	<td><?php print _('Theme'); ?></td>
	<td>
		<select name="theme" class="form-control input-sm input-w-auto">
			<option value="default"><?php print _("Default"); ?></option>
			<?php
			foreach($User->themes as $theme) {
				if($theme==$User->user->theme)	{ print "<option value='$theme' selected>$theme</option>"; }
				else							{ print "<option value='$theme'		    >$theme</option>"; }
			}
			?>
		</select>
	</td>
	<td class="info2"><?php print _('Select UI theme'); ?></td>
</tr>

<!-- select language -->
<tr>
	<td><?php print _('Language'); ?></td>
	<td>
		<select name="lang" class="form-control input-sm input-w-auto">
			<?php
			foreach($langs as $lang) {
				if($lang->l_id==$User->user->lang)	{ print "<option value='$lang->l_id' selected>$lang->l_name ($lang->l_code)</option>"; }
				else								{ print "<option value='$lang->l_id'		 >$lang->l_name ($lang->l_code)</option>"; }
			}
			?>
		</select>
	</td>
	<td class="info2"><?php print _('Select language'); ?></td>
</tr>

<!-- weather to receive mails -->
<tr>
	<td><?php print _('Mail notifications'); ?></td>
	<td>
		<select name="mailNotify" class="form-control input-sm input-w-auto">
			<option value="No"><?php print _("No"); ?></option>
			<option value="Yes" <?php if($User->user->mailNotify=="Yes") { print "selected='selected'"; } ?>><?php print _("Yes"); ?></option>
		</select>
	</td>
	<?php if($User->user->role=="Administrator") { ?>
	<td class="info2"><?php print _('Select yes to receive mail notification changes (IP state change, new hosts, requests)'); ?></td>
    <?php } else { ?>
	<td class="info2"><?php print _('Select yes to receive mail notifications for IP requests'); ?></td>
    <?php } ?>
</tr>

<!-- weather to receive mails for changelog -->
<tr>
	<td><?php print _('Mail Changelog'); ?></td>
	<td>
		<select name="mailChangelog" class="form-control input-sm input-w-auto">
			<option value="No"><?php print _("No"); ?></option>
			<option value="Yes" <?php if($User->user->mailChangelog=="Yes") { print "selected='selected'"; } ?>><?php print _("Yes"); ?></option>
		</select>
	</td>
	<?php if($User->user->role=="Administrator") { ?>
	<td class="info2"><?php print _('Select yes to receive notification change mail for changelog'); ?></td>
    <?php } else { ?>
	<td class="info2"><?php print _('Select yes to receive notification change mail for changelog'); ?></td>
    <?php } ?>
</tr>


<!-- display settings -->
<tr>
	<td colspan="2"><hr></td>
</tr>
<!-- Display -->
<tr class="settings-title">
	<th colspan="3"><h4><?php print _('Display settings'); ?></h4></th>
</tr>

<!-- DHCP compress -->
<tr>
	<td class="title"><?php print _('Override compression'); ?></td>
	<td>
		<input type="checkbox" value="Uncompress" class="input-switch" name="compressOverride" <?php if($User->user->compressOverride == "Uncompress") print 'checked'; ?>>
	</td>
	<td class="info2">
		<?php print _('Uncompress address ranges if compressed'); ?>
	</td>
</tr>

<!-- Hide free range -->
<tr>
	<td class="title"><?php print _('Hide free range'); ?></td>
	<td>
		<input type="checkbox" value="1" class="input-switch" name="hideFreeRange" <?php if($User->user->hideFreeRange == 1) print 'checked'; ?>>
	</td>
	<td class="info2">
		<?php print _('Do not display free range in IP address and subnets list'); ?>
	</td>
</tr>

<!-- Compress text in menu -->
<tr>
	<td class="title"><?php print _('Compress text in top menu'); ?></td>
	<td>
		<input type="checkbox" value="1" class="input-switch" name="menuCompact" <?php if($User->user->menuCompact == 1) print 'checked'; ?>>
	</td>
	<td class="info2">
		<?php print _('Do not show text next to menu items in dynamic menu'); ?>
	</td>
</tr>

<!-- Menu type -->
<tr>
	<td class="title"><?php print _('Menu Type'); ?></td>
	<td>
		<select name="menuType" class="form-control input-sm input-w-auto">
			<?php
			$opts = array(
				"Static"=>_("Static"),
				"Dynamic"=>_("Dynamic")
			);
			foreach($opts as $key=>$line) {
				if($User->user->menuType == $key) { print "<option value='$key' selected>$line</option>"; }
				else 							  { print "<option value='$key'>$line</option>"; }
			}
			?>
		</select>
	</td>
	<td class="info2">
		<?php print _('Select menu type to display'); ?>
	</td>
</tr>


<!-- Submit and hidden values -->
<tr class="th">
    <td></td>
    <td class="submit">
        <input type="submit" class="btn btn-sm btn-success pull-right" value="<?php print _('Save changes'); ?>">
    </td>
    <td></td>
</tr>

</table>
<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
</form>


<!-- result -->
<div class="userModSelfResult" style="margin-bottom:90px;display:none"></div>