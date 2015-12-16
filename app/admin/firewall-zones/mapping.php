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
	<table id="mappingsPrint" class="table table-td-top table-top table-condensed">

	<!-- header -->
	<tr>
		<th><?php print _('Type'); ?></th>
		<th><?php print _('Zone'); ?></th>
		<th><?php print _('Alias'); ?></th>
		<th><?php print _('Description'); ?></th>
		<th><?php print _('Interface'); ?></th>
		<th><?php print _('Subnets'); ?></th>
		<th><?php print _('VLAN'); ?></th>
		<th style="width:60px"></th>
	</tr>

	<?php

	# loop
	foreach ($devices as $k=>$firewallZoneMapping) { ?>
		<!-- header -->
		<tr>
		<?php
		$devices[$k][0]->deviceDescription = strlen($devices[$k][0]->deviceDescription)<1 ? "" : "(".$devices[$k][0]->deviceDescription.")";
		print '<th colspan="8" style="background:white"><h4>'.$devices[$k][0]->deviceName.$devices[$k][0]->deviceDescription	.'</h4></th>';
		?>
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
					print '<tr class="border-top">';
					if ($i === 1) {
						$title = $mapping->indicator == 0 ? 'Own Zone' : 'Customer Zone';
						// print
						print '<td rowspan="'.$counter.'"><span class="fa '.($mapping->indicator == 0 ? 'fa-home':'fa-group').'"  title="'._($title).'"></span></td>';
						print '<td rowspan="'.$counter.'">'.$mapping->zone.'</td>';
						print '<td rowspan="'.$counter.'">'.$mapping->alias.'</td>';
						print '<td rowspan="'.$counter.'">'.$mapping->description.'</td>';
						print '<td rowspan="'.$counter.'">'.$mapping->interface.'</td>';
					}
					# display subnet informations
					if ($network->subnetId) {
						// description fix
						$network->subnetDescription = strlen($network->subnetDescription)>0 ? " (".$network->subnetDescription.")" : "";
						// subnet
						if (!$network->subnetIsFolder) {
							print '<td><a href="'.create_link("subnets",$network->sectionId,$network->subnetId).'">'.$Subnets->transform_to_dotted($network->subnet).'/'.$network->subnetMask.$network->subnetDescription.'</a></td>';
						}
						else {
							print '<td><a href="'.create_link("subnets",$network->sectionId,$network->subnetId).'">Folder'.$network->subnetDescription.'</a></td>';
						}
					} else {
						print '<td>/</td>';
					}
					# display vlan informations
					if ($network->vlanId) {
						// name fix
						$network->vlanName = strlen($network->vlanName)>0 ? " (".$network->vlanName.")" : "";
						print '<td><a href="'.create_link('tools','vlan',$network->domainId,$network->vlanId).'">Vlan '.$network->vlan.''.$network->vlanName.'</a></td>';
					} else {
						print '<td>/</td>';
					}
					if ($i === 1) {
						# action menu
						print '<td rowspan="'.$counter.'">';
						print '<div class="btn-group">';
						print '<button class="btn btn-default btn-xs editMapping" data-action="edit" data-id="'.$mapping->mappingId.'""><i class="fa fa-pencil"></i></button>';
						print '<button class="btn btn-default btn-xs editMapping" data-action="delete" data-id="'.$mapping->mappingId.'"><i class="fa fa-remove"></i></button>';
						print '</div>';
						print '</td>';
					}
					print '</tr>';
					# increase the loop counter
					$i++;
				}
			} else {
				print "<tr class='border-top'>";
				# display only the zone mapping data if there is no network data available
				$title = $mapping->indicator == 0 ? 'Own Zone' : 'Customer Zone';

				print '<td rowspan="'.$counter.'"><span class="fa fa-home"  title="'._($title).'"></span></td>';
				print '<td rowspan="'.$counter.'">'.$mapping->zone.'</td>';
				print '<td rowspan="'.$counter.'">'.$mapping->alias.'</td>';
				print '<td rowspan="'.$counter.'">'.$mapping->description.'</td>';
				print '<td rowspan="'.$counter.'">'.$mapping->interface.'</td>';
				print '<td colspan="2"></td>';
				# action menu
				print '<td>';
				print '<div class="btn-group">';
				print '<button class="btn btn-default btn-xs editMapping" data-action="edit" data-id="'.$mapping->mappingId.'""><i class="fa fa-pencil"></i></button>';
				print '<button class="btn btn-default btn-xs editMapping" data-action="delete" data-id="'.$mapping->mappingId.'"><i class="fa fa-remove"></i></button>';
				print '</div>';
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