<?php if (array_key_exists("2fa", $subpages)) { ?>

<?php
# verify that user is logged in
$User->check_user_session();

# init class
require_once (dirname(__FILE__)."/../../../functions/GoogleAuthenticator/PHPGangsta/GoogleAuthenticator.php");
$ga = new PHPGangsta_GoogleAuthenticator();

# secret
if (is_null($User->user->{'2fa_secret'}) && $User->user->{'2fa'}=="1") {
	// create secret
	$User->user->{'2fa_secret'} = $ga->createSecret($User->settings->{'2fa_length'});
	// admin class
	$Admin = new Admin ($Database, false);
	// update user
	if($Admin->object_modify ("users", "edit", "id", ["id"=>$User->user->id, "2fa_secret"=>$User->user->{'2fa_secret'}])===false) {
		$Result->show("danger", _("Failed to activate 2fa for user"), true, true, false, false, true );
	}
}

// get QR code
$username = strtolower($User->user->username)."@".$User->settings->{'2fa_name'};

// passkey only
if ($User->settings->dbversion >= 40 && $User->settings->{'passkeys'}=="1") {
	// get user passkeys
	$user_passkeys = $User->get_user_passkeys($User->user->id);
	// set passkey_only flag
	$passkey_only = sizeof($user_passkeys)>0 && $User->user->passkey_only=="1" ? true : false;
}
?>



<h4><?php print _('Two-factor authentication'); ?></h4>
<hr>
<span class="info2"><?php print _("Here you can change settings for two-factor authentication and get your 2fa secret."); ?></span>
<br><br>

<?php if(!$passkey_only) { ?>
<div class="panel panel-default" style="max-width:300px;min-width:350px;">
<ul class="list-group">
<div class="panel-heading"><?php print _('2fa account status'); ?></div>
<li class="list-group-item">
<form name="2fa_user" id="2fa_user">
<table id="userModSelf" class="table table-condensed" style='margin-bottom:0px;width:100%'>
<tr>
	<td class="title"><?php print _('2fa status'); ?></td>
	<?php if ($User->settings->{'2fa_userchange'}=="1") { ?>
	<td>
		<input type="checkbox" value="1" class="input-switch" name="2fa" <?php if($User->user->{'2fa'} == 1) print 'checked'; ?>>
	</td>
	<td class="text-right">
		<input type="submit" class="btn btn-default btn-success btn-sm submit_popup" data-script="app/tools/user-menu/2fa_save.php" data-result_div="userModSelf2faResult" data-form='2fa_user' value="<?php print _("Save"); ?>">
	</td>
	<?php } else { ?>
	<td>
		<?php
		print $User->user->{'2fa'} == 1 ? _("Enabled") : _("Disabled");
		?>
	</td>
	<?php }  ?>
</tr>
</table>
<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
</form>
</li>
</ul>
</div>
<?php } ?>


<!-- result -->
<div id="userModSelf2faResult" style="margin-bottom:90px;display:none"></div>

<?php

if ($passkey_only) {
	$Result->show ("warning alert-absolute", _("You can only login to your account using passkeys").".", false);
}
elseif($User->user->{'2fa_secret'}!=null && $User->user->{'2fa'}==1) {
	$html   = [];
	$html[] = "<hr><br>";
	$html[] = '<div class="loginForm row" style="width:500px;">';
	$html[] = '		'._('Details for your preferred authenticator application are below. Please write down your details, otherwise you will not be able to login to phpipam').".";
	$html[] = '		<div style="border: 2px dashed red;margin:20px;padding: 10px" class="text-center row">';
	$html[] = '			<div class="col-xs-12" style="padding:5px 10px 3px 20px;"><strong>'._('Account').':<br> <span style="color:red; font-size: 16px">'.$username.'</span></strong><hr></div>';
	$html[] = '			<div class="col-xs-12" style="padding:0px 10px 3px 20px;"><strong>'._('Secret').' :<br> <span style="color:red; font-size: 16px">'.$User->user->{'2fa_secret'}.'</span></strong></div>';
	$html[] = '		</div>';
	$html[] = '		<div class="text-center">';
	$html[] = '		<hr>'._('You can also scan following QR code with your preferred authenticator application').':<br><br>';
	$html[] = '			<div id="qrcode" style="width:200px;margin:auto;"></div>';
	$html[] = '		</div><br>';
	$html[] = '</div>';

	print implode("\n", $html);
}
?>


<?php
}
else {
	$Result->show ("warning", _("Two-factor authentication")." "._("disabled"), false);
}
?>


<script src="functions/qrcodejs/qrcode.min.js"></script>
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

// show QR code
var qrcode = new QRCode(document.getElementById("qrcode"), {
	text: "otpauth://totp/<?php print $username."?secret=".$User->user->{'2fa_secret'}; ?>",
	width: 200,
	height: 200,
	colorDark : "#000000",
	colorLight : "#ffffff",
	correctLevel : QRCode.CorrectLevel.H
});
</script>