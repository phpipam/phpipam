<?php

/**
 * Main script to display master subnet details if subnet has slaves
 ***********************************************************************/

# set object
$subnet = $Tools->fetch_object("subnets", "id", $temp_objects[$GET->section]->id);

# subnet id must be numeric
if(!is_numeric($subnet->id)) 		{ $Result->show("danger", _('Invalid ID'), true); }

//cast
$subnet = (array) $subnet;

# fetch subnet reated stuff
$custom_fields = $Tools->fetch_custom_fields ('subnets');											//custom fields
$subnet  = (array) $Subnets->fetch_subnet(null, $subnet['id']);										//subnet details
if(sizeof($subnet)==0) 				{ $Result->show("danger", _('Subnet does not exist'), true); }	//die if empty
$subnet_detailed = $Subnets->get_network_boundaries ($subnet['subnet'], $subnet['mask']);			//set network boundaries
$slaves = $Subnets->has_slaves ($subnet['id']) ? true : false;										//check if subnet has slaves and set slaves flag true/false

# permissions
$subnet_permission  = 1;			//subnet permission
$section_permission = 1;			//section permission
if($subnet_permission == 0)			{ $Result->show("danger", _('You do not have permission to access this network'), true); }

# fetch VLAN details
$vlan = (array) $Tools->fetch_object("vlans", "vlanId", $subnet['vlanId']);

# fetch all addresses and calculate usage
if($slaves) {
	$addresses = $Addresses->fetch_subnet_addresses_recursive ($subnet['id'], false);
	$slave_subnets = (array) $Subnets->fetch_subnet_slaves ($subnet['id']);
} else {
	$addresses = $Addresses->fetch_subnet_addresses ($subnet['id']);
}
$subnet_usage  = $Subnets->calculate_subnet_usage ($subnet);		//Calculate free/used etc

?>


<!-- content print! -->
<div class="row" style="margin-bottom: 40px;">

	<!-- subnet details -->
	<div class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
		<!-- for adding IP address! -->
		<div id="subnetId" style="display:none;"><?php print $subnet['id']; ?></div>
		<?php include('subnet-details.php'); ?>
	</div>

	<!-- subnet graph -->
	<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
		<?php include('subnet-graph.php'); ?>
	</div>

	<!-- addresses -->
	<div class="col-xs-12 ipaddresses_overlay">
		<?php include('subnet-addresses.php'); ?>
	</div>

	<!-- visual subnet display -->
	<div class="col-xs-12">
	<?php
	if($Subnets->identify_address($subnet['subnet']) == "IPv4") {
		if($settings->visualLimit > 0) {
			if($settings->visualLimit <= $subnet['mask'] && !$slaves) {
				include('subnet-visual.php');
			}
		}
	}
	?>
	</div>

	<!-- orphaned addresses -->
	<div class="col-xs-12">
		<?php
		# if subnet has slaves check if also has addresses - if so they are orphaned, print them!
		if($slaves) {
			$addresses = $Addresses->fetch_subnet_addresses ($subnet['id']);
			if(sizeof($addresses)>0) {
				# set flag
				$orphaned = true;
				include('addresses/print-address-table.php');
			}
		}
		?>
	</div>

</div>
