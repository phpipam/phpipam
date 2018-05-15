<?php

/**
 *	Edit circuit option details
 ************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();
# admin check
$User->is_admin(true);

# create csrf token
$csrf = $User->Crypto->csrf_cookie ("create", "circuit_options");

# strip tags - XSS
$_POST = $User->strip_input_tags ($_POST);

# validate action
$Admin->validate_action ($_POST['action']);

# validate type
if(!in_array($_POST['type'], array("type"))) { $Result->show("danger", _('Invalid type'), true, true); }

# disabled
$readonly = $_POST['action']=="delete" ? "disabled" : "";
?>

<!-- header -->
<div class="pHeader"><?php print ucwords(_("$_POST[action]")); ?> <?php print _('curcuit option'); ?></div>

<!-- content -->
<div class="pContent">

	<form id="circuit-option-edit" name="circuit-option-edit">
	<table class="table table-noborder table-condensed">

	<!-- option  -->
	<tr>
		<td><?php print _('Option'); ?></td>
		<td>
			<input type="text" name="option" class="form-control input-sm" placeholder="<?php print _('New Option'); ?>" value="<?php if(isset($_POST['value'])) print $_POST['value']; ?>" <?php print $readonly; ?>>
            <?php if($_POST['action']=="delete") { ?>
            <input type="hidden" name="option" value="<?php print $_POST['value']; ?>">
            <?php } ?>
            <input type="hidden" name="action" value="<?php print $_POST['action']; ?>">
            <input type="hidden" name="type" value="<?php print $_POST['type']; ?>">
            <input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
		</td>
	</tr>

	</table>
	</form>
</div>

<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<a class="btn btn-sm btn-default submit_popup <?php if($_POST['action']=="delete") { print "btn-danger"; } else { print "btn-success"; } ?>" data-script="app/admin/circuits/edit-options-submit.php" data-result_div="circuit-option-edit-result" data-form='circuit-option-edit'>
			<i class="fa <?php if($_POST['action']=="add") { print "fa-plus"; } else if ($_POST['action']=="delete") { print "fa-trash-o"; } else { print "fa-check"; } ?>"></i> <?php print ucwords(_($_POST['action'])); ?>
		</a>
	</div>

	<!-- result -->
	<div id="circuit-option-edit-result"></div>
</div>