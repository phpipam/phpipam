<?php

/**
 * Script to display all slave IP addresses and subnets in content div of subnets table!
 ***************************************************************************************/

# verify that user is logged in
$User->check_user_session();
# perm check
$User->check_module_permissions ("vrf", 1, true, false);

# fetch all subnets in vrf in this section
$slaves = $Subnets->fetch_vrf_subnets ($_GET['subnetId'], NULL);

# no subnets
if(!$slaves) {
	print "<hr>";
	print "<h4>"._('VRF')." $vrf->name (".$vrf->description.") "._('has no belonging subnets')."</h4>";
}
else {
	# cast
	$vrf = (array) $vrf;
	# print title
	$slaveNum = sizeof($slaves);
	print "<h4>"._('VRF')." $vrf[name] (".$vrf['description'].") "._('has')." $slaveNum "._('belonging subnets').":</h4><hr><br>";

	# table
	print '<table class="table slaves sorted table-striped table-condensed table-hover table-full table-top" data-cookie-id-table="vrf_subnets_slaves">'. "\n";

	# headers
	print "<thead>";
	print "<tr>";
	if($User->get_module_permissions ("vlan")>0)
	print "	<th class='small'>"._('VLAN')."</th>";
	print "	<th class='small description'>"._('Subnet description')."</th>";
	print "	<th class='description'>"._('Subnet')."</th>";
	print "	<th class='small hidden-xs hidden-sm'>"._('Used')."</th>";
	print "	<th class='small hidden-xs hidden-sm'>% "._('Free')."</th>";
	print " <th class='actions'></th>";
	print "</tr>";
	print "</thead>";

	$m=0;
	print "<tbody>";
	# print subnets
	foreach ($slaves as $subnet) {
		# cast
		$subnet = (array) $subnet;
		# check permission
		$permission = $Subnets->check_permission ($User->user, $subnet['id']);
		# allowed
		if($permission > 0) {

            # add full information
            $fullinfo = $subnet['isFull']==1 ? " <span class='badge badge1 badge2 badge4'>"._("Full")."</span>" : "";

            # fetch vlan
            $vlan = $Tools->fetch_object ("vlans", "vlanId", $subnet['vlanId']);

			print "<tr>";
			if($User->get_module_permissions ("vlan")>0)
		    print "	<td><a href='".create_link("tools","vlan", $vlan->domainId, $vlan->vlanId)."'><span class='badge badge1'>$vlan->number</span></a></td>";
		    print "	<td class='small description'><a href='".create_link("subnets",$_GET['section'],$subnet['id'])."'>$subnet[description]</a></td>";
		    print "	<td><a href='".create_link("subnets",$_GET['section'],$subnet['id'])."'>".$Subnets->transform_address($subnet['subnet'], "dotted")."/$subnet[mask] $fullinfo</a></td>";

			# increase IP count
			$ipCount = 0;
			if(!$Subnets->has_slaves ($slave['id']))	{ $ipCount = $Addresses->count_subnet_addresses ($subnet['id']); }			//ip count - no slaves
			else 										{
				# fix for subnet and broadcast free space calculation
				$ipCount = 0;															//initial count
				$Subnets->reset_subnet_slaves_recursive ();
				$slaves2 = $Subnets->fetch_subnet_slaves_recursive ($subnet['id']);		//fetch all slaves
				foreach($Subnets->slaves as $s) {
					$ipCount = $ipCount + $Addresses->count_subnet_addresses ($s['id']);
					# subnet and broadcast add used
					if($Subnets->get_ip_version ($s['subnet'])=="IPv4" && $s['mask']<31) {
						$ipCount = $ipCount+2;
					}
				}
			}

			# print usage
			$calculate = $Subnets->calculate_subnet_usage ($subnet);
		    print ' <td class="small hidden-xs hidden-sm">'. $calculate['used'] .'/'. $calculate['maxhosts'] .'</td>'. "\n";
		    print '	<td class="small hidden-xs hidden-sm">'. $calculate['freehosts_percent'] .'</td>';

			# edit
			print "	<td class='actions'>";
			print "	<div class='btn-group'>";
			if($permission == 3) {
				print "		<button class='btn btn-xs btn-default editSubnet'     data-action='edit'   data-subnetid='".$subnet['id']."'  data-sectionid='".$subnet['sectionId']."'><i class='fa fa-gray fa-pencil'></i></button>";
				print "		<button class='btn btn-xs btn-default showSubnetPerm' data-action='show'   data-subnetid='".$subnet['id']."'  data-sectionid='".$subnet['sectionId']."'><i class='fa fa-gray fa-tasks'></i></button>";
				print "		<button class='btn btn-xs btn-default editSubnet'     data-action='delete' data-subnetid='".$subnet['id']."'  data-sectionid='".$subnet['sectionId']."'><i class='fa fa-gray fa-times'></i></button>";
			}
			else {
				print "		<button class='btn btn-xs btn-default disabled'><i class='fa fa-gray fa-pencil'></i></button>";
				print "		<button class='btn btn-xs btn-default disabled'><i class='fa fa-gray fa-tasks'></i></button>";
				print "		<button class='btn btn-xs btn-default disabled'><i class='fa fa-gray fa-times'></i></button>";
			}
			print "	</div>";
			print " </td>";
			print '</tr>' . "\n";

			$m++;
		}
	}

	# no because of permissions
	if($m==0) {
		print "<tr>";
		print "<td colspan='6' class='visible-md visible-lg'>";
		$Result->show("info", _("VRF has no belonging subnets")."!", false);
		print "</td>";
		print "</tr>";
	}

	print "</tbody>";
	print '</table>'. "\n";
}