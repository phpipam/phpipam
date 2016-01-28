<?php

/**
 *	Edit device details
 ************************/

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# create csrf token
$csrf = $User->create_csrf_cookie ();

# ID must be numeric
if($_POST['action']!="add" && !is_numeric($_POST['tid'])) { $Result->show("danger", _("Invalid ID"), true, true); }
# set delete flag
$readonly = $_POST['action']=="delete" ? "readonly" : "";

# fetch device type details
if( ($_POST['action'] == "edit") || ($_POST['action'] == "delete") ) {
	$device = $Admin->fetch_object("deviceTypes", "tid", $_POST['tid']);
	# fail if false
	$device===false ? $Result->show("danger", _("Invalid ID"), true) : null;
}
?>


<!-- header -->
<div class="pHeader"><?php print ucwords(_("$_POST[action]")); ?> <?php print _('device type'); ?></div>


<!-- content -->
<div class="pContent">

	<form id="devTypeEdit">
	<table class="table table-noborder table-condensed">

	<!-- hostname  -->
	<tr>
		<td><?php print _('Name'); ?></td>
		<td>
			<input type="text" name="tname" class="form-control input-sm" placeholder="<?php print _('Name'); ?>" value="<?php print @$device->tname; ?>" <?php print $readonly; ?>>
			<input type="hidden" name="action" value="<?php print $_POST['action']; ?>">
			<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
			<?php
			if( ($_POST['action'] == "edit") || ($_POST['action'] == "delete") ) {
				print '<input type="hidden" name="tid" value="'. $_POST['tid'] .'">'. "\n";
			}
			?>
		</td>
	</tr>

	<!-- IP address -->
	<tr>
		<td><?php print _('Description'); ?></td>
		<td>
			<input type="text" name="tdescription" class="form-control input-sm" placeholder="<?php print _('Description'); ?>" value="<?php print @$device->tdescription; ?>" <?php print $readonly; ?>>
		</td>
	</tr>

	</table>
	</form>
</div>


<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<button class="btn btn-sm btn-default <?php if($_POST['action']=="delete") { print "btn-danger"; } else { print "btn-success"; } ?>" id="editDevTypeSubmit"><i class="fa <?php if($_POST['action']=="add") { print "fa-plus"; } else if ($_POST['action']=="delete") { print "fa-trash-o"; } else { print "fa-check"; } ?>"></i> <?php print ucwords(_($_POST['action'])); ?></button>
	</div>

	<!-- result -->
	<div class="devTypeEditResult"></div>
</div>