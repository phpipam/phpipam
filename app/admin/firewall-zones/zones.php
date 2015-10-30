<?php

/**
 *	firewall zone fwzones.php
 *	display firewall zones
 *******************************/

# validate session parameters
$User->check_user_session();

# initialize classes
$Zones = new FirewallZones($Database);
$firewallZones = $Zones->get_zones();


# Add new firewall zone
print '<button class="btn btn-sm btn-default btn-success editFirewallZone" style="margin-bottom:10px;margin-top: 25px;" data-action="add" data-id="0"><i style="padding-right:5px;" class="fa fa-plus"></i>'._('Create Firewall zone').'</button>';


# display the zone table if there are any zones in the database
if($firewallZones) {

	# table
	print '<table id="zonesPrint" class="table table-condensed">';

	# table headers
	print '<tr>';
	print '<th>'._('Type').'</th>';
	print '<th>'._('Zone').'</th>';
	print '<th>'._('Description').'</th>';
	print '<th colspan="2">'._('Subnet').'</th>';
	print '<th colspan="3">'._('VLAN').'</th>';
	print '</tr>';

	# display all firewall zones and network information
	foreach ($firewallZones as $zoneObject ) {
		# set rowspan in case if there are more than one networks bound to the zone
		$counter = count($zoneObject->network);
		if ($counter === 0) {
			$counter = 1;
		}
		# set the loop counter
		$i = 1;
		if ($zoneObject->network) {
			foreach ($zoneObject->network as $key => $network) {
				print '<tr>';
				if ($i === 1) {
					print '<td rowspan="'.$counter.'">';
					if ($zoneObject->indicator == 0 ) {
						print '<span class="fa fa-home"  title="'._('Own Zone').'"></span>';
					} else {
						print '<span class="fa fa-group" title="'._('Customer Zone').'"></span>';
					}
					print '</td><td rowspan="'.$counter.'">';
					print $zoneObject->zone;
					print '</td><td rowspan="'.$counter.'">';
					print $zoneObject->description;
					print '</td>';
				}
				# display subnet informations
				if ($network->subnetId) {
					if (!$network->subnetIsFolder) {
						if ($network->subnetDescription) {
							print '<td><a href="'.create_link("subnets",$network->sectionId,$network->subnetId).'">'.$Subnets->transform_to_dotted($network->subnet).'/'.$network->subnetMask.'</a></td>';
							print '<td><a href="'.create_link("subnets",$network->sectionId,$network->subnetId).'">'.$network->subnetDescription.'</a></td>';
						} else {
							print '<td colspan="2"><a href="'.create_link("subnets",$network->sectionId,$network->subnetId).'">'.$Subnets->transform_to_dotted($network->subnet).'/'.$network->subnetMask.'</a></td>';
						}
					} else {
						print '<td><a href="'.create_link("subnets",$network->sectionId,$network->subnetId).'">Folder</a></td>';
						print '<td><a href="'.create_link("subnets",$network->sectionId,$network->subnetId).'">'.$network->subnetDescription.'</a></td>';
					}
				} else {
					print '<td colspan="2"></td>';
				}
				# display vlan informations
				if ($network->vlanId) {
					print '<td><a href="'.create_link('tools','vlan',$network->domainId,$network->vlanId).'">'.$network->vlan.'</a></td>';
					print '<td><a href="'.create_link('tools','vlan',$network->domainId,$network->vlanId).'">'.$network->vlanName.'</a></td>';
				} else {
					print '<td colspan="2"></td>';
				}
				if ($i === 1) {
					# action menu
					print '<td rowspan="'.$counter.'"><div class="btn-group">';
					print '<button class="btn btn-default btn-xs editFirewallZone" data-action="edit" data-id="'.$zoneObject->id.'""><i class="fa fa-pencil"></i></button>';
					print '<button class="btn btn-default btn-xs editFirewallZone" data-action="delete" data-id="'.$zoneObject->id.'"><i class="fa fa-remove"></i></button>';
					print '</td>';
				}
				print '</tr>';
				# increase the loop counter
				$i++;
				}
			} else {
				# display only the zone data if there is no network data available
				print '<tr>';
				print '<td rowspan="'.$counter.'">';
				if ($zoneObject->indicator == 0 ) {
					print '<span class="fa fa-home"  title="'._('Own Zone').'"></span>';
				} else {
					print '<span class="fa fa-group" title="'._('Customer Zone').'"></span>';
				}
				print '</td><td>';
				print $zoneObject->zone;
				print '</td><td>';
				print $zoneObject->description;
				print '</td>';
				print '<td colspan="4">';
				# action menu
				print '<td><div class="btn-group">';
				print '<button class="btn btn-default btn-xs editFirewallZone" data-action="edit" data-id="'.$zoneObject->id.'""><i class="fa fa-pencil"></i></button>';
				print '<button class="btn btn-default btn-xs editFirewallZone" data-action="delete" data-id="'.$zoneObject->id.'"><i class="fa fa-remove"></i></button>';
				print '</td>';
				print '</tr>';
			}
	}
	print '</table>';
} else {
	# print an info if there are no zones in the database
	$Result->show("info", _("No firewall zones configured"), false);
}
?>
