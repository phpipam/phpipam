<?php
// firewall zone fwzones.php
// display firewall zones


// validate session parameters
$User->check_user_session();

// initialize classes
$Zones = new FirewallZones($Database);
$firewallZones = $Zones->get_zones();


// Add new firewall zone
print '<button class="btn btn-sm btn-default btn-success editFirewallZone" style="margin-bottom:10px;margin-top: 25px;" data-action="add" data-id="0"><i style="padding-right:5px;" class="fa fa-plus"></i>'._('Create Firewall zone').'</button>';


// display the zone table if there are any zones in the database
if($firewallZones) {

	// table
	print '<table id="zonesPrint" class="table table-striped table-top table-auto">';

	// table headers
	print '<tr>';
	print '<th>'._('Type').'</th>';
	print '<th>'._('Zone').'</th>';
	print '<th>'._('Description').'</th>';
	print '<th colspan="2">'._('Subnet').'</th>';
	print '<th colspan="2">'._('VLAN').'</th>';
	print '<th></th>';
	print '</tr>';

	// firewall zones
	foreach ($firewallZones as $zoneObject ) {
		print '<tr>';
		if ($zoneObject->indicator == 0 ) {
			print '<td><span class="fa fa-home"  title="'._('Own Zone').'"></span></td>';
		} else {
			print '<td><span class="fa fa-group" title="'._('Customer Zone').'"></span></td>';
		}
		print '<td>';
		print $zoneObject->zone;
		print '</td><td>';
		print $zoneObject->description;
		print '</td><td>';
		// check if there is a subnetId and if it is convertable to dotted decimal
		if ($zoneObject->subnetId && $zoneObject->subnetDescription) {
			if (!$zoneObject->subnetIsFolder) {
				print '<a href="'.create_link("subnets",$zoneObject->sectionId,$zoneObject->subnetId).'">'.$Subnets->transform_to_dotted($zoneObject->subnet).'/'.$zoneObject->subnetMask.'</a>';
				print '</td><td>';
				print '<a href="'.create_link("subnets",$zoneObject->sectionId,$zoneObject->subnetId).'">'.$zoneObject->subnetDescription.'</a>';
			} else {
				print '<a href="'.create_link("subnets",$zoneObject->sectionId,$zoneObject->subnetId).'">Folder</a>';
				print '</td><td>';
				print '<a href="'.create_link("subnets",$zoneObject->sectionId,$zoneObject->subnetId).'">'.$zoneObject->subnetDescription.'</a>';
			}
		} else {
			print '</td><td>';
		}
		print '</td><td>';
		print '<a href="'.create_link('tools','vlan',$zoneObject->domainId,$zoneObject->vlanId).'">'.$zoneObject->vlan.'</a>';
		print '</td><td>';
		print '<a href="'.create_link('tools','vlan',$zoneObject->domainId,$zoneObject->vlanId).'">'.$zoneObject->vlanName.'</a>';
		// action menu
		print '</td><td><div class="btn-group">';
		print '<button class="btn btn-default btn-xs editFirewallZone" data-action="edit" data-id="'.$zoneObject->id.'""><i class="fa fa-pencil"></i></button>';
		print '<button class="btn btn-default btn-xs editFirewallZone" data-action="delete" data-id="'.$zoneObject->id.'"><i class="fa fa-remove"></i></button>';
		print '</td></tr></div>';
	}

	print '</table>';

} else {
	// print an info if there are no zones in the database
	$Result->show("info", _("No firewall zones configured"), false);
}
?>
