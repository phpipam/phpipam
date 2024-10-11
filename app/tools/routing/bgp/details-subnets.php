<?php

# perm check
$User->check_module_permissions ("routing", User::ACCESS_R, true, false);

// fetch subnets
$subnets = $Tools->fetch_routing_subnets ("bgp", $bgp->id, false);

// title
print "<h4>"._('Mapped subnets')."</h4>";
print "<hr>";

if (!isset($colspan))
	$colspan = 0;

// check
if($subnets===false) {
	$Result->show("info", _("BGP has no mapped subnets")."!", false);
}
else {
	# table
	print '<table class="table slaves sorted table-striped table-condensed table-hover table-full table-top" data-cookie-id-table="vrf_subnets_slaves">'. "\n";

	# headers
	print "<thead>";
	print "<tr>";
	print "	<th>"._('Subnet')."</th>";
	print "	<th>"._('Description')."</th>";
	print "	<th>"._('Direction')."</th>";
	if($User->get_module_permissions ("vlan")>=User::ACCESS_R) {
	print "	<th class='small'>"._('VLAN')."</th>";
	$colspan++;
	}
	if($User->get_module_permissions ("vrf")>=User::ACCESS_R) {
	print "	<th class='small'>"._('VRF')."</th>";
	$colspan++;
	}
	print " <th class='actions'></th>";
	print "</tr>";
	print "</thead>";

	$m=0;
	print "<tbody>";
	# print subnets
	foreach ($subnets as $subnet) {
		# cast
		$subnet = (array) $subnet;
		# check permission
		$permission = $Subnets->check_permission ($User->user, $subnet['id']);
		# allowed
		if($permission > 0) {
            # add full information
            $fullinfo = $subnet['isFull']==1 ? " <span class='badge badge1 badge2 badge4'>"._("Full")."</span>" : "";

            # fetch vlan, vrf
            $vlan = $Tools->fetch_object ("vlans", "vlanId", $subnet['vlanId']);
            $vrf  = $Tools->fetch_object ("vrf", "vrfId", $subnet['vrfId']);
			
			is_object($vlan) ? : $vlan = new Params();
			is_object($vrf) ? : $vrf = new Params();

            # icon
            $icon = $subnet['direction'] == "advertised" ? "<i class='fa fa-arrow-up'></i>" : "<i class='fa fa-arrow-down'></i>";

			print "<tr>";
		    print "	<td><a href='".create_link("subnets",$subnet['sectionId'],$subnet['subnet_id'])."'>".$Subnets->transform_address($subnet['subnet'], "dotted")."/$subnet[mask] $fullinfo</a></td>";
		    print "	<td>$subnet[description]</td>";
		    print "	<td>$icon ".ucwords($subnet['direction'])."</td>";

			if($User->get_module_permissions ("vlan")>=User::ACCESS_R)
		    print "	<td class='small'><a href='".create_link("tools","vlan", $vlan->domainId, $vlan->vlanId)."'><span class='badge badge1'>$vlan->number</span></a></td>";
			if($User->get_module_permissions ("vrf")>=User::ACCESS_R)
		    print "	<td class='small'><a href='".create_link("tools","vrf", $vrf->vrfId)."'><span class='badge badge1'>$vrf->name</span></a></td>";
			# edit
			print "	<td class='actions'>";
            $links = [];
            if($User->get_module_permissions ("routing")>=User::ACCESS_RWA) {
                $links[] = ["type"=>"header", "text"=>_("Edit mapping")];
                $links[] = ["type"=>"link", "text"=>_("Delete mapping"), "href"=>"", "class"=>"open_popup", "dataparams"=>"data-script='app/admin/routing/edit-bgp-mapping-delete.php' data-secondary='true' data-bgpid='$subnet[id]'", "icon"=>"minus"];
            }
            // print links
            print $User->print_actions(0, $links);
			print '</tr>' . "\n";

			$m++;
		}
	}

	print "</tbody>";
	print '</table>'. "\n";


	# no because of permissions
	if($m==0) {
		$Result->show("info", _("BGP has no mapped subnets")."!", false);
	}
}