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

# display a link to the firewall zone management admin site
print "<h4>"._(' Firewall Zones')."</h4><hr>";
// manage link for admins
if($User->is_admin(false)) { print "<a href='".create_link('administration','firewall-zones')."' class='btn btn-sm btn-default'><i class='fa fa-pencil'></i> "._('Manage zones')."</a>"; }

print "<br><br>";

if($firewallZoneMapping) {
?>
	<!-- table -->
	<table id="mappingsPrint" class="table table-td-top table-top table-condensed">

	<!-- headers -->
	<tr>
		<th><?php print _('Type'); ?></th>
		<th><?php print _('Zone'); ?></th>
		<th><?php print _('Alias'); ?></th>
		<th><?php print _('Description'); ?></th>
		<th><?php print _('Interface'); ?></th>
		<th><?php print _('Subnet'); ?></th>
		<th><?php print _('VLAN'); ?></th>
	</tr>
	<?php
	# loop
	foreach ($devices as $k=>$firewallZoneMapping) { ?>
		<!-- header -->
		<tr>
		<?php
		$devices[$k][0]->deviceDescription = strlen($devices[$k][0]->deviceDescription) < 1 ? "" : " (".$devices[$k][0]->deviceDescription.")";
		print '<th colspan="7" style="background:white"><h4>'.$devices[$k][0]->deviceName.$devices[$k][0]->deviceDescription	.'</h4></th>';
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
						print '<td rowspan="'.$counter.'"><span class="fa fa-home"  title="'._($title).'"></span></td>';
						print '<td rowspan="'.$counter.'">'.$mapping->zone.'</td>';
						print '<td rowspan="'.$counter.'">'.$mapping->alias.'</td>';
						print '<td rowspan="'.$counter.'">'.$mapping->description.'</td>';
						print '<td rowspan="'.$counter.'">'.$mapping->interface.'</td>';
					}
					# display subnet informations
					if ($network->subnetId) {
						// description fix
						$network->subnetDescription = $network->subnetDescription ? " (".$network->subnetDescription.")" : "";
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
						$network->vlanName = strlen($network->vlanName)>0 ? " (".$network->vlanName.")" : "";
						print '<td><a href="'.create_link('tools','vlan',$network->domainId,$network->vlanId).'">Vlan '.$network->vlan.$network->vlanName.'</a></td>';
					} else {
						print '<td>/</td>';
					}
					print '</tr>';
					# increase the loop counter
					$i++;
				}
			}
			# display only the zone mapping data if there is no network data available
			else {
				$title = $mapping->indicator == 0 ? 'Own Zone' : 'Customer Zone';
				print '<tr class="border-top">';
				print '<td rowspan="'.$counter.'"><span class="fa fa-home"  title="'._($title).'"></span></td>';
				print '<td rowspan="'.$counter.'">'.$mapping->zone.'</td>';
				print '<td rowspan="'.$counter.'">'.$mapping->alias.'</td>';
				print '<td rowspan="'.$counter.'">'.$mapping->description.'</td>';
				print '<td rowspan="'.$counter.'">'.$mapping->interface.'</td>';
				print '<td colspan="2">';
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