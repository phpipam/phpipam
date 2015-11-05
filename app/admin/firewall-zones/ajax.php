<?php
/**
 *	firewall zone ajax.php
 *	deliver content for ajax requests
 **************************************/

# functions
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database = new Database_PDO;
$User 	  = new User ($Database);
$Admin 	  = new Admin ($Database);
$Subnets  = new Subnets ($Database);
$Result   = new Result ();
$Zones 	  = new FirewallZones($Database);

# verify that user is logged in
$User->check_user_session();

# generate a dropdown list for all subnets within a section
if($_POST['sectionId']) {
	if(preg_match('/^[0-9]+$/i',$_POST['sectionId'])) {
		$sectionId = $_POST['sectionId'];
		print $Subnets->print_mastersubnet_dropdown_menu($sectionId);
	} else {
		$Result->show('danger', _('Invalid ID.'), true);
	}
}

# deliver zone details
if ($_POST['zoneId']) {
	if(preg_match('/^[0-9]+$/i',$_POST['zoneId'])) {
		# return the zone details
		$Zones->get_zone_detail($_POST['zoneId']);

	} else {
		$Result->show('danger', _('Invalid zone ID.'), true);
	}
}

# deliver networkinformations about a specific zone
if ($_POST['netZoneId']) {
	if(preg_match('/^[0-9]+$/i',$_POST['netZoneId'])) {
		# return the zone details
		$Zones->get_zone_network($_POST['netZoneId']);
	} else {
		$Result->show('danger', _('Invalid netZone ID.'), true);
	}
}

# deliver networkinformations about a specific zone
if ($_POST['noZone'] == 1) {
	if($_POST['masterSubnetId']) {
		$_POST['network'][] = $_POST['masterSubnetId'];
	}
	if ($_POST['network']) {
		$rowspan = count($_POST['network']);
		$i = 1;
		print '<table class="table table-noborder table-condensed" style="padding-bottom:20px;">';
		foreach ($_POST['network'] as $key => $network) {
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
?>