<?php

/**
 * Usermenu - user can change password and email
 */

# verify that user is logged in
$User->check_user_session();

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

	// default tab
	if(!isset($_GET['subnetId'])) {
		$_GET['subnetId'] = "account";
	}

	// check
	if(!array_key_exists($_GET['subnetId'], $subpages)) 	{ $Result->show("danger", "Invalid request", true); }

	// print
	foreach($subpages as $href=>$t) {
		$class = $_GET['subnetId']==$href ? "class='active'" : "";
		print "<li role='presentation' $class><a href=".create_link("tools", "user-menu", "$href").">". _($t)."</a></li>";
	}
	?>
</ul>

<div class="container-fluid" style="margin-top: 10px;">
	<?php
	// include
	include ($_GET['subnetId'].".php");
	?>
</div>