<?php

/**
 *	Edit provider details
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
if($POST->action=="edit") {
    $User->check_module_permissions ("circuits", User::ACCESS_RW, true, true);
}
else {
    $User->check_module_permissions ("circuits", User::ACCESS_RWA, true, true);
}

# create csrf token
$csrf = $User->Crypto->csrf_cookie ("create", "provider");

# validate action
$Admin->validate_action();

# fetch custom fields
$custom = $Tools->fetch_custom_fields('circuitProviders');

# ID must be numeric
if($POST->action!="add" && !is_numeric($POST->providerid))	{ $Result->show("danger", _("Invalid ID"), true, true); }

# fetch provider details
if( ($POST->action == "edit") || ($POST->action == "delete") ) {
	$provider = $Admin->fetch_object("circuitProviders", "id", $POST->providerid);
	// false
	if ($provider===false)                                          { $Result->show("danger", _("Invalid ID"), true, true);  }
}
// defaults
else {
	$provider = new Params ();
}

# set readonly flag
$readonly = $POST->action=="delete" ? "readonly" : "";
?>

<script>
$(document).ready(function(){
     if ($("[rel=tooltip]").length) { $("[rel=tooltip]").tooltip(); }
});
</script>


<!-- header -->
<div class="pHeader"><?php print $User->get_post_action(); ?> <?php print _('Circuit provider'); ?></div>


<!-- content -->
<div class="pContent">

	<form id="providerManagementEdit">
	<table class="table table-noborder table-condensed">

	<!-- name -->
	<tr>
		<td><?php print _('Name'); ?></td>
		<td>
			<input type="text" name="name" class="form-control input-sm" placeholder="<?php print _('Name'); ?>" value="<?php if(isset($provider->name)) print $provider->name; ?>" <?php print $readonly; ?>>
			<?php
			if( ($POST->action == "edit") || ($POST->action == "delete") ) {
				print '<input type="hidden" name="providerid" value="'. escape_input($POST->providerid) .'">'. "\n";
			} ?>
			<input type="hidden" name="action" value="<?php print escape_input($POST->action); ?>">
			<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
		</td>
	</tr>

	<!-- description -->
	<tr>
		<td><?php print _('Description'); ?></td>
		<td>
			<input type="text" name="description" class="form-control input-sm" placeholder="<?php print _('Description'); ?>" value="<?php if(isset($provider->description)) print $provider->description; ?>" <?php print $readonly; ?>>
		</td>
	</tr>

	<!-- Contact -->
	<tr>
		<td><?php print _('Contact'); ?></td>
		<td>
			<input type="text" name="contact" class="form-control input-sm" placeholder="<?php print _('Contact'); ?>" value="<?php if(isset($provider->contact)) print $provider->contact; ?>" <?php print $readonly; ?>>
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
			// readonly
			$disabled = $readonly == "readonly" ? true : false;
    		// create input > result is array (required, input(html), timepicker_index)
    		$custom_input = $Tools->create_custom_field_input ($field, $provider, $timepicker_index, $disabled);
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
		<button class="btn btn-sm btn-default submit_popup <?php if($POST->action=="delete") { print "btn-danger"; } else { print "btn-success"; } ?>" data-script="app/admin/circuits/edit-provider-submit.php" data-result_div="providerManagementEditResult" data-form='providerManagementEdit'>
			<i class="fa <?php if($POST->action=="add") { print "fa-plus"; } elseif ($POST->action=="delete") { print "fa-trash-o"; } else { print "fa-check"; } ?>"></i>
			<?php print $User->get_post_action(); ?>
		</button>
	</div>

	<!-- result -->
	<div class='providerManagementEditResult' id="providerManagementEditResult"></div>
</div>
