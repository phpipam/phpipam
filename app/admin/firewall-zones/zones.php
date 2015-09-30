<?php
// firewall zone fwzones.php
// display firewall zones


// validate session parameters
$User->check_user_session();

// initialize classes
$Zones = new FirewallZones($Database);
$firewallZones = $Zones->get_zones();

// DEBUG
print 'DEBUG<br><pre>';
print_r($firewallZones);
print '<br>';
print '</pre>';
// !DEBUG



// Add new firewall zone
print '<button class="btn btn-sm btn-default btn-success editFirewallZone" style="margin-bottom:10px;margin-top: 25px;" data-action="add" data-id="0"><i style="padding-right:5px;" class="fa fa-plus"></i>'._('Create Firewall zone').'</button>';


// display the zone table if there are any zones in the database
if($firewallZones) {
	
	// table
	print '<table id="zonesPrint" class="table table-striped table-top table-auto">';

	// table headers
	print '<tr>';
	print '<th>'._('Zone name').'</th>';
	print '<th>'._('Indicator').'</th>';
	print '<th>'._('Description').'</th>';
	print '<th>'._('Subnet').'</th>';
	print '<th>'._('VLAN').'</th>';
	print '<th>'._('VLAN Name').'</th>';
	print '<th></th>';
	print '</tr>';

	// firewall zones
	foreach ($firewallZones as $zoneObject ) {
		print '<tr><td>';
		print $zoneObject->zone;
		print '</td><td>';
		print $zoneObject->indicator;
		print '</td><td>';
		print $zoneObject->description;
		print '</td><td>';
		if ($zoneObject->subnetId) {
			print '<a href="'.create_link("subnets",$zoneObject->sectionId,$zoneObject->subnetId).'">'.$Subnets->transform_to_dotted($zoneObject->subnet).'/'.$zoneObject->subnetMask.'</a>';
		}
		print '</td><td>';
		print '<a href="'.create_link('tools','vlan',$zoneObject->domainId,$zoneObject->vlanId).'">'.$zoneObject->vlan.'</a>';
		print '</td><td>';
		print $zoneObject->vlanName;
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
