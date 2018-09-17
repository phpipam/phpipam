<!-- test -->
<h4 style='margin-top:30px;'><?php print _('Module permissions'); ?></h4>
<hr>
<span class="info2"><?php print _("Summary of module permissions"); ?></span>
<br><br>


<?php
# Module permisisons
$user = (array) $User->user;
include(dirname(__FILE__)."/../../admin/users/print_module_permissions.php");