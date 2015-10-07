<?php
// firewall zone mapping.php
// list all firewall zone mappings

// validate session parameters
$User->check_user_session();

// initialize classes
$Database = new Database_PDO;
$Subnets = new Subnets ($Database);
$Result = new Result ();
$Zones = new FirewallZones($Database);

// fetch all zone mappings
$firewallZoneMapping = $Zones->get_zone_mappings();

print '<h4>'._('Firewall zone and device mappings').'</h4>';
?>

<!-- table -->
<table id="mappingsPrint" class="table table-striped table-top table-auto">
	<tr>
		<!-- header -->
		<th><?php print _('Type'); ?></th>
		<th><?php print _('Zone'); ?></th>
		<th><?php print _('Alias'); ?></th>
		<th><?php print _('Description'); ?></th>
		<th><?php print _('Devicename'); ?></th>
		<th><?php print _('Interface'); ?></th>
		<th colspan="2"><?php print _('Subnet'); ?></th>
		<th colspan="2"><?php print _('VLAN'); ?></th>
		<th></th>
	</tr>
<?php
foreach ($firewallZoneMapping as $mapping) {
	print '<tr>';
		// columns
		if ($mapping->indicator == 0 ) {
			print '<td><span class="fa fa-home"  title="'._('Own Zone').'"></span></td>';
		} else {
			print '<td><span class="fa fa-group" title="'._('Customer Zone').'"></span></td>';
		}
		print '<td>'.$mapping->zone.'</td>';
		print '<td>'.$mapping->alias.'</td>';
		print '<td>'.$mapping->description.'</td>';
		print '<td>'.$mapping->deviceName.'</td>';
		print '<td>'.$mapping->interface.'</td>';
		//print '<td>'.$Subnets->transform_to_dotted($mapping->subnet).'/'.$mapping->subnetMask.'</td>';
		print '<td>';
		// check if there is a subnetId and if it is convertable to dotted decimal
		if ($mapping->subnetId && $mapping->subnetDescription) {
			if (!$mapping->subnetIsFolder) {
				print '<a href="'.create_link("subnets",$mapping->sectionId,$mapping->subnetId).'">'.$Subnets->transform_to_dotted($mapping->subnet).'/'.$mapping->subnetMask.'</a>';
				print '</td><td>';
				print '<a href="'.create_link("subnets",$mapping->sectionId,$mapping->subnetId).'">'.$mapping->subnetDescription.'</a>';
			} else {
				print '<a href="'.create_link("subnets",$mapping->sectionId,$mapping->subnetId).'">Folder</a>';
				print '</td><td>';
				print '<a href="'.create_link("subnets",$mapping->sectionId,$mapping->subnetId).'">'.$mapping->subnetDescription.'</a>';
			}
		} else {
			print '</td><td>';
		}
		print '</td>';
		print '<td><a href="'.create_link('tools','vlan',$mapping->domainId,$mapping->vlanId).'">'.$mapping->vlan.'</a></td>';
		print '<td><a href="'.create_link('tools','vlan',$mapping->domainId,$mapping->vlanId).'">'.$mapping->vlanName.'</a></td>';
	print '</tr>';
}
?>

</table>