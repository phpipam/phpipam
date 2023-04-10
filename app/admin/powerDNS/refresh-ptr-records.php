<?php
/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );
?>
<!-- header -->
<div class="pHeader"><?php print _("PTR zone refresh records"); ?></div>

<!-- content -->
<div class="pContent">
	<?php
	if(is_numeric($_POST['subnetId'])) {
	?>
	<?php print _('Clicking on regenerate will remove all PTR records for subnet and recreate new.'); ?>
	<br>

	<div class="text-righ2t">
		<a class="btn btn-default btn-sm refreshPTRsubnetSubmit" data-subnetId=<?php print escape_input($_POST['subnetId']); ?>><i class="fa fa-refresh"></i> Regenerate</a>
		<hr>
	</div>

	<!-- result -->
	<div class="refreshPTRsubnetResult" style="padding: 10px;"></div>
	<?php
	} else {
		print "<div class='alert alert-danger'>"._("Invalid Subnet ID")."</div>";
	} ?>
</div>


<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopupsReload"><?php print _('Close'); ?></button>
	</div>
</div>