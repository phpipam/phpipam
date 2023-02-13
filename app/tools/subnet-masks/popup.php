<?php

/**
 * print subnet masks popup
 */

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# database object
$Database 	= new Database_PDO;
# initialize objects
$Result		= new Result;
$User		= new User ($Database);
$Subnets	= new Subnets ($Database);

# verify that user is logged in
$User->check_user_session();

# strip tags - XSS
$_POST = $User->strip_input_tags ($_POST);
?>

<!-- header -->
<div class="pHeader"><?php print _('Subnet masks'); ?></div>

<!-- content -->
<div class="pContent">
	<?php
	// set popup
	$popup = true;
	// table
	include('print-table.php');
	?>
</div>

<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default <?php escape_input(print @$_REQUEST['closeClass']); ?>"><?php print _('Close'); ?></button>
	</div>
</div>
