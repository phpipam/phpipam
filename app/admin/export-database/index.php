<?php

/**
 *	Script to export IP database to excel file!
 **********************************************/

# verify that user is logged in
$User->check_user_session();

?>

<h4><?php print _('phpIPAM database export'); ?></h4>
<hr><br>

<div class="alert alert-info alert-absolute"><?php print _('You can download MySQL dump of database or generate XLS file of IP addresses'); ?>!</div>

<!-- MySQL dump -->
<hr style="margin-top:50px;">
<h4><?php print _('Create MySQL database dump'); ?></h4>
<button class="btn btn-sm btn-default" id="MySQLdump"><i class="fa fa-download"></i> <?php print _('Prepare MySQL dump'); ?></button>

<!-- XLS dump -->
<h4><?php print _('Create XLS file of IP addresses'); ?></h4>
<button class="btn btn-sm btn-default" id="XLSdump"><i class="fa fa-download"></i> <?php print _('Prepare XLS dump'); ?></button>

<!-- XLS dump -->
<h4><?php print _('Create hostfile dump'); ?></h4>
<button class="btn btn-sm btn-default" id="hostfileDump"><i class="fa fa-download"></i> <?php print _('Prepare hostfile dump'); ?></button>