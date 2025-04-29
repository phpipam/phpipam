<?php
/**
 *	firewall zone ajax.php
 *	deliver content for ajax requests
 **************************************/

# functions
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database = new Database_PDO;
$User 	  = new User ($Database);
$Admin 	  = new Admin ($Database);
$Subnets  = new Subnets ($Database);
$Result   = new Result ();
$Zones 	  = new FirewallZones($Database);
$Tools	  = new Tools($Database);

# verify that user is logged in
$User->check_user_session();

# generate a dropdown list for all subnets within a section
if ($POST->operation == 'fetchSectionSubnets') {
	if($POST->sectionId) {
		if(preg_match('/^[0-9]+$/i',$POST->sectionId)) {
			$sectionId = $POST->sectionId;
			print $Subnets->print_mastersubnet_dropdown_menu($sectionId);
		} else {
			$Result->show('danger', _('Invalid ID.'), true);
		}
	}
}

# deliver zone details
if ($POST->operation == 'deliverZoneDetail') {
	if ($POST->zoneId) {
		if(preg_match('/^[0-9]+$/i',$POST->zoneId)) {
			# return the zone details
			$Zones->get_zone_detail($POST->zoneId);

		} else {
			$Result->show('danger', _('Invalid zone ID.'), true);
		}
	}
}

# deliver networkinformations about a specific zone
if ($POST->netZoneId) {
	if(preg_match('/^[0-9]+$/i',$POST->netZoneId)) {
		# return the zone details
		$Zones->get_zone_network($POST->netZoneId);
	} else {
		$Result->show('danger', _('Invalid netZone ID.'), true);
	}
}

# deliver networkinformations about a specific zone
if ($POST->noZone == 1) {
	if($POST->masterSubnetId) {
		$POST->network = [$POST->masterSubnetId];
	}
	if ($POST->network) {
		$rowspan = count($POST->network);
		$i = 1;
		print '<table class="table table-noborder table-condensed" style="padding-bottom:20px;">';
		foreach ($POST->network as $key => $network) {
			$network = $Subnets->fetch_subnet(null,$network);
			print '<tr>';
			if ($i === 1) {
				print '<td rowspan="'.$rowspan.'" style="width:150px;">Network</td>';
			}
			print '<td>';
			print '<span alt="'._('Delete Network').'" title="'._('Delete Network').'" class="deleteTempNetwork" style="color:red;margin-bottom:10px;margin-top: 10px;margin-right:15px;" data-action="delete" data-subnetArrayKey="'.$key.'"><i class="fa fa-close"></i></span>';
			if ($network->isFolder == 1) {
				print 'Folder: '.$network->description.'</td>';
			} else {
				# display network information with or without description
				if ($network->description) 	{	print $Subnets->transform_to_dotted($network->subnet).'/'.$network->mask.' ('.$network->description.')</td>';	}
				else 						{	print $Subnets->transform_to_dotted($network->subnet).'/'.$network->mask.'</td>';	}
			}
			print '<input type="hidden" name="network['.$key.']" value="'.$network->id.'">';
			print '</tr>';
			$i++;
		}
		print '</table>';
	}
}


# generate a new firewall address object on request
if ($POST->operation == 'autogen') {
	if ($POST->action == 'net') {
		if (preg_match('/^[0-9]+$/i',$POST->subnetId)){
			$Zones->update_address_objects($POST->subnetId);
		}
	} elseif ($POST->action == 'adr') {
		if (preg_match('/^[0-9]+$/i',$POST->subnetId) && preg_match('/^[0-9a-z-.]+$/i',$POST->dnsName) && preg_match('/^[0-9]+$/i',$POST->IPId)) {
			$Zones->update_address_object($POST->subnetId,$POST->IPId,$POST->dnsName);
		}
	} elseif ($POST->action == 'subnet') {
		if (preg_match('/^[0-9]+$/i',$POST->subnetId)) {
			$Zones->generate_subnet_object ($POST->subnetId);
		}
	}
}

# check if there is any mapping for a specific zone, if not, display inputs
if ($POST->operation == 'checkMapping') {

	if (!$Zones->check_zone_mapping($POST->zoneId) && $POST->zoneId != 0) {
		# fetch all firewall zones
		$firewallZones = $Zones->get_zones();

		# fetch settings
		$firewallZoneSettings = db_json_decode($User->settings->firewallZoneSettings,true);

		# fetch all devices
		$devices = $Tools->fetch_multiple_objects ("devices", "type", $firewallZoneSettings['deviceType']);

		?>
		<table class="table table-noborder table-condensed">
			<tr>
				<td colspan="2">
					<?php print _('In order to map this network to a zone without an existing device mapping you have to specify the following values.'); ?>
				</td>
			</tr>
			<tr>
				<!-- zone indicator -->
				<td>
					<?php print _('Firewall to map'); ?>
				</td>
				<td>
					<select name="deviceId" class="form-control input-sm input-w-auto input-max-200" <?php print $readonly; ?>>
					<option value="0"><?php print _('Select firewall'); ?></option>
					<?php
					if ($devices!==false) {
    					foreach ($devices as $device) {
    						if ($device->id == $mapping->deviceId) 	{
    							if($device->description) 	{	print '<option value="'.$device->id.'" selected>'.	$device->hostname.' ('.$device->description.')</option>'; }
    							else 						{ 	print '<option value="'.$device->id.'" selected>'.	$device->hostname.'</option>'; }}
    						else {
    							if($device->description)	{	print '<option value="'.$device->id.'">'.			$device->hostname.' ('.$device->description.')</option>'; }
    							else 						{	print '<option value="'.$device->id.'">'.			$device->hostname.'</option>'; }}
    					}
					}
					?>
					</select>
				</td>
			</tr>
			<tr>
				<td>
					<?php print _('Interface'); ?>
				</td>
				<td>
					<input type="text" class="form-control input-sm" name="interface" placeholder="<?php print _('Firewall interface'); ?>" value="<?php print $mapping->interface; ?>" <?php print $readonly; ?>>
				</td>
			</tr>
			<tr>
				<!-- description -->
				<td>
					<?php print _('Zone alias'); ?>
				</td>
				<td>
					<input type="text" class="form-control input-sm" name="alias" placeholder="<?php print _('Local zone alias'); ?>" value="<?php print $mapping->alias; ?>" <?php print $readonly; ?>>
				</td>
			</tr>
		</table>
		<?php
	} elseif ($POST->zoneId != 0) {
		# return the zone details
		$Zones->get_zone_detail($POST->zoneId);
	}
}


?>