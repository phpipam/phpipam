<?php

/**
 *	Print all available VRFs and configurations
 ************************************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database, false);
$Tools	 	= new Tools ($Database);
$Sections	= new Sections ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();
# perm check popup
if($_POST['action']=="edit") {
    $User->check_module_permissions ("vrf", 2, true, true);
}
else {
    $User->check_module_permissions ("vrf", 3, true, true);
}

# create csrf token
$csrf = $User->Crypto->csrf_cookie ("create", "vrf");

# validate action
$Admin->validate_action ($_POST['action'], true);

# get VRF
if($_POST['action']!="add") {
	$vrf = $Admin->fetch_object ("vrf", "vrfid", $_POST['vrfid']);
	$vrf!==false ? : $Result->show("danger", _("Invalid ID"), true, true);
	$vrf = (array) $vrf;
}

# disable edit on delete
$readonly = $_POST['action']=="delete" ? "readonly" : "";

# fetch custom fields
$custom = $Tools->fetch_custom_fields('vrf');
?>


<!-- header -->
<div class="pHeader"><?php print ucwords(_("$_POST[action]")); ?> <?php print _('VRF'); ?></div>

<!-- content -->
<div class="pContent">

	<form id="vrfManagementEdit">
	<table id="vrfManagementEdit2" class="table table-noborder table-condensed">

	<!-- name  -->
	<tr>
		<td><?php print _('Name'); ?></td>
		<td>
			<input type="text" class="name form-control input-sm" name="name" placeholder="<?php print _('VRF name'); ?>" value="<?php print $Tools->strip_xss(@$vrf['name']); ?>" <?php print $readonly; ?>>
		</td>
	</tr>
	<!-- RD -->
	<tr>
		<td><?php print _('RD'); ?></td>
		<td>
			<input type="text" class="rd form-control input-sm" name="rd" placeholder="<?php print _('Route distinguisher'); ?>" value="<?php print $Tools->strip_xss(@$vrf['rd']); ?>" <?php print $readonly; ?>>
		</td>
	</tr>
	<!-- Description -->
	<tr>
		<td><?php print _('Description'); ?></td>
		<td>
			<?php
			if( ($_POST['action'] == "edit") || ($_POST['action'] == "delete") ) { print '<input type="hidden" name="vrfId" value="'. $_POST['vrfid'] .'">'. "\n";}
			?>
			<input type="hidden" name="action" value="<?php print $_POST['action']; ?>">
			<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
			<input type="text" class="description form-control input-sm" name="description" placeholder="<?php print _('Description'); ?>" value="<?php print $Tools->strip_xss(@$vrf['description']); ?>" <?php print $readonly; ?>>
		</td>
	</tr>

	<?php
    // customers
    if($User->settings->enableCustomers==1) {
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
                if ($customer->id == $vrf['customer_id'])    { print '<option value="'. $customer->id .'" selected>'.$customer->title.'</option>'; }
                else                                         { print '<option value="'. $customer->id .'">'.$customer->title.'</option>'; }
            }
        }

        print ' </select>'. "\n";
        print ' </td>' . "\n";
        print '</tr>' . "\n";
    }
	?>

	<tr>
		<td colspan="2"><hr></td>
	</tr>
	<!-- sections -->
	<tr>
		<td style="vertical-align: top !important"><?php print _('Sections'); ?>:</td>
		<td>
		<?php
		# select sections
		$sections = $Sections->fetch_all_sections();
		# reformat domains sections to array
		$vrf_sections = explode(";", @$vrf['sections']);
		$vrf_sections = is_array($vrf_sections) ? $vrf_sections : array();
		// loop
		if($sections!==false) {
			foreach($sections as $section) {
				if(in_array($section->id, @$vrf_sections)) 	{ print '<div style="margin:0px;"><input type="checkbox" name="section-'. $section->id .'" value="on" checked> '. $section->name .'</div>'. "\n"; }
				else 										{ print '<div style="margin:0px;"><input type="checkbox" name="section-'. $section->id .'" value="on"> '. $section->name .'</div>'. "\n"; }
			}
		}
		?>
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
    		$custom_input = $Tools->create_custom_field_input ($field, $vrf, $_POST['action'], $timepicker_index);
    		// add datepicker index
    		$timepicker_index = $timepicker_index + $custom_input['timepicker_index'];
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

	<?php
	//print delete warning
	if($_POST['action'] == "delete")	{ $Result->show("warning", "<strong>"._('Warning').":</strong> "._("removing VRF will also remove VRF reference from belonging subnets!"), false);}
	?>
</div>


<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<button class="btn btn-sm btn-default <?php if($_POST['action']=="delete") { print "btn-danger"; } else { print "btn-success"; } ?>" id="editVRF"><i class="fa <?php if($_POST['action']=="add") { print "fa-plus"; } else if ($_POST['action']=="delete") { print "fa-trash-o"; } else { print "fa-check"; } ?>"></i> <?php print ucwords(_($_POST['action'])); ?></button>
	</div>
	<!-- result -->
	<div class="vrfManagementEditResult"></div>
</div>