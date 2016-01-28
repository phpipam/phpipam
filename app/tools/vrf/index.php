<?php

/**
 * Script to display all VRFs
 *
 */

# verify that user is logged in
$User->check_user_session();

# fetch all VRFs
$vrfs = $Tools->fetch_all_objects("vrf", "vrfId");


# title
print "<h4>"._('Available VRFs and belonging subnets')."</h4>";
print "<hr>";
if($User->isadmin) {
	print "<a class='btn btn-sm btn-default' href='".create_link("administration","vrfs")."' data-action='add'  data-switchid=''><i class='fa fa-pencil'></i> ". _('Manage')."</a>";
}


/* for each VRF check which subnet has it configured */
if($vrfs===false) {
	$Result->show("info", _('No VRFs configured'), false);
}
else {
	# print table
	print "<table id='vrf' class='table table-striped table-condensed table-top'>";

	# loop
	foreach ($vrfs as $vrf) {
		# cast
		$vrf = (array) $vrf;

		# print table body
		print "<tbody>";

		# vrf name and details
		print "<tr class='vrf-title'>";
	    print "	<th colspan='8'><h4>$vrf[name]</h4></th>";
		print "</tr>";

		# sections
		print "<tr class='text-top'>";
	    print "	<td colspan='8'>";
	    print _("Available in sections")." ";
            $vrf_sections = array_filter(explode(";", $vrf['sections']));
            if (sizeof($vrf_sections)==0)   {
                print "<span class='badge badge1'>"._("All sections")."</span>";
            }
            else {
                foreach ($vrf_sections as $s) {
                    $tmp_section = $Sections->fetch_section(null, $s);
                    print "<span class='badge badge1'><a href='".create_link("subnets",$tmp_section->id)."'>".$tmp_section->name."</a></span> ";
                }
            }
	    print " </td>";
		print "</tr>";

		# fetch subnets in vrf
		$subnets = $Subnets->fetch_vrf_subnets ($vrf['vrfId'], null);

		# headers
		print "	<tr>";
		print "	<th>"._('VLAN')."</th>";
		print "	<th>"._('Description')."</td>";
		print "	<th>"._('Section')."</td>";
		print "	<th>"._('Subnet')."</td>";
		print "	<th>"._('Master Subnet')."</td>";
		print "	<th class='hidden-xs hidden-sm'>"._('Requests')."</td>";
		print "</tr>";

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
					$subnet['VLAN'] = (empty($subnet['VLAN']) || !$subnet['VLAN']) ? "" : $subnet['VLAN']->number;

					# get section name
					$section = (array) $Sections->fetch_section(null, $subnet['sectionId']);

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

					# allow requests
					if($subnet['allowRequests'] == 1) 	{ print '<td class="allowRequests requests hidden-xs hidden-sm">'._('enabled').'</td>'; }
					else 								{ print '<td class="allowRequests hidden-xs hidden-sm"></td>'; }

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
	}
}
print "</table>";

?>