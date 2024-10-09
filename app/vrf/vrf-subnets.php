<?php

/**
 * Script to display all slave IP addresses and subnets in content div of subnets table!
 ***************************************************************************************/

# verify that user is logged in
$User->check_user_session();
# perm check
$User->check_module_permissions ("vlan", User::ACCESS_R, true, false);

# fetch all subnets in vrf in this section
$slaves = $Subnets->fetch_vrf_subnets ($GET->subnetId, $GET->section);

# no subnets
if(!$slaves) {
	print "<hr>";
	print "<h4>"._('VRF')." $vrf->number (".$vrf->description.") "._('has no belonging subnets')."</h4>";
}
else {
	# cast
	$vrf = (array) $vrf;
	# print title
	$slaveNum = sizeof($slaves);
	print "<h4>"._('VRF')." $vrf[number] (".$vrf['name'].") "._('has')." $slaveNum "._('belonging subnets').":</h4><hr><br>";

	# table
	print '<table class="slaves table table-striped table-condensed table-hover table-full table-top">'. "\n";

	# headers
	print "<tr>";
	print "	<th class='small description'>"._('Subnet description')."</th>";
	print "	<th>"._('Subnet')."</th>";
	print "	<th class='small hidden-xs hidden-sm'>"._('Hosts check')."</th>";
	print "	<th class='small hidden-xs hidden-sm'>"._('Used')."</th>";
	print "	<th class='small hidden-xs hidden-sm'>% "._('Free')."</th>";
	print "	<th class='small hidden-xs hidden-sm'>"._('Requests')."</th>";
	print " <th class='actions'></th>";
	print "</tr>";

	# print subnets
	foreach ($slaves as $subnet) {
		# cast
		$subnet = (array) $subnet;
		# check permission
		$permission = $Subnets->check_permission ($User->user, $subnet['id']);
		# allowed
		$m=0;
		if($permission > 0) {

            # add full information
            $fullinfo = $subnet['isFull']==1 ? " <span class='badge badge1 badge2 badge4'>"._("Full")."</span>" : "";

			print "<tr>";
		    print "	<td class='small description'><a href='".create_link("subnets",$GET->section,$subnet['id'])."'>$subnet[description]</a></td>";
		    print "	<td><a href='".create_link("subnets",$GET->section,$subnet['id'])."'>".$Subnets->transform_address($subnet['subnet'], "dotted")."/$subnet[mask] $fullinfo</a></td>";

			# host check
			if($subnet['pingSubnet'] == 1) 				{ print '<td class="allowRequests small hidden-xs hidden-sm">'._('enabled').'</td>'; }
			else 										{ print '<td class="allowRequests small hidden-xs hidden-sm"></td>'; }

			# print usage
			$calculate = $Subnets->calculate_subnet_usage ($subnet);
		    print ' <td class="small hidden-xs hidden-sm">'. $calculate['used'] .'/'. $calculate['maxhosts'] .'</td>'. "\n";
		    print '	<td class="small hidden-xs hidden-sm">'. $calculate['freehosts_percent'] .'</td>';

			# allow requests
			if($subnet['allowRequests'] == 1) 			{ print '<td class="allowRequests small hidden-xs hidden-sm">'._('enabled').'</td>'; }
			else 										{ print '<td class="allowRequests small hidden-xs hidden-sm"></td>'; }

			# edit
			if($permission == 3) {
				print "	<td class='actions'>";
				print "	<div class='btn-group'>";
				print "		<button class='btn btn-xs btn-default editSubnet'     data-action='edit'   data-subnetid='".$subnet['id']."'  data-sectionid='".$subnet['sectionId']."'><i class='fa fa-gray fa-pencil'></i></button>";
				print "		<button class='btn btn-xs btn-default showSubnetPerm' data-action='show'   data-subnetid='".$subnet['id']."'  data-sectionid='".$subnet['sectionId']."'><i class='fa fa-gray fa-tasks'></i></button>";
				print "		<button class='btn btn-xs btn-default editSubnet'     data-action='delete' data-subnetid='".$subnet['id']."'  data-sectionid='".$subnet['sectionId']."'><i class='fa fa-gray fa-times'></i></button>";
				print "	</div>";
				print " </td>";
			}
			else {
				print "	<td class='actions'>";
				print "	<div class='btn-group'>";
				print "		<button class='btn btn-xs btn-default disabled'><i class='fa fa-gray fa-pencil'></i></button>";
				print "		<button class='btn btn-xs btn-default disabled'><i class='fa fa-gray fa-tasks'></i></button>";
				print "		<button class='btn btn-xs btn-default disabled'><i class='fa fa-gray fa-times'></i></button>";
				print "	</div>";
				print " </td>";
			}
			print '</tr>' . "\n";

			$m++;
		}
		# no because of permissions
		if($m==0) {
			print "<tr>";
			print "<td colspan='7' class='visible-md visible-lg'>";
			print "<td colspan='3' class='visible-xs visible-sm'>";
			$Result->show("info", _("VRF has no belonging subnets")."!", false);
			print "</td>";
			print "</tr>";
		}
	}
	print '</table>'. "\n";
}
