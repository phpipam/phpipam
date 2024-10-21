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
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();
# perm check popup
if($POST->action=="edit") {
    $User->check_module_permissions ("vlan", User::ACCESS_RW, true, true);
}
else {
    $User->check_module_permissions ("vlan", User::ACCESS_RWA, true, true);
}

# create csrf token
$csrf = $User->Crypto->csrf_cookie ("create", "vlan");

# validate action
$Admin->validate_action();

# fetch vlan details
$vlan = $Admin->fetch_object ("vlans", "vlanid", $POST->vlanid);
if (!is_object($vlan)) {
	$vlan  = new Params;
}
# fetch custom fields
$custom = $Tools->fetch_custom_fields('vlans');

# set readonly flag
$readonly = $POST->action=="delete" ? "readonly" : "";

# set form name!
if(isset($POST->fromSubnet)) { $formId = "vlanManagementEditFromSubnet"; }
else 							{ $formId = "vlanManagementEdit"; }

# domain
if(!isset($POST->domain)) 	{ $POST->domain=1; }

# fetch l2 domain
if($POST->action=="add") {
	# all
	if ($POST->domain=="all") {
		$vlan_domains = $Admin->fetch_all_objects("vlanDomains");
	} else {
		$vlan_domain = $Admin->fetch_object("vlanDomains", "id", $POST->domain);
	}
	if(isset($POST->number)) {
		if (!is_numeric($POST->number)) {
			$Result->show("danger", _("Invalid ID"), true, true);
		}
		$vlan->number = $POST->number;
	}
} else {
		$vlan_domain = $Admin->fetch_object("vlanDomains", "id", $vlan->domainId);
}
if($vlan_domain===false)			{ $Result->show("danger", _("Invalid ID"), true, true); }
?>

<script>
$(document).ready(function(){
     if ($("[rel=tooltip]").length) { $("[rel=tooltip]").tooltip(); }
});
</script>


<!-- header -->
<div class="pHeader"><?php print $User->get_post_action(); ?> <?php print _('VLAN'); ?></div>

<!-- content -->
<div class="pContent">
	<form id="<?php print $formId; ?>">

	<table id="vlanManagementEdit2" class="table table-noborder table-condensed">
	<!-- domain -->
	<tr>
		<td><?php print _('l2 domain'); ?></td>
		<th>
		<?php
		# not all
		if ($POST->domain!="all") {
			print $vlan_domain->name." (".$vlan_domain->description.")";
		} else {
			print "<select name='domainid' class='form-control input-sm'>";
			foreach ($vlan_domains as $d) {
				print "<option value='$d->id'>$d->name</option>";
			}
			print "</select>";
		}
		?>
		</th>
	</tr>
	<tr>
		<td colspan="2"><hr></td>
	</tr>
	<!-- number -->
	<tr>
		<td><?php print _('Number'); ?></td>
		<td>
			<input type="text" class="number form-control input-sm" name="number" placeholder="<?php print _('VLAN number'); ?>" value="<?php print $vlan->number; ?><?php print escape_input($POST->vlanNum); ?>" <?php print $readonly; ?>>
		</td>
	</tr>

	<!-- hostname  -->
	<tr>
		<td><?php print _('Name'); ?></td>
		<td>
			<input type="text" class="name form-control input-sm" name="name" placeholder="<?php print _('VLAN name'); ?>" value="<?php print $vlan->name; ?>" <?php print $readonly; ?>>
		</td>
	</tr>

	<!-- Description -->
	<tr>
		<td><?php print _('Description'); ?></td>
		<td>
			<input type="text" class="description form-control input-sm" name="description" placeholder="<?php print _('Description'); ?>" value="<?php print $vlan->description; ?>" <?php print $readonly; ?>>
			<input type="hidden" name="vlanid" value="<?php print escape_input($POST->vlanid); ?>">
			<?php if($POST->domain!=="all") { ?>
			<input type="hidden" name="domainid" value="<?php print $vlan_domain->id; ?>">
			<?php } ?>
			<input type="hidden" name="action" value="<?php print escape_input($POST->action); ?>">
			<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
		</td>
	</tr>


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
                if ($customer->id == $vlan->customer_id)    { print '<option value="'. $customer->id .'" selected>'.$customer->title.'</option>'; }
                else                                          { print '<option value="'. $customer->id .'">'.$customer->title.'</option>'; }
            }
        }

        print ' </select>'. "\n";
        print ' </td>' . "\n";
        print '</tr>' . "\n";
    }
	?>


	<?php if($POST->action=="add" || $POST->action=="edit") { ?>
    <!-- require unique -->
    <tr>
	    <td colspan="2"><hr></td>
    </tr>
    <tr>
    	<td><?php print _('Unique VLAN'); ?></td>
    	<td>
	    	<input type="checkbox" name="unique" value="on">
	    	<span class="text-muted"><?php print _('Require unique vlan accross domains'); ?></span>
	    </td>
    </tr>
	<?php } ?>

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
    		$custom_input = $Tools->create_custom_field_input ($field, $vlan, $timepicker_index);
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

	<?php
	//print delete warning
	if($POST->action == "delete")	{ $Result->show("warning", _('Warning').':</strong> '._('removing VLAN will also remove VLAN reference from belonging subnets')."!", false);  }
	?>
</div>


<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default <?php if(isset($POST->fromSubnet)) { print "hidePopup2"; } else { print "hidePopups"; } ?>"><?php print _('Cancel'); ?></button>
		<button class="btn btn-sm btn-default <?php if($POST->action=="delete") { print "btn-danger"; } else { print "btn-success"; } ?> vlanManagementEditFromSubnetButton" id="editVLANsubmit"><i class="fa <?php if($POST->action=="add") { print "fa-plus"; } elseif ($POST->action=="delete") { print "fa-trash-o"; } else { print "fa-check"; } ?>"></i> <?php print $User->get_post_action(); ?></button>
	</div>

	<!-- result -->
	<div class="<?php print $formId; ?>Result"></div>
</div>