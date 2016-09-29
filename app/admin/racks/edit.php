<?php

/**
 *	Edit rack details
 ************************/

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Tools	 	= new Tools ($Database);
$Racks      = new phpipam_rack ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# create csrf token
$csrf = $User->csrf_cookie ("create", "rack");

# validate action
$Admin->validate_action ($_POST['action'], true);

# fetch custom fields
$custom = $Tools->fetch_custom_fields('racks');

# ID must be numeric
if($_POST['action']!="add" && !is_numeric($_POST['rackid']))		{ $Result->show("danger", _("Invalid ID"), true, true); }

# fetch device details
if( ($_POST['action'] == "edit") || ($_POST['action'] == "delete") ) {
	$rack = $Admin->fetch_object("racks", "id", $_POST['rackid']);
}
else {
    $rack = new StdClass ();
    $rack->size = 42;
}

# all locations
if($User->settings->enableLocations=="1")
$locations = $Tools->fetch_all_objects ("locations", "name");

# set readonly flag
$readonly = $_POST['action']=="delete" ? "readonly" : "";
?>

<script type="text/javascript">
$(document).ready(function(){
     if ($("[rel=tooltip]").length) { $("[rel=tooltip]").tooltip(); }
});
</script>


<!-- header -->
<div class="pHeader"><?php print ucwords(_("$_POST[action]")); ?> <?php print _('rack'); ?></div>


<!-- content -->
<div class="pContent">

	<form id="rackManagementEdit">
	<table class="table table-noborder table-condensed">

	<!-- hostname  -->
	<tr>
		<td><?php print _('Name'); ?></td>
		<td>
			<input type="text" name="name" class="form-control input-sm" placeholder="<?php print _('Name'); ?>" value="<?php if(isset($rack->name)) print $rack->name; ?>" <?php print $readonly; ?>>
		</td>
	</tr>

	<!-- Type -->
	<tr>
		<td><?php print _('Size'); ?></td>
		<td>
			<select name="size" class="form-control input-sm input-w-auto">
			<?php
			foreach($Racks->rack_sizes as $s) {
				if($rack->size == $s)	{ print "<option value='$s' selected='selected'>$s U</option>"; }
				else					{ print "<option value='$s' >$s U</option>"; }
			}
			?>
			</select>
		</td>
	</tr>

	<!-- Location -->
	<?php if($User->settings->enableLocations=="1") { ?>
	<tr>
		<td><?php print _('Location'); ?></td>
		<td>
			<select name="location" class="form-control input-sm input-w-auto">
    			<option value="0"><?php print _("None"); ?></option>
    			<?php
                if($locations!==false) {
        			foreach($locations as $l) {
        				if($rack->location == $l->id)	{ print "<option value='$l->id' selected='selected'>$l->name</option>"; }
        				else					{ print "<option value='$l->id'>$l->name</option>"; }
        			}
    			}
    			?>
			</select>
		</td>
	</tr>
	<?php } ?>

	<!-- Description -->
	<tr>
		<td><?php print _('Description'); ?></td>
		<td>
			<textarea name="description" class="form-control input-sm" placeholder="<?php print _('Description'); ?>" <?php print $readonly; ?>><?php if(isset($rack->description)) print $rack->description; ?></textarea>
			<?php
			if( ($_POST['action'] == "edit") || ($_POST['action'] == "delete") ) {
				print '<input type="hidden" name="rackid" value="'. $_POST['rackid'] .'">'. "\n";
			} ?>
			<input type="hidden" name="action" value="<?php print $_POST['action']; ?>">
			<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
		</td>
	</tr>

	<!-- Custom -->
	<?php
	if(sizeof($custom) > 0) {

		print '<tr>';
		print '	<td colspan="2"><hr></td>';
		print '</tr>';

		# count datepickers
		$timepicker_index = 0;

		# all my fields
		foreach($custom as $field) {
    		// create input > result is array (required, input(html), timepicker_index)
    		$custom_input = $Tools->create_custom_field_input ($field, $rack, $_POST['action'], $timepicker_index);
    		// add datepicker index
    		$timepicker_index = $timepicker_index + $custom_input['timepicker_index'];
            // print
			print "<tr>";
			print "	<td>".ucwords($field['name'])." ".$custom_input['required']."</td>";
			print "	<td>".$custom_input['field']."</td>";
			print "</tr>";
		}
	}

	?>

	</table>
	</form>
</div>


<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<button class="btn btn-sm btn-default <?php if($_POST['action']=="delete") { print "btn-danger"; } else { print "btn-success"; } ?>" id="editRacksubmit"><i class="fa <?php if($_POST['action']=="add") { print "fa-plus"; } else if ($_POST['action']=="delete") { print "fa-trash-o"; } else { print "fa-check"; } ?>"></i> <?php print ucwords(_($_POST['action'])); ?></button>
	</div>

	<!-- result -->
	<div class="rackManagementEditResult"></div>
</div>