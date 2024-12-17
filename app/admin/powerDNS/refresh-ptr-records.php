<?php
/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# Initialize database connection and user objects
$Database = new Database_PDO;
$User = new User ($Database);
$Result = new Result ();

# set default language
if(isset($User->settings->defaultLang) && !is_null($User->settings->defaultLang) ) {
    # get global default language
    $lang = $User->get_default_lang();
    if (is_object($lang))
        set_ui_language($lang->l_code);
}
?>
<!-- header -->
<div class="pHeader"><?php print _("PTR zone refresh records"); ?></div>

<!-- content -->
<div class="pContent">
	<?php
	if(is_numeric($POST->subnetId)) {
	?>
	<?php print _('Clicking on regenerate will remove all PTR records for subnet and recreate new.'); ?>
	<br>

	<div class="text-righ2t">
		<a class="btn btn-default btn-sm refreshPTRsubnetSubmit" data-subnetId=<?php print escape_input($POST->subnetId); ?>><i class="fa fa-refresh"></i> <?php print _("Regenerate");?></a>
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
