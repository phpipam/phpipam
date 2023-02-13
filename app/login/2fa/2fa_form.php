<?php
# create csrf token
$csrf = $User->Crypto->csrf_cookie ("create", "2fa_validation");
?>

<div id="login">

<form name="login_2fs" id="login_2fs" class="form-inline" method="post">
<div class="loginForm row login_2fs">

	<!-- title -->
	<div class="col-xs-12">
		<legend style="margin-top:10px;"><?php print _('Two-factor authentication'); ?></legend>
	</div>

	<!-- username -->
	<div class="col-xs-12"><strong><?php print _('Two-factor code'); ?></strong></div>
	<div class="col-xs-12">
		<input type="text" id="2fa_code" name="code" class="login form-control input-sm" placeholder="<?php print _('Enter 2fa code'); ?>" autofocus="autofocus" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"></input>
		<input type="hidden" id='csrf_cookie' name="csrf_cookie" value="<?php print $csrf; ?>">
	    <?php
		// add requested var for redirect
		if ($redirect = $User->get_redirect_cookie()) {
			print "<input type='hidden' name='phpipamredirect' id='phpipamredirect' value='" . escape_input($redirect) . "'>";
		}
	    ?>
	</div>
	<div class="col-xs-12">
	<?php print _("Please enter two-factor authentication code from Google Authenticator"); ?>
		<hr>
	</div>
	<div class="col-xs-12 text-right">
	<div class="btn-group">
		<a href="<?php print create_link("login"); ?>" class="btn btn-sm btn-default"><?php print _('Login'); ?></a>
		<input type="submit" value="<?php print _('Validate'); ?>" class="btn btn-sm btn-success"></input>
	</div>
	</div>


	<div class="col-xs-12" style='margin-top:10px;display:none' id="twofaCheck"></div>
</div>
</form>

</div>