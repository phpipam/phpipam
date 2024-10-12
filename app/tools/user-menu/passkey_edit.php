<?php

/**
 *
 * Name created passkey
 */

# include required scripts
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# validate action
$User->validate_action();

# create csrf token
$csrf = $User->Crypto->csrf_cookie ("create", "passkeyedit");

# fetch passkey
$passkey = $User->get_user_passkey_by_keyId ($POST->keyid);

# validate
if(is_null($passkey))
$Result->show("danger", _("Passkey not found"), true, true);
?>


<!-- header -->
<div class="pHeader"><?php print _("Edit")." "._("passkey"); ?></div>

<!-- content -->
<div class="pContent">

	<?php
	if($POST->action=="add") {
		$Result->show("success", _("New passkey succesfully registered!"));
		print "<hr>";
	}
	?>

	<form id="passkeyEdit" name="passkeyEdit">

	<?php if ($POST->action!="delete") { ?>
	<table class="groupEdit table table-noborder table-condensed">
	<!-- name -->
	<tr>
	    <td><?php print _('Name your passkey'); ?>:</td>
	    <td>

	    	<input type="text" name="comment" class="form-control input-sm" value="<?php print escape_input(@$passkey->comment); ?>" <?php if($POST->action == "delete") print "readonly"; ?>>
	        <input type="hidden" name="keyid" value="<?php print escape_input($POST->keyid); ?>">
    		<input type="hidden" name="action" value="<?php print escape_input($POST->action); ?>">
    		<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
	    </td>
    </tr>
	</table>
    <?php } else { ?>
	        <input type="hidden" name="keyid" value="<?php print escape_input($POST->keyid); ?>">
    		<input type="hidden" name="action" value="<?php print escape_input($POST->action); ?>">
    		<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
    <?php } ?>

    <?php
	if($POST->action=="delete") {
		$Result->show("danger", _("You are about to delete your passkey ").escape_input($passkey->comment)."!", false);
	}
	?>
</form>

</div>


<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<button class='btn btn-sm btn-default submit_popup <?php if($POST->action=="delete") { print "btn-danger"; } else { print "btn-success"; } ?>' data-script="app/tools/user-menu/passkey_edit_result.php" data-result_div="passkeyEditResult" data-form='passkeyEdit'>
			<i class="fa <?php if($POST->action=="add") { print "fa-plus"; } else if ($POST->action=="delete") { print "fa-trash-o"; } else { print "fa-check"; } ?>"></i> <?php print $User->get_post_action(); ?>
		</button>

	</div>
	<!-- Result -->
	<div id="passkeyEditResult"></div>
</div>