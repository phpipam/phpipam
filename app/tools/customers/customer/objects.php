<?php

/**
 * Script to display customer objects
 *
 */

# verify that user is logged in
$User->check_user_session();
# verify module permissions
$User->check_module_permissions ("customers", 1, true);
?>

<div style="margin-top:150px;">
	<h4><?php print _('Customer objects'); ?></h4>
	<hr>
</div>


<?php

# menu
include("objects/menu.php");

# item or error
