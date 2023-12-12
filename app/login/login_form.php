<div id="login">

<form name="login" id="login" class="form-inline" method="post">
<div class="loginForm row">

	<!-- title -->
	<div class="col-xs-12">
		<legend style="margin-top:10px;"><?php print _('Please login'); ?></legend>
	</div>

	<?php if(strlen(@$User->settings->siteLoginText)>0) { ?>
    <!-- login text -->
    <div class="col-xs-12 text-muted text-right" style="margin-bottom:1em;"><?php print $User->settings->siteLoginText; ?></div>
	<?php } ?>

	<!-- username -->
	<div class="col-xs-12"><strong><?php print _('Username'); ?></strong></div>
	<div class="col-xs-12">
		<input type="text" id="username" name="ipamusername" class="login form-control input-sm" placeholder="<?php print _('Username'); ?>" autofocus="autofocus" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"></input>
	</div>

	<!-- password -->
	<div class="col-xs-12"><strong><?php print _('Password'); ?></strong></div>
	<div class="col-xs-12">
	    <input type="password" id="password" name="ipampassword" class="login form-control input-sm" placeholder="<?php print _('Password'); ?>" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"></input>
	    <?php
		// add requested var for redirect
		if ($redirect = $User->get_redirect_cookie()) {
			print "<input type='hidden' name='phpipamredirect' id='phpipamredirect' value='" . escape_input($redirect) . "'>";
		}
	    ?>
	</div>

	<?php
	# do we need captcha?
	$cnt = $User->block_check_ip ();
	if($cnt>4) {
	?>
	<!-- captcha -->
	<div class="col-xs-12"><strong><?php print _('Security code'); ?></strong></div>
	<div class="col-xs-12">
		<input id="validate_captcha" type="text" name="captcha" class="login form-control input-sm col-xs-12">
	</div>
	<div class="col-xs-12">
		<img src="<?php print $url.BASE; ?>app/login/captchashow.php" alt="<?php print _("CAPTCHA image"); ?>" class="imgcaptcha" align="captcha">
	</div>
	<?php } ?>

	<div class="col-xs-12">
		<hr>
		<input type="submit" value="<?php print _('Login'); ?>" class="btn btn-sm btn-default pull-right"></input>
	</div>

</div>

</form>

<?php
/* show request module if enabled in config file */
if($User->settings->enableIPrequests == 1) {
?>
<div class="iprequest">
	<a href="<?php print create_link("request_ip"); ?>">
	<i class="fa fa-plus fa-pad-right"></i> <?php print _('Request new IP address'); ?>
	</a>
</div>
<?php
}
?>

</div>
