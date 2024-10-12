<div id="login">

<form name="login" id="login" action="<?php $BASE ?>" class="form-inline" method="post">
<div class="loginForm row">

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


	<!-- login response -->
	<div id="loginCheck" class="col-xs-12 text-center">
		<?php
		# deauthenticate user
		if ( $User->is_authenticated()===true ) {
			# print result
			if(isset($GET->section) && $GET->section=="timeout")
				$Result->show("success", _('You session has timed out'));
			else
				$Result->show("success", _('You have logged out'));

			# write log
			$Log->write( _("User logged out"), _("User")." ".$User->username." "._("has logged out"), 0, $User->username );

			# destroy session
			$User->destroy_session();
		}

		//check if SAML2 login is possible
		$saml2settings=$Tools->fetch_object("usersAuthMethod", "type", "SAML2");

		if ($saml2settings!=false) {
			$version = db_json_decode(@file_get_contents(dirname(__FILE__).'/../../functions/php-saml/src/Saml2/version.json'), true);
			$version = $version['php-saml']['version'];

			if ($version < 3.4) {
				$Result->show("danger", _('php-saml library missing, please update submodules'));
			} else {
				$Result->show("success", _('You can login with SAML2').' <a href="'.create_link('saml2').'">'._('here').'</a>!');
			}
		}

		?>
	</div>


	<?php if($User->settings->dbversion >= 40 && $User->settings->{'passkeys'}=="1"){ ?>
	<div class="col-xs-12" style="padding-top:20px;">

		<?php
		// set disabled class if composer has errors
		$disabled = $User->composer_has_errors(["firehed/webauthn", "firehed/cbor"]) ? "disabled" : "";
		?>

		<div style="width: 45%;" class='text-center pull-left'>
			<hr style="padding-top: 3px">
		</div>
		<div style="width: 10%;" class='text-center pull-left'>
			or
		</div>
		<div style="width: 45%;" class='text-center pull-left'>
			<hr style="padding-top: 3px">
		</div>

		<button class="btn btn-sm btn-default passkey_login <?php print $disabled; ?>" style="width:100%;margin-top:20px;">
			<svg height="14" aria-hidden="true" viewBox="0 -3 32 24" version="1.1" width="20" data-view-component="true" class="octicon octicon-passkey-fill">
    			<path d="M9.496 2a5.25 5.25 0 0 0-2.519 9.857A9.006 9.006 0 0 0 .5 20.228a.751.751 0 0 0 .728.772h5.257c3.338.001 6.677.002 10.015 0a.5.5 0 0 0 .5-.5v-4.669a.95.95 0 0 0-.171-.551 9.02 9.02 0 0 0-4.814-3.423A5.25 5.25 0 0 0 9.496 2Z"></path>
    			<path d="M23.625 10.313c0 1.31-.672 2.464-1.691 3.134a.398.398 0 0 0-.184.33v.886a.372.372 0 0 1-.11.265l-.534.534a.188.188 0 0 0 0 .265l.534.534c.071.07.11.166.11.265v.347a.374.374 0 0 1-.11.265l-.534.534a.188.188 0 0 0 0 .265l.534.534a.37.37 0 0 1 .11.265v.431a.379.379 0 0 1-.097.253l-1.2 1.319a.781.781 0 0 1-1.156 0l-1.2-1.319a.379.379 0 0 1-.097-.253v-5.39a.398.398 0 0 0-.184-.33 3.75 3.75 0 1 1 5.809-3.134ZM21 9.75a1.125 1.125 0 1 0-2.25 0 1.125 1.125 0 0 0 2.25 0Z"></path>
			</svg>
			<span>
			<?php print _("Login with a passkey"); ?>
			</span>
		</button>

	</div>
	<div id="loginCheckPasskeys" class="col-xs-12 text-center"></div>
	<?php } ?>

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
