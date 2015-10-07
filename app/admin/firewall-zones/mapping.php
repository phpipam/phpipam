<?php
// firewall zone mapping.php
// list all firewall zone mappings

# initialize classes
$Database 	= new Database_PDO;
$Subnets 	= new Subnets ($Database);
$Result 	= new Result ();
$Zones 		= new FirewallZones($Database);

# validate session parameters
$User->check_user_session();

# fetch all zone mappings
$firewallZoneMapping = $Zones->get_zone_mappings();

?>
<!-- Add new firewall zone mapping -->
<button class="btn btn-sm btn-default btn-success editMapping" style="margin-bottom:10px;margin-top: 25px;" data-action="add" data-id="0"><i style="padding-right:5px;" class="fa fa-plus"></i><?php print _('Create Firewall zone mapping') ?></button>

<?php
if($firewallZoneMapping) {
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
	// loop
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
			} ?>
			</td>
			<td><a href="<?php create_link('tools','vlan',$mapping->domainId,$mapping->vlanId); ?>"><?php print $mapping->vlan; ?></a></td>
			<td><a href="<?php create_link('tools','vlan',$mapping->domainId,$mapping->vlanId); ?>"><?php print $mapping->vlanName; ?></a></td>
			<!-- action menu -->
			<td>
				<div class="btn-group">
					<button class="btn btn-default btn-xs editMapping" data-action="edit" data-id="<?php print $mapping->mappingId; ?>"><i class="fa fa-pencil"></i></button>
					<button class="btn btn-default btn-xs editMapping" data-action="delete" data-id="<?php print $mapping->mappingId; ?>"><i class="fa fa-remove"></i></button>
				</div>
			</td>
		</tr>
	<?php } ?>
	</table>

<?php
}
else {
	// print an info if there are no zones in the database
	$Result->show("info", _("No firewall zones configured"), false);
}
?>