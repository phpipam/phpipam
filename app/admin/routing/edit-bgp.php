<?php

/*
 * Print edit sections form
 *************************************************/

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

# create csrf token
$csrf = $User->Crypto->csrf_cookie ("create", "routing_bgp");

# perm check popup
if($POST->action=="edit") {
    $User->check_module_permissions ("routing", User::ACCESS_RW, true, true);
}
else {
    $User->check_module_permissions ("routing", User::ACCESS_RWA, true, true);
}

# validate action
$Admin->validate_action();

# fetch custom fields
$custom = $Tools->fetch_custom_fields('routing_bgp');

# ID must be numeric
if($POST->action!="add" && !is_numeric($POST->bgpid))		{ $Result->show("danger", _("Invalid ID"), true, true); }

# fetch bgp details
if( ($POST->action == "edit") || ($POST->action == "delete") ) {
	$bgp = $Admin->fetch_object("routing_bgp", "id", $POST->bgpid);
	// false
	if ($bgp===false)                                            { $Result->show("danger", _("Invalid ID"), true, true);  }
}
// defaults
else {
	$bgp = new StdClass();
}

// set readonly flag
$readonly = $POST->action=="delete" ? "readonly" : "";
?>

<!-- header -->
<div class="pHeader"><?php print $User->get_post_action(); ?> <?php print _('BGP peer'); ?></div>


<!-- content -->
<div class="pContent">
	<form id="BGPEdit">
	<table class="table table-noborder table-condensed">

	<!-- name -->
	<tr>
		<td style='width:130px;'><?php print _('Peer name'); ?></td>
		<td>
			<input type="text" name="peer_name" class="form-control input-sm" placeholder="<?php print _('Peer name'); ?>" value="<?php if(isset($bgp->peer_name)) print $Tools->strip_xss($bgp->peer_name); ?>" <?php print $readonly; ?>>
			<?php
			if( ($POST->action == "edit") || ($POST->action == "delete") ) {
				print '<input type="hidden" name="id" value="'. escape_input($POST->bgpid) .'">'. "\n";
			} ?>
			<input type="hidden" name="action" value="<?php print escape_input($POST->action); ?>">
			<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
		</td>
	</tr>

	<!-- type -->
	<tr>
		<td><?php print _('BGP type'); ?></td>
		<td>
			<select name="bgp_type" class="form-control input-w-auto input-sm">
				<?php
				foreach (["internal", "external"] as $type) {
					$selected = isset($bgp->bgp_type) && $bgp->bgp_type == $type ? "selected" : "";
					print "<option value='$type' $selected>$type</option>";
				}
				?>
			</select>
		</td>
	</tr>

	<?php
    // customers
    if($User->settings->enableVRF==1 && $User->get_module_permissions ("vrf")>=User::ACCESS_R) {
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
                if (isset($bgp->customer_id) && $customer->id == $bgp->customer_id)    	{ print '<option value="'. $customer->id .'" selected>'.$customer->title.'</option>'; }
                else                                         { print '<option value="'. $customer->id .'">'.$customer->title.'</option>'; }
            }
        }

        print ' </select>'. "\n";
        print ' </td>' . "\n";
        print '</tr>' . "\n";
    }

    // circuits
    if($User->settings->enableCircuits==1 && $User->get_module_permissions ("circuits")>=User::ACCESS_R) {
        // fetch customers
        $circuits = $Tools->fetch_all_objects ("circuits", "cid");
        // print
        print '<tr>' . "\n";
        print ' <td class="middle">'._('Circuit').'</td>' . "\n";
        print ' <td>' . "\n";
        print ' <select name="circuit_id" class="form-control input-sm input-w-auto">'. "\n";

        //blank
        print '<option disabled="disabled">'._('Select Circuit').'</option>';
        print '<option value="0">'._('None').'</option>';

        if($circuits!=false) {
            foreach($circuits as $circuit) {
                if (isset($bgp->circuit_id) && $circuit->id == $bgp->circuit_id)    	{ print '<option value="'. $circuit->id .'" selected>'.$circuit->cid.'</option>'; }
                else                                        { print '<option value="'. $circuit->id .'">'.$circuit->cid.'</option>'; }
            }
        }

        print ' </select>'. "\n";
        print ' </td>' . "\n";
        print '</tr>' . "\n";
    }

    // circuits
    if($User->settings->enableVRF==1 && $User->get_module_permissions ("vrf")>=User::ACCESS_R) {
        // fetch customers
        $vrfs = $Tools->fetch_all_objects ("vrf", "name");
        // print
        print '<tr>' . "\n";
        print ' <td class="middle">'._('VRF').'</td>' . "\n";
        print ' <td>' . "\n";
        print ' <select name="vrf_id" class="form-control input-sm input-w-auto">'. "\n";

        //blank
        print '<option disabled="disabled">'._('Select VRF').'</option>';
        print '<option value="0">'._('None').'</option>';

        if($vrfs!=false) {
            foreach($vrfs as $vrf) {
                if (isset($bgp->vrf_id) && $vrf->vrfId == $bgp->vrf_id)    { print '<option value="'. $vrf->vrfId .'" selected>'.$vrf->name.'</option>'; }
                else                                { print '<option value="'. $vrf->vrfId .'">'.$vrf->name.'</option>'; }
            }
        }
        print ' </select>'. "\n";
        print ' </td>' . "\n";
        print '</tr>' . "\n";
    }
	?>


	<!-- Peer -->
	<tr>
		<td colspan="2"><hr></td>
	</tr>
	<tr>
		<td><?php print _('Peer Address'); ?></td>
		<td>
			<input type="text" name="peer_address" class="form-control input-sm" placeholder="<?php print _('Peer address'); ?>" value="<?php if(isset($bgp->peer_address)) print $Tools->strip_xss($bgp->peer_address); ?>" <?php print $readonly; ?>>
		</td>
	</tr>
	<tr>
		<td><?php print _('Peer AS'); ?></td>
		<td>
			<input type="text" name="peer_as" class="form-control input-sm" placeholder="<?php print _('Peer AS'); ?>" value="<?php if(isset($bgp->peer_as)) print $Tools->strip_xss($bgp->peer_as); ?>" <?php print $readonly; ?>>
		</td>
	</tr>

	<!-- Local -->
	<tr>
		<td colspan="2"><hr></td>
	</tr>
	<tr>
		<td><?php print _('Local Address'); ?></td>
		<td>
			<input type="text" name="local_address" class="form-control input-sm" placeholder="<?php print _('Local address'); ?>" value="<?php if(isset($bgp->local_address)) print $Tools->strip_xss($bgp->local_address); ?>" <?php print $readonly; ?>>
		</td>
	</tr>
	<tr>
		<td><?php print _('Local AS'); ?></td>
		<td>
			<input type="text" name="local_as" class="form-control input-sm" placeholder="<?php print _('Local AS'); ?>" value="<?php if(isset($bgp->local_as)) print $Tools->strip_xss($bgp->local_as); ?>" <?php print $readonly; ?>>
		</td>
	</tr>


	<!-- comment -->
	<tr>
		<td colspan="2"><hr></td>
	</tr>
	<tr>
		<td><?php print _('Description'); ?></td>
		<td>
			<textarea name="description" class="form-control input-sm" <?php print $readonly; ?>><?php if(isset($bgp->description)) print $bgp->description; ?></textarea>
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
    		$custom_input = $Tools->create_custom_field_input ($field, $bgp, $timepicker_index, $disabled);
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
		<button class="btn btn-sm btn-default submit_popup <?php if($POST->action=="delete") { print "btn-danger"; } else { print "btn-success"; } ?>" data-script="app/admin/routing/edit-bgp-submit.php" data-result_div="BGPEditResult" data-form='BGPEdit'>
			<i class="fa <?php if($POST->action=="add") { print "fa-plus"; } elseif ($POST->action=="delete") { print "fa-trash-o"; } else { print "fa-check"; } ?>"></i>
			<?php print $User->get_post_action(); ?>
		</button>
	</div>

	<!-- result -->
	<div class='BGPEditResult' id="BGPEditResult"></div>
</div>
