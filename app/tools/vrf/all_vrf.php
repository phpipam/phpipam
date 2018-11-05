<?php

/**
 * Script to display all VRFs
 *
 */

# verify that user is logged in
$User->check_user_session();
# perm check
$User->check_module_permissions ("vrf", 1, true, false);

# fetch all VRFs
$vrfs = $Tools->fetch_all_objects("vrf", "vrfId");

# title
print "<h4>"._('Available VRFs and belonging subnets')."</h4>";
print "<hr>";
?>

<?php if($User->get_module_permissions ("vrf")>2) { ?>
<div class="btn-group" style='margin-bottom:10px;'>
    <button class='btn btn-sm btn-default open_popup' data-script='app/admin/vrfs/edit.php' data-class='700' data-action='add'><i class='fa fa-plus'></i> <?php print _('Add VRF'); ?></button>
</div>
<?php } ?>

<?php
/* for each VRF check which subnet has it configured */
if($vrfs===false) {
	$Result->show("info", _('No VRFs configured'), false);
}
else {
	# loop
	foreach ($vrfs as $k=>$vrf) {
		# cast
		$vrf = (array) $vrf;

		// print name
		print "<br><br>";
		print "<h4><a href='".create_link("tools", "vrf", $vrf['vrfId'])."'><span class='badge badge1' style='font-size:20px;'>$vrf[name]</span></a></h4>";

		// customers
		if($User->settings->enableCustomers=="1" && $User->get_module_permissions ("customers")>0) {
			 $customer = $Tools->fetch_object ("customers", "id", $vrf['customer_id']);
			 print $customer===false ? "" : "<span class='text-muted'>"._("Customer")." ".$customer->title." <a target='_blank' href='".create_link("tools","customers",$customer->title)."'><i class='fa fa-external-link'></i></a></span>";
		}

		# print table
		print "<table id='vrf' class='table sorted table-striped table-condensed table-top' data-cookie-id-table='tools_vrf_$vrf[name]'>";

		# headers
		print "<thead>";
		print "	<tr>";
		if($User->get_module_permissions ("vlan")>0)
		print "	<th>"._('VLAN')."</th>";
		print "	<th>"._('Description')."</th>";
		print "	<th>"._('Section')."</th>";
		print "	<th>"._('Subnet')."</th>";
		print "	<th>"._('Master Subnet')."</th>";
		print "</tr>";
		print "</thead>";

		# sections
		print "<tbody>";

		# in sections
		print "<tr class='text-top'>";
	    print "	<td colspan='8' class='th'>";
	    print _("Available in sections")." ";
            $vrf_sections = array_filter(explode(";", $vrf['sections']));
            if (sizeof($vrf_sections)==0)   {
                print "<span class='badge badge1'>"._("All sections")."</span>";
            }
            else {
                foreach ($vrf_sections as $s) {
                    $tmp_section = $Sections->fetch_section(null, $s);
                    if($tmp_section!==false) {
	                    print "<span class='badge badge1'><a href='".create_link("subnets",$tmp_section->id)."'>".$tmp_section->name."</a></span> ";
    				}
                }
            }
	    print " </td>";
		print "</tr>";

		# fetch subnets in vrf
		$subnets = $Subnets->fetch_vrf_subnets ($vrf['vrfId'], null);

		# subnets
		if($subnets) {
			# count
			$subnet_allowed = 0;

			foreach ($subnets as $subnet) {
				# cast
				$subnet = (array) $subnet;

				# check permission
				$permission = $Subnets->check_permission ($User->user, $subnet['id']);

				# permission
				if($permission > 0) {
					$subnet_allowed++;

					# check if it is master
					$masterSubnet = ($subnet['masterSubnetId'] == 0)||(empty($subnet['masterSubnetId'])) ? true : false;

					print "<tr>";

					# get VLAN details
					$subnet['VLAN'] = $Tools->fetch_object("vlans", "vlanId", $subnet['vlanId']);
					$subnet['VLAN'] = (empty($subnet['VLAN']) || !$subnet['VLAN']) ? "/" : $subnet['VLAN']->number;
					$subnet['description'] = strlen($subnet['description']==0) ? "/" : $subnet['description'];

					# get section name
					$section = (array) $Sections->fetch_section(null, $subnet['sectionId']);

					if($User->get_module_permissions ("vlan")>0)
					print "	<td>$subnet[VLAN]</td>";
					print "	<td>$subnet[description]</td>";
					print "	<td><a href='".create_link("subnets",$section['id'])."'>$section[name]</a></td>";

					# folder?
					if($subnet->isFolder==1) {
						print "	<td><a href='".create_link("folder",$section['id'],$subnet['id'])."'>$subnet[description]</a></td>";
					}
					else {
						print "	<td><a href='".create_link("subnets",$section['id'],$subnet['id'])."'>".$Subnets->transform_to_dotted($subnet['subnet'])."/$subnet[mask]</a></td>";
					}

					if($masterSubnet) {
						print '	<td>/</td>' . "\n";
					}
					else {
						$master = (array) $Subnets->fetch_subnet (null, $subnet['masterSubnetId']);
						# folder
						if($master['isFolder']==1)		{ print "	<td><i class='fa fa-folder fa-gray'></i> <a href='".create_link("folder",$subnet['sectionId'],$subnet['masterSubnetId'])."'>$master[description]</a></td>"; }
						# orphaned
						elseif(strlen(@$master['subnet']) == 0)	{ print "	<td>".$Result->show('warning', _('Master subnet does not exist')."!", false, false, true)."</td>";}
						# folder
						elseif($master['isFolder']==1)		{ print "	<td><i class='fa fa-folder fa-gray'></i> <a href='".create_link("folder",$subnet['sectionId'],$subnet['masterSubnetId'])."'>$master[description]</a></td>"; }
						else 								{ print "	<td><a href='".create_link("subnets",$subnet['sectionId'],$subnet['masterSubnetId'])."'>".$Subnets->transform_to_dotted($master['subnet'])."/$master[mask] ($master[description])</a></td>"; }
					}

					print '</tr>' . "\n";
				}
			}

			if ($subnet_allowed==0) {
				// none available
				print '<tr>'. "\n";
				print '<td colspan="8">';
				$Result->show("info", _('No subnets available')."!", false);
				print '</td>'. "\n";
				print '</tr>'. "\n";
			}
		}
		# no subnets!
		else {
			print '<tr>'. "\n";
			print '<td colspan="8">';
			$Result->show("info", _('No subnets available')."!", false);
			print '</td>'. "\n";
			print '</tr>'. "\n";
		}
		# end
		print '</tbody>';
		print "</table>";
	}
}