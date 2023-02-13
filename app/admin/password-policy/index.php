<?php

/**
 *	Password policy
 **************************/

# verify that user is logged in
$User->check_user_session();
# create csrf token
$csrf = $User->Crypto->csrf_cookie ("create", "settings");

# current policy
$policy = pf_json_decode($User->settings->passwordPolicy);
?>

<!-- title -->
<h4><?php print _('phpIPAM password policy settings'); ?></h4>
<span class="text-muted"><?php print _("Here you can set password policy for user authentication."); ?></span>
<br><br>



<form name="passpolicy" id="passpolicy">
<table id="passpolicy" class="table table-hover table-condensed table-auto">



<tr>
	<td><?php print _('Minimum length'); ?></th>
	<td>
		<input type="text" class="form-control input-sm" name="minLength" maxlength="3" value="<?php print $policy->minLength; ?>">
		<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
	</td>
	<td class="info2"><?php print _('Minimum password length'); ?></td>
</tr>

<tr>
	<td><?php print _('Maximum length'); ?></th>
	<td>
		<input type="text" class="form-control input-sm" name="maxLength" maxlength="3" value="<?php print $policy->maxLength; ?>">
	</td>
	<td class="info2"><?php print _('Maximum password length'); ?></td>
</tr>

<tr>
	<td><?php print _('Minimum numbers'); ?></th>
	<td>
		<input type="text" class="form-control input-sm" name="minNumbers" maxlength="3" value="<?php print $policy->minNumbers; ?>">
	</td>
	<td class="info2"><?php print _('Minumum number of numbers'); ?></td>
</tr>

<tr>
	<td><?php print _('Minimum letters'); ?></th>
	<td>
		<input type="text" class="form-control input-sm" name="minLetters" maxlength="3" value="<?php print $policy->minLetters; ?>">
	</td>
	<td class="info2"><?php print _('Minumum number of letters'); ?></td>
</tr>

<tr>
	<td><?php print _('Minimum lowercase letter'); ?></th>
	<td>
		<input type="text" class="form-control input-sm" name="minLowerCase" maxlength="3" value="<?php print $policy->minLowerCase; ?>">
	</td>
	<td class="info2"><?php print _('Minumum number of lowercase letters'); ?></td>
</tr>

<tr>
	<td><?php print _('Minimum uppercase letter'); ?></th>
	<td>
		<input type="text" class="form-control input-sm" name="minUpperCase" maxlength="3" value="<?php print $policy->minUpperCase; ?>">
	</td>
	<td class="info2"><?php print _('Minumum number of uppercase letters'); ?></td>
</tr>

<tr>
	<td><?php print _('Minimum symbols'); ?></th>
	<td>
		<input type="text" class="form-control input-sm" name="minSymbols" maxlength="3" value="<?php print $policy->minSymbols; ?>">
	</td>
	<td class="info2"><?php print _('Minumum number of symbols'); ?></td>
</tr>

<tr>
	<td><?php print _('Maximum symbols'); ?></th>
	<td>
		<input type="text" class="form-control input-sm" name="maxSymbols" maxlength="3" value="<?php print $policy->maxSymbols; ?>">
	</td>
	<td class="info2"><?php print _('Maximum number of symbols'); ?></td>
</tr>

<tr>
	<td><?php print _('Symbols'); ?></th>
	<td>
		<input type="text" class="form-control input-sm" name="allowedSymbols" value="<?php print $policy->allowedSymbols; ?>">
	</td>
	<td class="info2"><?php print _('List of allowed symbols. csv separated.'); ?></td>
</tr>


<tr>
	<td><?php print _('Enforce'); ?></th>
	<td>
		<input type="checkbox" class="form-control input-sm" name="enforce" value="1">
	</td>
	<td class="info2"><?php print _('Require all users to change password upon next login.'); ?></td>
</tr>

<!-- Submit -->
<tr class="th">
	<td class="title"></td>
	<td class="submit" colspan="2">
		<input type="submit" class="btn btn-default btn-success btn-sm submit_popup" data-script="app/admin/password-policy/save.php" data-result_div="policyResult" data-form='passpolicy' value="<?php print _("Save"); ?>">
		<div id="policyResult"></div>
	</td>
</tr>

</table>
</form>
