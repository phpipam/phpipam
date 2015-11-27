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
	print '<table id="zonesPrint" class="table table-top table-td-top table-condensed">';

	# table headers
	print '<tr style="background:white">';
	print '<th>'._('Type').'</th>';
	print '<th>'._('Zone').'</th>';
	print '<th>'._('Description').'</th>';
	print '<th>'._('Subnet').'</th>';
	print '<th>'._('VLAN').'</th>';
	print '<th style="width:60px;"></th>';
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
				print '<tr class="border-top">';
				if ($i === 1) {
					// set title
					$title = $zoneObject->indicator == 0 ? 'Own Zone' : 'Customer Zone';
					// print
					print '<td rowspan="'.$counter.'"><span class="fa '.($zoneObject->indicator == 0 ? 'fa-home':'fa-group').'"  title="'._($title).'"></span></td>';
					print '<td rowspan="'.$counter.'">'.$zoneObject->zone.'</td>';
					print '<td rowspan="'.$counter.'">'.$zoneObject->description.'</td>';
				}
				# display subnet informations
				if ($network->subnetId) {
					// description fix
					$network->subnetDescription = strlen($network->subnetDescription)>0 ? " (".$network->subnetDescription.")" : "";

					if (!$network->subnetIsFolder) {
						print '<td><a href="'.create_link("subnets",$network->sectionId,$network->subnetId).'">'.$Subnets->transform_to_dotted($network->subnet).'/'.$network->subnetMask.$network->subnetDescription.'</a></td>';
					} else {
						print '<td><a href="'.create_link("subnets",$network->sectionId,$network->subnetId).'">Folder'.$network->subnetDescription.'</a></td>';
					}
				} else {
					print '<td>/</td>';
				}
				# display vlan informations
				if ($network->vlanId) {
					// name fix
					$network->vlanName = strlen($network->vlanName)>0 ? " (".$network->vlanName.")" : "";
					print '<td><a href="'.create_link('tools','vlan',$network->domainId,$network->vlanId).'">'.$network->vlan.$network->vlanName.'</a></td>';
				} else {
					print '<td>/</td>';
				}
				// actions
				if ($i === 1) {
					# action menu
					print '<td rowspan="'.$counter.'">';
					print '<div class="btn-group">';
					print '<button class="btn btn-default btn-xs editFirewallZone" data-action="edit" data-id="'.$zoneObject->id.'""><i class="fa fa-pencil"></i></button>';
					print '<button class="btn btn-default btn-xs editFirewallZone" data-action="delete" data-id="'.$zoneObject->id.'"><i class="fa fa-remove"></i></button>';
					print '</div>';
					print '</td>';
				}
				print '</tr>';

				// increase the loop counter
				$i++;
			}
		}
		# display only the zone data if there is no network data available
		else {
			// set title
			$title = $zoneObject->indicator == 0 ? 'Own Zone' : 'Customer Zone';

			print '<tr class="border-top">';
			print '<td rowspan="'.$counter.'"><span class="fa fa-home"  title="'._($title).'"></span></td>';
			print '<td>'.$zoneObject->zone.'</td>';
			print '<td>'.$zoneObject->description.'</td>';
			print '<td>/</td>';
			print '<td>/</td>';
			# action menu
			print '<td>';
			print '<div class="btn-group">';
			print '<button class="btn btn-default btn-xs editFirewallZone" data-action="edit" data-id="'.$zoneObject->id.'""><i class="fa fa-pencil"></i></button>';
			print '<button class="btn btn-default btn-xs editFirewallZone" data-action="delete" data-id="'.$zoneObject->id.'"><i class="fa fa-remove"></i></button>';
			print '</div>';
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
