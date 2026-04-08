	<!-- title -->
	<div class="col-xs-12">
		<legend style="margin-top:10px;"><?php print _('Please login'); ?></legend>
	</div>

	<?php if(!is_blank(@$User->settings->siteLoginText)) { ?>
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

	<div class="col-xs-12" style="padding-top:15px;">
		<!-- <hr style="margin-top:5px;margin-bottom:10px;"> -->
		<input type="submit" value="<?php print _('Login'); ?>" class="btn btn-sm btn-success" style="width:100%"></input>
	</div>

	<?php require(dirname(__FILE__) . '/login_form_sso.php'); ?>

	<?php if(defined('IS_DEMO')) { ?>

	</div>

	<div class="alert alert-warning" style="width:400px;margin:auto;margin-top:30px;">
	<strong>Demo accounts:</strong>
	<span class="pull-right">
	<!-- Place this tag where you want the +1 button to render -->
	<g:plusone size="medium" class='pull-right'></g:plusone>
	<!-- Place this render call where appropriate -->
	<script type="text/javascript">
	(function() {
	  var po = document.createElement('script'); po.type = 'text/javascript'; po.async = true;
	    po.src = 'https://apis.google.com/js/plusone.js';
	    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(po, s);
	})();
	</script>
	</span>
	<hr>
	<strong>Admin demo:</strong> Admin / ipamadmin<br>
	<strong>Viewer demo:</strong> demo / demo1234<br>

	<?php } ?>
