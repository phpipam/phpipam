<?php

/**
 * Script to display customer objects
 *
 */

# verify that user is logged in
$User->check_user_session();
# verify module permissions
$User->check_module_permissions ("customers", User::ACCESS_R, true);
?>

<div style="margin-top:150px;">
	<h4><?php print _('Customer objects'); ?></h4>
</div>


<?php

# fetch all objects
$objects = $Tools->fetch_customer_objects ($customer->id);

# default page
if(!isset($GET->sPage)) {
	$GET->sPage = "subnets";
}

# menu
include("objects/menu.php");

# item or error
if(array_key_exists($GET->sPage, $Tools->get_customer_object_types())) {
	if (file_exists(dirname(__FILE__)."/objects/".$GET->sPage.".php")) {
		include("objects/".$GET->sPage.".php");
	}
	else {
		$Result->show ("danger", _("Invalid subpage"), false);
	}
}
else {
	$Result->show ("danger", _("Invalid subpage"), false);
}