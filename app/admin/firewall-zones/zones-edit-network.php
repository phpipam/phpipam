<?php

/**
 *	firewall zone fwzones-edit.php
 *	add, edit and delete firewall zones
 ******************************************/

# functions
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize classes
$Database = new Database_PDO;
$User 	  = new User ($Database);
$Admin 	  = new Admin($Database);
$Subnets  = new Subnets ($Database);
$Sections = new Sections ($Database);
$Result   = new Result ();
$Zones    = new FirewallZones($Database);

# validate session parameters
$User->check_user_session();

# validate $POST->action values
if ($POST->action != 'add' && $POST->action != 'delete') 	{ $Result->show("danger", _("Invalid action. Do not manipulate the POST values!").'<button class="btn btn-sm btn-default hidePopup2">'._('Cancel').'</button>', true); }
# validate $POST->id values
if ($POST->id && !preg_match('/^[0-9]+$/i', $POST->id)) 	{ $Result->show("danger", _("Invalid ID. Do not manipulate the POST values!").'<button style="margin-left:50px;" class="btn btn-sm btn-default hidePopup2">'._('Cancel').'</button>', true); }
# validate $POST->sectionId values
if ($POST->id && $POST->subnetId != '') {
	if (!preg_match('/^[0-9]+$/i', $POST->subnetId)) 		{ $Result->show("danger", _("Invalid subnet ID. Do not manipulate the POST values!").'<button class="btn btn-sm btn-default hidePopup2">'._('Cancel').'</button>', true); }
}

# fetch all sections
$sections = $Sections->fetch_all_sections();

?>

<script>
$(document).ready(function() {
	/* bootstrap switch */
	var switch_options = {
	    onColor: 'default',
	    offColor: 'default',
	    size: "mini"
	};
	$(".input-switch").bootstrapSwitch(switch_options);
});
</script>

<!-- header  -->
<div class="pHeader"><?php print $User->get_post_action().' '._('network mapping'); ?></div>
<!-- content -->
<div class="pContent">
<!-- form -->
<form id="networkEdit">
<!-- table -->
<table class="table table-noborder table-condensed">
<?php
	if ($POST->action == 'delete') { ?>
		<!-- delete warning and network information-->
		<tr>
			<td style="width:150px;">
				<?php
				$subnet = $Subnets->fetch_subnet('id',$POST->subnetId);
				# display network information with or without description
				if ($subnet->description) 	{	$network = $Subnets->transform_to_dotted($subnet->subnet).'/'.$subnet->mask.' ('.$subnet->description.')';	}
				else 						{	$network = $Subnets->transform_to_dotted($subnet->subnet).'/'.$subnet->mask;	}
				$Result->show("warning", "<strong>"._('Warning').":</strong><br>"._("You are about to remove the following Network from the firewall zone:<br>".$network), false); ?>
				<input type="hidden" name="masterSubnetId" value="<?php print escape_input($POST->subnetId); ?>">
			</td>
	<?php } else {
		# add a network to the zone
		?>
	<tr>
		<td colspan="2">
			<?php print _('First select a section to choose a subnet afterwards.'); ?>
		</td>
	</tr>
	<tr>
		<!-- section  -->
		<td style="width:150px;">
			<?php print _('Section'); ?>
		</td>
		<td>
			<select name="sectionId" class="firewallZoneSection form-control input-sm input-w-auto input-max-200">
			<?php
			if(sizeof($sections)>1){
				print '<option value="0">'._('No section selected').'</option>';
			}
			foreach ($sections as $section) {
				if($section->description) 	{	print '<option value="'.$section->id.'">'. $section->name.' ('.$section->description.')</option>'; }
				else 						{	print '<option value="'.$section->id.'">'. $section->name.'</option>'; }}
			?>
			</select>
		</td>
	</tr>
	<tr>
		<!-- subnet -->
		<td>
			<?php print _('Subnet'); ?>
		</td>
			<?php
			# display the subnet if already configured
			if ($POST->sectionId) {
				print '<td><div class="sectionSubnets">';
				print $Subnets->print_mastersubnet_dropdown_menu($POST->sectionId,$POST->subnetId);
				print '</div></td>';
			} else {
				# if there is only one section, fetch the subnets of that section
				if(sizeof($sections)<=1){
					print '<td>';
					print $Subnets->print_mastersubnet_dropdown_menu($sections[0]->id,$firewallZone->subnetId);
					print '</td>';
				} else {
					# if there are more than one section, use ajax to fetch the subnets of the selected section
					print '<td><div class="sectionSubnets"></div></td>';
				}
			}
			?>
	</tr>

<?php } ?>
</table>
<input type="hidden" name="action" value="<?php print escape_input($POST->action); ?>">
<?php
if ($POST->id) 	{ print '<input type="hidden" name="netZoneId" value="'.escape_input($POST->id).'">'; }
else 				{ print '<input type="hidden" name="noZone" value="1">';
						if (is_array($POST->network)) {
					  		foreach ($POST->network as $key => $network) {
					    		print '<input type="hidden" name="network['.escape_input($key).']" value="'.escape_input($network).'">';
					    	}
					    }
					}
?>
</form>


</div>
<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopup2"><?php print _('Cancel'); ?></button>
		<button class="btn btn-sm btn-default <?php if($POST->action=="delete") { print "btn-danger"; } else { print "btn-success"; } ?>" id="editNetworkSubmit"><i class="fa <?php if($POST->action=="add") { print "fa-plus"; } elseif ($POST->action=="delete") { print "fa-trash-o"; } else { print "fa-check"; } ?>"></i> <?php print $User->get_post_action(); ?></button>
	</div>
	<!-- result -->
	<div class="zones-edit-network-result"></div>
</div>