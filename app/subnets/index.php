<?php

/* script to show subnet details, addresses etc */

# verify that user is logged in
$User->check_user_session();

# subnet id must be numeric
if(!is_numeric($GET->subnetId)) 	{ $Result->show("danger", _('Invalid ID'), true); }

# fetch subnet related stuff
$custom_fields = $Tools->fetch_custom_fields ('subnets');											//custom fields
$subnet  = $Subnets->fetch_subnet(null, $GET->subnetId);									//subnet details
if($subnet===false) 				{ header("Location: ".create_link("subnets", $GET->section)); die(); }	//redirect if false
else { $subnet = (array) $subnet; }
$subnet_detailed = $Subnets->get_network_boundaries ($subnet['subnet'], $subnet['mask']);			//set network boundaries
$slaves = $Subnets->has_slaves ($subnet['id']) ? true : false;										//check if subnet has slaves and set slaves flag true/false

# if subnet is requested but is folder redirect
if ($subnet['isFolder']==1) { header("Location: ".create_link("folder", $GET->section, $GET->subnetId)); }

# permissions
$subnet_permission  = $Subnets->check_permission($User->user, $subnet['id']);						//subnet permission
$section_permission = $Sections->check_permission($User->user, $subnet['sectionId']);				//section permission
if($subnet_permission == 0)			{ $Result->show("danger", _('You do not have permission to access this network'), true); }

# fetch VLAN details
$vlan = (array) $Tools->fetch_object("vlans", "vlanId", $subnet['vlanId']);

# fetch recursive nameserver details
$nameservers = (array) $Tools->fetch_object("nameservers", "id", $subnet['nameserverId']);

# verify that is it displayed in proper section, otherwise warn!
if($subnet['sectionId']!=$GET->section)	{
	$sd = $Sections->fetch_section("id", $subnet['sectionId']);
	if (!is_object($sd)) {
		$sd = new Params();
	}
	$Result->show("warning", _("Subnet is in section") . " <a href='" . create_link("subnets", $sd->id, $subnet['id']) . "'>" . $sd->name . "</a>!", false);
}

// get usage
$subnet_usage  = $Subnets->calculate_subnet_usage ($subnet);

# set title
$location = "subnets";

# NAT search
$all_nats = array();
$all_nats_per_object = array();

if ($User->settings->enableNAT==1) {
    # fetch all object
    $all_nats = $Tools->fetch_all_objects ("nat", "name");

    if ($all_nats!==false) {
    	$out = [];
        foreach ($all_nats as $n) {
            $out[$n->id] = $n;
        }
        $all_nats = $out;

        # reindex
        $all_nats_per_object = $Tools->reindex_nat_objects ($all_nats);
    }
}
?>

<!-- content print! -->
<div class="row" style="margin-bottom: 40px;">

	<!-- subnet details -->
	<div class="col-sm-12 col-xs-12 <?php if($GET->sPage=="changelog" || $GET->sPage=="location") { print "col-lg-12 col-md-12";} else { print "col-lg-8 col-md-8"; } ?>">
		<!-- for adding IP address! -->
		<div id="subnetId" style="display:none;"><?php print $subnet['id']; ?></div>

        <!-- subnet details upper table -->
        <h4><?php print _('Subnet details'); ?></h4>
        <hr>

        <!-- tabs -->
        <ul class='nav nav-tabs ip-det-switcher' style='margin-bottom:20px;'>
            <li role='presentation' <?php if(!isset($GET->sPage)) print " class='active'"; ?>> <a href='<?php print create_link("subnets", $subnet['sectionId'], $subnet['id']); ?>'><?php print _("Subnet details"); ?></a></li>
            <li role='presentation' <?php if($GET->sPage=="map") print " class='active'"; ?>> <a href='<?php print create_link("subnets", $subnet['sectionId'], $subnet['id'], "map"); ?>'><?php print _("Space map"); ?></a></li>
            <li role='presentation' <?php if($GET->sPage=="mapsearch") print " class='active'"; ?>> <a href='<?php print create_link("subnets", $subnet['sectionId'], $subnet['id'], "mapsearch"); ?>'><?php print _("Mask search"); ?></a></li>
            <?php if($User->is_admin(false)) { ?>
            <li role='presentation' <?php if($GET->sPage=="permissions") print "class='active'"; ?>><a href='<?php print create_link("subnets", $subnet['sectionId'], $subnet['id'], "permissions"); ?>'><?php print _("Permissions"); ?></a></li>
            <?php } ?>
            <?php if($User->settings->enableNAT==1 && $User->get_module_permissions ("nat")>=User::ACCESS_R) { ?>
            <li role='presentation' <?php if($GET->sPage=="nat") print "class='active'"; ?>> <a href='<?php print create_link("subnets", $subnet['sectionId'], $subnet['id'], "nat"); ?>'><?php print _("NAT"); ?></a></li>
            <?php } ?>
            <?php if($User->settings->enableLocations==1 && $User->get_module_permissions ("locations")>=User::ACCESS_R) { ?>
            <li role='presentation' <?php if($GET->sPage=="location") print "class='active'"; ?>> <a href='<?php print create_link("subnets", $subnet['sectionId'], $subnet['id'], "location"); ?>'><?php print _("Location"); ?></a></li>
            <?php } ?>
            <li role='presentation' <?php if($GET->sPage=="changelog") print " class='active'"; ?>> <a href='<?php print create_link("subnets", $subnet['sectionId'], $subnet['id'], "changelog"); ?>'><?php print _("Changelog"); ?></a></li>
        </ul>

        <!-- details -->
        <?php
        if(!isset($GET->sPage)) {
        	include("subnet-details/subnet-details.php");
        }
        if($GET->sPage=="permissions") {
            include("subnet-details/subnet-permissions.php");
        }
        if($User->settings->enableNAT==1 && $GET->sPage=="nat") {
            include("subnet-details/subnet-nat.php");
        }
        if($GET->sPage=="changelog") {
            include("subnet-details/subnet-changelog.php");
        }
        if($GET->sPage=="location") {
            include("subnet-details/subnet-location.php");
        }
        if($GET->sPage=="map") {
            include("subnet-details/subnet-map.php");
        }
        if($GET->sPage=="mapsearch") {
            include("subnet-details/subnet-map-search.php");
        }
        ?>

	</div>

    <?php if($GET->sPage!="changelog") { ?>

	<!-- subnet graph -->
	<?php if($GET->sPage!="location") { ?>
	<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
		<?php include('subnet-details/subnet-graph.php'); ?>
	</div>
	<?php } ?>

	<!-- subnet slaves list -->
	<div class="col-xs-12 subnetSlaves">
		<?php
		if($slaves) {
			$slave_subnets = (array) $Subnets->fetch_subnet_slaves ($subnet['id']);
			// check slave permissions
			foreach($slave_subnets as $k=>$slave) {
				if ($Subnets->check_permission($User->user, $slave->id, $slave) == 0) {
					unset($slave_subnets[$k]);
				}
			}
			$slave_subnets = array_values($slave_subnets);
			include('subnet-slaves.php');
		}
		?>
	</div>

	<!-- addresses -->
	<div class="col-xs-12 ipaddresses_overlay">
		<?php
		if(!$slaves) {
			$addresses = $Addresses->fetch_subnet_addresses ($subnet['id']);
			include('addresses/print-address-table.php');
		}
		?>
	</div>

	<!-- visual subnet display -->
	<div class="col-xs-12">
	<?php
		if(!$slaves && $User->settings->visualLimit > 0) {
			$max_visual_hosts_subnet = (object) ['subnet'=>'10.0.0.0', 'mask'=>$User->settings->visualLimit, 'isPool'=>true];
			if($Subnets->max_hosts($subnet) <= $Subnets->max_hosts($max_visual_hosts_subnet)) {
				include('subnet-visual.php');
			}
	}
	?>
	</div>

	<!-- orphaned addresses -->
	<div class="col-xs-12">
		<?php
		# if subnet has slaves check if also has addresses - if so they are orphaed, print them!
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
	<?php } ?>

</div>
