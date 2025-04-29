<?php

/**
 * Usermenu - user can change password and email
 */

# verify that user is logged in
$User->check_user_session();

# create csrf token
$csrf = $User->Crypto->csrf_cookie ("create", "user-menu");

# fetch all languages
$langs = $User->fetch_langs();

/* print hello */
print "<h4>".$User->user->real_name.", "._('here you can change your account details').":</h4>";
print "<hr><br>";

?>

<ul class="nav nav-tabs">
	<?php
	/* Include subpage */
	$subpages = [
		"account" => "Account details",
		"widgets" => "Widgets"
		];

	// module permisisons
	$subpages['permissions'] = "Module permissions";

	// 2fa
	if ($User->settings->{'2fa_provider'}!=='none') {
	$subpages['2fa'] = "Two-factor authentication";
	}

	// Passkeys
	if ($User->settings->dbversion >= 40 && $User->settings->{'passkeys'}=="1") {
	$subpages['passkeys'] = "Passwordless authentication";
	}

	// default tab
	if(!isset($GET->subnetId)) {
		$GET->subnetId = "account";
	}

	// check
	if(!array_key_exists($GET->subnetId, $subpages)) 	{ $Result->show("danger", "Invalid request", true); }

	// print
	foreach($subpages as $href=>$t) {
		$class = $GET->subnetId==$href ? "class='active'" : "";
		print "<li role='presentation' $class><a href=".create_link("tools", "user-menu", "$href").">". _($t)."</a></li>";
	}
	?>
</ul>

<div class="container-fluid" style="margin-top: 10px;">
	<?php
	// include
	include ($GET->subnetId.".php");
	?>
</div>