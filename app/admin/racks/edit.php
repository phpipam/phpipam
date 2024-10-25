<?php

/**
 *	Edit rack details
 ************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database, false);
$Tools	 	= new Tools ($Database);
$Racks      = new phpipam_rack ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();
# perm check popup
if($POST->action=="edit") {
    $User->check_module_permissions ("racks", User::ACCESS_RW, true, true);
}
else {
    $User->check_module_permissions ("racks", User::ACCESS_RWA, true, true);
}

# create csrf token
$csrf = $User->Crypto->csrf_cookie ("create", "rack");

# validate action
$Admin->validate_action();

# fetch custom fields
$custom = $Tools->fetch_custom_fields('racks');

# ID must be numeric
if($POST->action!="add" && !is_numeric($POST->rackid))		{ $Result->show("danger", _("Invalid ID"), true, true); }

# fetch device details
if( ($POST->action == "edit") || ($POST->action == "delete") ) {
	$rack = $Admin->fetch_object("racks", "id", $POST->rackid);
}
else {
    $rack = new StdClass ();
    $rack->size = 42;
    $rack->topDown = 1;
}

# fetch all racks
$Racks->fetch_all_racks();

# all locations
if($User->settings->enableLocations=="1")
$locations = $Tools->fetch_all_objects ("locations", "name");

# set readonly flag
$readonly = $POST->action=="delete" ? "readonly" : "";
?>

<script>
$(document).ready(function(){
     if ($("[rel=tooltip]").length) { $("[rel=tooltip]").tooltip(); }

	/* bootstrap switch */
	var switch_options = {
	    onColor: 'default',
	    offColor: 'default',
	    onText: 'Yes',
	    offText: 'No',
	    size: "mini"
	};
	$(".input-switch").bootstrapSwitch(switch_options);
});
</script>


<!-- header -->
<div class="pHeader"><?php print $User->get_post_action(); ?> <?php print _('rack'); ?></div>


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

	<!-- Size -->
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

	<!-- Front -->
	<tr>
		<td><?php print _('Back side'); ?></td>
		<td>
			<?php $checked = @$rack->hasBack=="1" ? "checked" : ""; ?>
			<input type="checkbox" name="hasBack" class="input-switch" value="1" <?php print $checked; ?>>
		</td>
	</tr>

    <!-- Orientation -->
    <tr>
        <td><?php print _('Orientation'); ?></td>
        <td>
            <select name="topDown" class="form-control input-sm input-w-auto">
                <option value="1"<?php if ($rack->topDown) print " selected" ?>><?php print _("Top-down (unit 1 at the top)"); ?></option>
                <option value="0"<?php if (!$rack->topDown) print " selected" ?>><?php print _("Bottom-up (unit 1 at the bottom)"); ?></option>
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
        				if($rack->location == $l->id)	{ print "<option value='$l->id' selected='selected'>$l->name</option>"; }
        				else					{ print "<option value='$l->id'>$l->name</option>"; }
        			}
    			}
    			?>
			</select>
		</td>
	</tr>
	<?php } ?>

	<?php
    // customers
    if($User->settings->enableCustomers==1 && $User->get_module_permissions ("customers")>=User::ACCESS_R) {
        // fetch customers
        $customers = $Tools->fetch_all_objects ("customers", "title");
        // print
        print '<tr>' . "\n";
        print ' <td class="middle">'._('Customer').'</td>' . "\n";
        print ' <td>' . "\n";
        print ' <select name="customer_id" class="form-control input-sm input-w-auto">'. "\n";

        //blank
        print '<option disabled="disabled">'._('Select Customer').'</option>';
        print '<option value="0">'._('None').'</option>';

        if($customers!=false) {
            foreach($customers as $customer) {
                if ($customer->id == $rack->customer_id)    { print '<option value="'. $customer->id .'" selected>'.$customer->title.'</option>'; }
                else                                        { print '<option value="'. $customer->id .'">'.$customer->title.'</option>'; }
            }
        }

        print ' </select>'. "\n";
        print ' </td>' . "\n";
        print '</tr>' . "\n";
    }
	?>

	<!-- Description -->
	<tr>
		<td><?php print _('Description'); ?></td>
		<td>
			<textarea name="description" class="form-control input-sm" placeholder="<?php print _('Description'); ?>" <?php print $readonly; ?>><?php if(isset($rack->description)) print $rack->description; ?></textarea>
			<?php
			if( ($POST->action == "edit") || ($POST->action == "delete") ) {
				print '<input type="hidden" name="rackid" value="'. escape_input($POST->rackid) .'">'. "\n";
			} ?>
			<input type="hidden" name="action" value="<?php print escape_input($POST->action); ?>">
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
    		$custom_input = $Tools->create_custom_field_input ($field, $rack, $timepicker_index);
    		$timepicker_index = $custom_input['timepicker_index'];
            // print
			print "<tr>";
			print "	<td>".ucwords($Tools->print_custom_field_name ($field['name']))." ".$custom_input['required']."</td>";
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
		<a class='btn btn-sm btn-default submit_popup' data-script="app/admin/racks/edit-result.php" data-result_div="rackManagementEditResult" data-form='rackManagementEdit'>
			<i class="fa <?php if($POST->action=="add") { print "fa-plus"; } elseif ($POST->action=="delete") { print "fa-trash-o"; } else { print "fa-check"; } ?>"></i> <?php print $User->get_post_action(); ?>
		</a>

	</div>

	<!-- result -->
	<div id="rackManagementEditResult"></div>
</div>