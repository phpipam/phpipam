<?php

# verify that user is logged in
$User->check_user_session();

# fetch user details
$user = $Admin->fetch_object("users", "id", $GET->subnetId);

# invalid?
if($user===false) { $Result->show("danger", _("Invalid ID"), true); }

# fetch user lang
$language 	  = $Admin->fetch_object("lang", "l_id", $user->lang);
# check users auth method
$auth_details = $Admin->fetch_object("usersAuthMethod", "id", $user->authMethod);
# fetch custom fields
$custom_fields = $Tools->fetch_custom_fields('users');
?>

<!-- display existing users -->
<h4><?php print _('User details'); ?> <?php print _("for user"); ?> <?php print $user->real_name; ?></h4>
<hr><br>

<!-- Show all users -->
<a class='btn btn-sm btn-default' href="<?php print create_link("administration","users"); ?>" style="margin-bottom:10px;"><i class='fa fa-angle-left'></i> <?php print _('All users'); ?></a>


<!-- menu -->
<ul class="nav nav-tabs">
	<?php
	/* Include subpage */
	$subpages = [
		"account"        => "Account details",
		"modules"        => "Module permissions",
		"authentication" => "Authentication",
		"display"        => "Display settings",
		"mail"           => "Mail settings"
	];

	// default tab
	if(!isset($GET->sPage)) {
		$GET->sPage = "account";
	}

	// check
	if(!array_key_exists($GET->sPage, $subpages)) 	{ $Result->show("danger", "Invalid request", true); }

	// print
	foreach($subpages as $href=>$t) {
		$class = $GET->sPage==$href ? "class='active'" : "";
		print "<li role='presentation' $class><a href=".create_link("administration", "users", $GET->subnetId, $href).">". _($t)."</a></li>";
	}
	?>
</ul>

<div class="container-fluid" style="margin-top: 10px;">
<table id="userPrint" class="table table-hover table-auto table-condensed table-noborder">
	<?php
	// include
	include ($GET->sPage.".php");
	?>
</table>
</div>