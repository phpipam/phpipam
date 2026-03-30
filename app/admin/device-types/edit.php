<?php

/**
 *	Edit device details
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
# perm check popup
if ($POST->action == "edit") {
	$User->check_module_permissions("devices", User::ACCESS_RW, true, true);
} else {
	$User->check_module_permissions("devices", User::ACCESS_RWA, true, true);
}

# create csrf token
$csrf = $User->Crypto->csrf_cookie ("create", "device_types");

# validate action
$Admin->validate_action();

# ID must be numeric
if($POST->action!="add" && !is_numeric($POST->tid)) { $Result->show("danger", _("Invalid ID"), true, true); }
# set delete flag
$readonly = $POST->action=="delete" ? "readonly" : "";
# set default values
if($POST->action=="add") {
	$device = (object) array(
		"bgcolor"=>"#E6E6E6",
		"fgcolor"=>"#000",
		);
}

# fetch device type details
if( ($POST->action == "edit") || ($POST->action == "delete") ) {
	$device = $Admin->fetch_object("deviceTypes", "tid", $POST->tid);
	# fail if false
	$device===false ? $Result->show("danger", _("Invalid ID"), true) : null;
}
?>

<script src="js/bootstrap-colorpicker.min.js?v=<?php print SCRIPT_PREFIX; ?>"></script>
<link rel="stylesheet" type="text/css" href="css/bootstrap/bootstrap-colorpicker.min.css?v=<?php print SCRIPT_PREFIX; ?>">
<script>
$(function(){
    $('.select-bgcolor').colorpicker();
});
$(function(){
    $('.select-fgcolor').colorpicker();
});

</script>


<!-- header -->
<div class="pHeader"><?php print $User->get_post_action(); ?> <?php print _('device type'); ?></div>


<!-- content -->
<div class="pContent">

	<form id="devTypeEdit">
	<table class="table table-noborder table-condensed">

	<!-- type -->
	<tr>
		<td><?php print _('Name'); ?></td>
		<td>
			<input type="text" name="tname" class="form-control input-sm" placeholder="<?php print _('Name'); ?>" value="<?php print @$device->tname; ?>" <?php print $readonly; ?>>
			<input type="hidden" name="action" value="<?php print escape_input($POST->action); ?>">
			<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
			<?php
			if( ($POST->action == "edit") || ($POST->action == "delete") ) {
				print '<input type="hidden" name="tid" value="'. escape_input($POST->tid) .'">'. "\n";
			}
			?>
		</td>
	</tr>

	<!-- description -->
	<tr>
		<td><?php print _('Description'); ?></td>
		<td>
			<input type="text" name="tdescription" class="form-control input-sm" placeholder="<?php print _('Description'); ?>" value="<?php print @$device->tdescription; ?>" <?php print $readonly; ?>>
		</td>
	</tr>

	<!-- bg color -->
	<tr>
		<td><?php print _('Bg color'); ?></td>
		<td>
			<div class="input-group select-bgcolor">
				<input type="text" name="bgcolor" class="form-control input-xs"  value="<?php print $device->bgcolor; ?>"  maxlength='32' <?php if($POST->action == "delete") print "readonly"; ?>><span class="input-group-addon"><i></i></span>
			</div>
		</td>
	</tr>

	<!-- fg color -->
	<tr>
		<td><?php print _('Fg color'); ?></td>
		<td>
			<div class="input-group select-fgcolor">
				<input type="text" name="fgcolor" class="form-control input-sm"  value="<?php print $device->fgcolor; ?>"  maxlength='32' <?php if($POST->action == "delete") print "readonly"; ?>><span class="input-group-addon"><i></i></span>
			</div>
		</td>
	</tr>

	</table>
	</form>
</div>


<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<button class="btn btn-sm btn-default <?php if($POST->action=="delete") { print "btn-danger"; } else { print "btn-success"; } ?>" id="editDevTypeSubmit"><i class="fa <?php if($POST->action=="add") { print "fa-plus"; } elseif ($POST->action=="delete") { print "fa-trash-o"; } else { print "fa-check"; } ?>"></i> <?php print $User->get_post_action(); ?></button>
	</div>

	<!-- result -->
	<div class="devTypeEditResult"></div>
</div>
