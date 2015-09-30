<?php

/**
 * Not implemented message 
 */

# include required scripts
require( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);

# verify that user is logged in
$User->check_user_session();

?>

<!-- header -->
<div class="pHeader"><?php print _("Not implemented"); ?></div>

<!-- content -->
<div class="pContent">

<i class="fa fa-exclamation-triangle"></i> <?php print _("Not implemented"); ?>

</div>
<br>
<!-- footer -->
<div class="pFooter">
	<button class="btn btn-sm btn-default hidePopups"><?php print _('Ok'); ?></button>
</div>
