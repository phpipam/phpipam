<?php
/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Tools	 	= new Tools ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# perm check popup
$User->check_module_permissions ("routing", User::ACCESS_RW, true, true);

# ID must be numeric
if($POST->action!="add" && !is_numeric($POST->bgpid))		{ $Result->show("danger", _("Invalid ID"), true, true); }
?>

<!-- header -->
<div class="pHeader"><?php print _('Remove mapping'); ?></div>

<!-- content -->
<div class="pContent">
	<?php
	# submit
	if(!$Admin->object_modify ("routing_subnets", "delete", "id", ["id"=>$POST->bgpid]))  { $Result->show("danger",  _("Mapping removal failed"), false); }
	else																  			   { $Result->show("success", _("Mapping removed"), false); }
	?>

</div>

<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopupsReload"><?php print _('Close'); ?></button>
	</div>
</div>