<?php

/**
 *	Edit circuit option details
 ************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database, false);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();
# perm check
$User->check_module_permissions ("circuits", User::ACCESS_RWA, true, false);

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
<div class="pHeader"><?php print ucwords(_("$_POST[action]")); ?> <?php print _('Circuit option'); ?></div>

<!-- content -->
<div class="pContent">

	<form id="circuit-option-edit" name="circuit-option-edit">
	<table class="table table-noborder table-condensed">

	<!-- option  -->
	<tr>
		<td><?php print _('Option Name'); ?></td>
		<td>
			<input type="text" name="option" class="form-control input-sm" placeholder="<?php print _('New Option'); ?>" value="<?php if(isset($_POST['value'])) print escape_input($_POST['value']); ?>" <?php print $readonly; ?>>

		</td>
	</tr>
	<tr>
		<td><?php print  _('Map Color') ?></td>
		<td>

			<input type="color" name="color" id="pick-a-color" class="form-control input-sm" placeholder="<?php print _('Hex Color (ex. #000000)'); ?>" value="<?php if(isset($_POST['color'])) print escape_input($_POST['color']); ?>" <?php print $readonly; ?>>

		</td>
	</tr>
	<tr>
		<td><?php print  _('Map Pattern') ?></td>
		<td>
			<select name="pattern"  <?php print $readonly; ?>>
				<option <?php if(isset($_POST['pattern']) && $_POST['pattern'] == 'Solid'){ print 'selected'; }?>>Solid</option>
				<option <?php if(isset($_POST['pattern']) && $_POST['pattern'] == 'Dotted'){ print 'selected'; }?>>Dotted</option>
			</select>
		</td>
	</tr>
	<?php if($_POST['action']=="delete") { ?>
	<input type="hidden" name="option" value="<?php print escape_input($_POST['value']); ?>">
	<?php } ?>
	<input type="hidden" name="action" value="<?php print escape_input($_POST['action']); ?>">
	<input type="hidden" name="type" value="<?php print escape_input($_POST['type']); ?>">
	<input type="hidden" name="op_id" value="<?php print escape_input($_POST['op_id']); ?>">
	<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
	</table>
	</form>
</div>



<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<a class="btn btn-sm btn-default submit_popup <?php if($_POST['action']=="delete") { print "btn-danger"; } else { print "btn-success"; } ?>" data-script="app/admin/circuits/edit-options-submit.php" data-result_div="circuit-option-edit-result" data-form='circuit-option-edit'>
			<i class="fa <?php if($_POST['action']=="add") { print "fa-plus"; } else if ($_POST['action']=="delete") { print "fa-trash-o"; } else { print "fa-check"; } ?>"></i> <?php print escape_input(ucwords(_($_POST['action']))); ?>
		</a>
	</div>

	<!-- result -->
	<div id="circuit-option-edit-result"></div>
</div>


<script src="js/bootstrap-colorpicker.min.js?v=<?php print SCRIPT_PREFIX; ?>"></script>
<link rel="stylesheet" type="text/css" href="css/bootstrap/bootstrap-colorpicker.min.css?v=<?php print SCRIPT_PREFIX; ?>">
<script>
$(function(){
    $('#color-picker').colorpicker();
});
