<?php


/**
 * Script to unlock vault
 *************************************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database, false);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# create csrf token
$csrf = $User->Crypto->csrf_cookie ("create", "vaultunlock");

# make sure user has access
if ($User->get_module_permissions ("vaults")==User::ACCESS_NONE) { $Result->show("danger", _("Insufficient privileges").".", true, true); }

# ID must be numeric
if(!is_numeric($POST->id)) { $Result->show("danger", _("Invalid ID"), true, true); }

# fetch vault details
$vault = $Admin->fetch_object ("vaults", "id", $POST->id);
# null ?
$vault===false ? $Result->show("danger", _("Invalid ID"), true) : null;

?>

<!-- header -->
<div class="pHeader"><?php print _("Unlock Vault"); ?></div>

<!-- content -->
<div class="pContent">

	<form id="vaultEdit" name="vaultEdit">
	<table class="groupEdit table table-noborder table-condensed">

	<tr>
	    <td style='width:200px;'>
	        <?php print _("Enter Vault password"); ?>
	    </td>
	    <td>
	    	<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
	    	<input type="hidden" name="vaultId" value="<?php print $vault->id; ?>">
	        <input type="password" class="form-control input-sm" name="vaultpass">
	    </td>
	</tr>

</table>
</form>

</div>

<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<button class='btn btn-sm btn-default submit_popup btn-success' data-script="app/admin/vaults/unlock-result.php" data-result_div="vaultEditResult" data-form='vaultEdit'>
			<i class="fa fa-check"></i> <?php print _("Unlock"); ?>
		</button>

	</div>
	<!-- Result -->
	<div id="vaultEditResult"></div>
</div>


<script type="text/javascript">
$(document).keypress(function(e){
    if (e.which == 13){
        $(".submit_popup").click();
	    return false
    }
});
</script>