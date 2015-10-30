<?php

/**
 *	firewall zone mapping.php
 *	list all firewall zone mappings
 ***************************************/

# initialize classes
$Database 	= new Database_PDO;
$Subnets 	= new Subnets ($Database);
$Result 	= new Result ();
$Zones 		= new FirewallZones($Database);

# validate session parameters
$User->check_user_session();

# fetch all zone mappings
$firewallZoneMapping = $Zones->get_zone_mappings();

# reorder by device
if ($firewallZoneMapping!==false) {
	# devices
	$devices = array();
	# add
	foreach ($firewallZoneMapping as $m) {
		$devices[$m->deviceId][] = $m;
	}
}
?>
<!-- Add new firewall zone mapping -->
<button class="btn btn-sm btn-default btn-success editMapping" style="margin-bottom:10px;margin-top: 25px;" data-action="add" data-id="0"><i style="padding-right:5px;" class="fa fa-plus"></i><?php print _('Create Firewall zone mapping') ?></button>

<?php
if($firewallZoneMapping) {
?>
	<!-- table -->
	<table id="mappingsPrint" class="table table-condensed">
	<?php

	# loop
	foreach ($devices as $k=>$firewallZoneMapping) { ?>
		<!-- header -->
		<tr>
		<th colspan='10'><h4><?php print $devices[$k][0]->deviceName; ?></h4></th>
		</tr>
		<tr>
			<!-- header -->
			<th><?php print _('Type'); ?></th>
			<th><?php print _('Zone'); ?></th>
			<th><?php print _('Alias'); ?></th>
			<th><?php print _('Description'); ?></th>
			<th><?php print _('Interface'); ?></th>
			<th colspan="2"><?php print _('Subnet'); ?></th>
			<th colspan="3"><?php print _('VLAN'); ?></th>
		</tr>
		<?php

		# mappings
		foreach ($firewallZoneMapping as $mapping ) {
			# set rowspan in case if there are more than one networks bound to the zone
			$counter = count($mapping->network);
			if ($counter === 0) {
				$counter = 1;
			}
			# set the loop counter
			$i = 1;
			if ($mapping->network) {
				foreach ($mapping->network as $key => $network) {
					print '<tr>';
					if ($i === 1) {
						print '<td rowspan="'.$counter.'">';
					# columns
					if ($mapping->indicator == 0 ) {
						print '<span class="fa fa-home"  title="'._('Own Zone').'"></span>';
					} else {
						print '<span class="fa fa-group" title="'._('Customer Zone').'"></span>';
					}
					print '</td>';
					print '<td rowspan="'.$counter.'">'.$mapping->zone.'</td>';
					print '<td rowspan="'.$counter.'">'.$mapping->alias.'</td>';
					print '<td rowspan="'.$counter.'">'.$mapping->description.'</td>';
					print '<td rowspan="'.$counter.'">'.$mapping->interface.'</td>';
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
						print '<button class="btn btn-default btn-xs editMapping" data-action="edit" data-id="'.$mapping->mappingId.'""><i class="fa fa-pencil"></i></button>';
						print '<button class="btn btn-default btn-xs editMapping" data-action="delete" data-id="'.$mapping->mappingId.'"><i class="fa fa-remove"></i></button>';
						print '</td>';
					}
					print '</tr>';
					# increase the loop counter
					$i++;
					}
				} else {
					# display only the zone mapping data if there is no network data available
					print '<td rowspan="'.$counter.'">';
					# columns
					if ($mapping->indicator == 0 ) {
						print '<span class="fa fa-home"  title="'._('Own Zone').'"></span>';
					} else {
						print '<span class="fa fa-group" title="'._('Customer Zone').'"></span>';
					}
					print '<td rowspan="'.$counter.'">'.$mapping->zone.'</td>';
					print '<td rowspan="'.$counter.'">'.$mapping->alias.'</td>';
					print '<td rowspan="'.$counter.'">'.$mapping->description.'</td>';
					print '<td rowspan="'.$counter.'">'.$mapping->interface.'</td>';
					print '<td colspan="4">';
					# action menu
					print '<td><div class="btn-group">';
					print '<button class="btn btn-default btn-xs editMapping" data-action="edit" data-id="'.$mapping->mappingId.'""><i class="fa fa-pencil"></i></button>';
					print '<button class="btn btn-default btn-xs editMapping" data-action="delete" data-id="'.$mapping->mappingId.'"><i class="fa fa-remove"></i></button>';
					print '</td>';
					print '</tr>';
				}
		}
	}
	print '</table>';
}
else {
	# print an info if there are no zones in the database
	$Result->show("info", _("No firewall zones configured"), false);
}
?>