<?php

/**
 *	Edit device details
 ************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database, false);
$Tools	 	= new Tools ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();
# perm check popup
if($_POST['action']=="edit") {
    $User->check_module_permissions ("devices", User::ACCESS_RW, true, true);
}
else {
    $User->check_module_permissions ("devices", User::ACCESS_RWA, true, true);
}

# create csrf token
$csrf = $User->Crypto->csrf_cookie ("create", "device");

# strip tags - XSS
$_POST = $User->strip_input_tags ($_POST);

# validate action
$Admin->validate_action ($_POST['action'], true);

# fetch custom fields
$custom = $Tools->fetch_custom_fields('devices');

# ID must be numeric
if($_POST['action']!="add" && !is_numeric($_POST['switchid']))		{ $Result->show("danger", _("Invalid ID"), true, true); }

# fetch device details
if( ($_POST['action'] == "edit") || ($_POST['action'] == "delete") ) {
	$device = (array) $Admin->fetch_object("devices", "id", $_POST['switchid']);
	// false
	if ($device===false)                                            { $Result->show("danger", _("Invalid ID"), true, true);  }
}
// defaults
else {
	$device = array ();
	$device['type']       = 9;
	$device['rack_start'] = 1;
	$device['rack_size']  = 1;
}

# set readonly flag
$readonly = $_POST['action']=="delete" ? "readonly" : "";


# all locations
if($User->settings->enableLocations=="1")
$locations = $Tools->fetch_all_objects ("locations", "name");

// set show for rack
if (is_null(@$device['rack']))  { $display='display:none'; }
else                            { $display=''; }
?>

<script>
$(document).ready(function(){
     if ($("[rel=tooltip]").length) { $("[rel=tooltip]").tooltip(); }
});
// form change
$('#switchManagementEdit select[name=rack]').change(function() {
   //change id
   $('.showRackPopup').attr("data-rackid",$('#switchManagementEdit select[name=rack]').val());
   //toggle show
   if($('#switchManagementEdit select[name=rack]').val().length == 0) { $('tbody#rack').hide(); }
   else                                                               { $('tbody#rack').show(); }
   // select location
   var loc = $('#switchManagementEdit select[name=rack] :selected').attr('data-location');
   $('select[name=location] option:selected').prop("selected",null)
   $('select[name=location] option[value="'+loc+'"]').prop("selected","selected");

   // load dropdown
   $.post("app/admin/devices/edit-rack-dropdown.php", {rackid:$('#switchManagementEdit select[name=rack]').val(), deviceid:$('#switchManagementEdit input[name=switchid]').val(), action:$('#switchManagementEdit input[name=action]').val()}, function(data) {
   		$('tbody#rack').html(data);
   });
});
</script>


<!-- header -->
<div class="pHeader"><?php print ucwords(_("$_POST[action]")); ?> <?php print _('device'); ?></div>

<!-- content -->
<div class="pContent">

	<form id="switchManagementEdit">
	<table class="table table-noborder table-condensed">

	<!-- hostname  -->
	<tr>
		<td><?php print _('Name'); ?></td>
		<td>
			<input type="text" name="hostname" class="form-control input-sm" placeholder="<?php print _('Hostname'); ?>" value="<?php if(isset($device['hostname'])) print $Tools->strip_xss($device['hostname']); ?>" <?php print $readonly; ?>>
		</td>
	</tr>

	<!-- IP address -->
	<tr>
		<td><?php print _('IP address'); ?></td>
		<td>
			<input type="text" name="ip_addr" class="form-control input-sm" placeholder="<?php print _('IP address'); ?>" value="<?php if(isset($device['ip_addr'])) print $Tools->strip_xss($device['ip_addr']); ?>" <?php print $readonly; ?>>
		</td>
	</tr>

	<!-- Type -->
	<tr>
		<td><?php print _('Device type'); ?></td>
		<td>
			<select name="type" class="form-control input-sm input-w-auto">
			<?php
			$types = $Admin->fetch_all_objects("deviceTypes", "tid");
			foreach($types as $name) {
				if($device['type'] == $name->tid)	{ print "<option value='$name->tid' selected='selected'>$name->tname</option>"; }
				else								{ print "<option value='$name->tid' >$name->tname</option>"; }
			}
			?>
			</select>
		</td>
	</tr>

	<!-- Location -->
	<?php if($User->settings->enableLocations=="1" && $User->get_module_permissions ("locations")>=User::ACCESS_R) { ?>
	<tr>
		<td><?php print _('Location'); ?></td>
		<td>
			<select name="location" class="form-control input-sm input-w-auto">
    			<option value="0"><?php print _("None"); ?></option>
    			<?php
                if($locations!==false) {
        			foreach($locations as $l) {
        				if($device['location'] == $l->id)	{ print "<option value='$l->id' selected='selected'>$l->name</option>"; }
        				else								{ print "<option value='$l->id'>$l->name</option>"; }
        			}
    			}
    			?>
			</select>
		</td>
	</tr>
	<?php } ?>

    <!-- Rack -->
    <?php if($User->settings->enableRACK=="1" && $User->get_module_permissions ("racks")>=User::ACCESS_R) { ?>
	<tr>
	   	<td colspan="2"><hr></td>
    </tr>
    <tr>
        <?php
        $Racks = new phpipam_rack ($Database);
        $Racks->fetch_all_racks();
        ?>
        <td><?php print _('Rack'); ?></td>
        <td>
            <select name="rack" class="form-control input-sm">
                <option value="0"><?php print _("None"); ?></option>
                <?php
                if ($Racks->all_racks!==false) {
                    foreach ($Racks->all_racks as $r) {
                        if($device['rack'] == $r->id)   { print "<option value='$r->id' data-location='$r->location' selected>$r->name</option>"; }
                        else                            { print "<option value='$r->id' data-location='$r->location'>$r->name</option>"; }
                    }
                }
                ?>
            </select>
        </td>
    </tr>

	<?php if ($User->get_module_permissions ("racks")>=User::ACCESS_R) { ?>
    <tbody id="rack" style="<?php print $display; ?>">
		<?php include ("edit-rack-dropdown.php"); ?>
    </tbody>
	<tr>
	   	<td colspan="2"><hr></td>
    </tr>
    <?php } ?>
    <?php } ?>

	<!-- Description -->
	<tr>
		<td><?php print _('Description'); ?></td>
		<td>
			<textarea name="description" class="form-control input-sm" placeholder="<?php print _('Description'); ?>" <?php print $readonly; ?>><?php if(isset($device['description'])) print $device['description']; ?></textarea>
			<?php
			if( ($_POST['action'] == "edit") || ($_POST['action'] == "delete") ) {
				print '<input type="hidden" name="switchid" value="'. $_POST['switchid'] .'">'. "\n";
			} ?>
			<input type="hidden" name="action" value="<?php print escape_input($_POST['action']); ?>">
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
    		$custom_input = $Tools->create_custom_field_input ($field, $device, $timepicker_index);
    		$timepicker_index = $custom_input['timepicker_index'];
            // print
			print "<tr>";
			print "	<td>".ucwords($Tools->print_custom_field_name ($field['name']))." ".$custom_input['required']."</td>";
			print "	<td>".$custom_input['field']."</td>";
			print "</tr>";
		}
	}
	?>

	<!-- Sections -->
	<tr>
		<td colspan="2">
			<hr>
		</td>
	</tr>
	<tr>
		<td colspan="2"><?php print _('Sections to display device in'); ?>:</td>
	</tr>
	<tr>
		<td></td>
		<td>
		<?php
		# select sections
		$Sections = new Sections ($Database);
		$sections = $Sections->fetch_all_sections();

		# reformat device sections to array
		$deviceSections = pf_explode(";", @$device['sections']);
		$deviceSections = is_array($deviceSections) ? $deviceSections : array();

		if ($sections!==false) {
			foreach($sections as $section) {
				if(in_array($section->id, $deviceSections)) 	{ print '<div class="checkbox" style="margin:0px;"><input type="checkbox" name="section-'. $section->id .'" value="on" checked> '. $section->name .'</div>'. "\n"; }
				else 											{ print '<div class="checkbox" style="margin:0px;"><input type="checkbox" name="section-'. $section->id .'" value="on">'. $section->name .'</span></div>'. "\n"; }
			}
		}
		?>
		</td>
	</tr>

	</table>
	</form>
</div>

<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<button class="btn btn-sm btn-default <?php if($_POST['action']=="delete") { print "btn-danger"; } else { print "btn-success"; } ?>" id="editSwitchsubmit"><i class="fa <?php if($_POST['action']=="add") { print "fa-plus"; } else if ($_POST['action']=="delete") { print "fa-trash-o"; } else { print "fa-check"; } ?>"></i> <?php print escape_input(ucwords(_($_POST['action']))); ?></button>
	</div>

	<!-- result -->
	<div class="switchManagementEditResult"></div>
</div>