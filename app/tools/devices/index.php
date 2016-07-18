<?php

/**
 * Script to display devices
 *
 */

# verify that user is logged in
$User->check_user_session();


if(isset($_GET['subnetId'])) {
    # check
    is_numeric($_GET['subnetId']) ? : $Result->show("danger", _("Invalid ID"), true);

    # fetch device
    $device = $Tools->fetch_object ("devices", "id", $_GET['subnetId']);

    # count subnets and addresses
    $cnt_subnets   = $Tools->count_database_objects ("subnets", "device", $device->id);
    $cnt_addresses = $Tools->count_database_objects ("ipaddresses", "switch", $device->id);
    $cnt_nat       = $Tools->count_database_objects ("nat", "device", $device->id);
    $cnt_pstn      = $Tools->count_database_objects ("pstnPrefixes", "deviceId", $device->id);

    ?>
    <!-- tabs -->
    <ul class='nav nav-tabs' style='margin-bottom:20px;'>
        <li role='presentation' <?php if(!isset($_GET['sPage'])) print " class='active'"; ?>> <a href='<?php print create_link("tools", "devices", $device->id); ?>'><?php print _("Device details"); ?></a></li>
        <li role='presentation' <?php if(@$_GET['sPage']=="subnets") print "class='active'"; ?>> <a href='<?php print create_link("tools", "devices", $device->id, $subnet['id'], "subnets"); ?>'><?php print _("Subnets"); ?> <span class='badge' style="margin-left: 5px;"><?php print $cnt_subnets; ?></span></a></li>
        <li role='presentation' <?php if(@$_GET['sPage']=="addresses") print "class='active'"; ?>> <a href='<?php print create_link("tools", "devices", $device->id, $subnet['id'], "addresses"); ?>'><?php print _("Addresses"); ?> <span class='badge' style="margin-left: 5px;"><?php print $cnt_addresses; ?></span></a></li>
        <?php if($User->settings->enableNAT==1) { ?>
        <li role='presentation' <?php if(@$_GET['sPage']=="nat") print "class='active'"; ?>> <a href='<?php print create_link("tools", "devices", $device->id, $subnet['id'], "nat"); ?>'><?php print _("NAT"); ?> <span class='badge' style="margin-left: 5px;"><?php print $cnt_nat; ?></span></a></li>
        <?php } ?>
        <?php if($User->settings->enableLocations==1) { ?>
        <li role='presentation' <?php if(@$_GET['sPage']=="location") print "class='active'"; ?>> <a href='<?php print create_link("tools", "devices", $device->id, "location"); ?>'><?php print _("Location"); ?></a></li>
        <?php } ?>
        <?php if($User->settings->enablePSTN==1) { ?>
        <li role='presentation' <?php if(@$_GET['sPage']=="pstn-prefixes") print "class='active'"; ?>> <a href='<?php print create_link("tools", "devices", $device->id, "pstn-prefixes"); ?>'><?php print _("PSTN prefixes"); ?> <span class='badge' style="margin-left: 5px;"><?php print $cnt_pstn; ?></span></a></li>
        <?php } ?>

    </ul>

    <!-- details -->
    <?php
    if(!isset($_GET['sPage'])) {
    	include("device-details/device-details.php");
    }
    elseif(@$_GET['sPage']=="subnets") {
        include("device-details/device-subnets.php");
    }
    elseif(@$_GET['sPage']=="addresses") {
        include("device-details/device-addresses.php");
    }
    elseif($User->settings->enableNAT==1 && @$_GET['sPage']=="nat") {
        include("device-details/device-nat.php");
    }
    elseif(@$_GET['sPage']=="location") {
        include("device-details/device-location.php");
    }
    elseif(@$_GET['sPage']=="pstn-prefixes") {
        include("device-details/device-pstn.php");
    }
} else {
	include('all-devices.php');
}

?>