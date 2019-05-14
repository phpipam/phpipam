<?php

/**
 * Script to display devices
 *
 */

# verify that user is logged in
$User->check_user_session();


# perm check
if ($User->get_module_permissions ("devices")<1) {
    $Result->show("danger", _("You do not have permissions to access this module"), false);
}
# details
elseif(isset($_GET['subnetId'])) {
    # device by types ?
    if ($_GET['subnetId']=="type" ||
        $_GET['subnetId']=="section" ||
        $_GET['subnetId']=="rack" ||
        $_GET['subnetId']=="location") {
        include('all-devices.php');
    }
    # by id
    else {
        # check
        is_numeric($_GET['subnetId']) ? : $Result->show("danger", _("Invalid ID"), true);

        # fetch device
        $device = $Tools->fetch_object ("devices", "id", $_GET['subnetId']);

        # count subnets and addresses
        $cnt_subnets   = $Tools->count_database_objects ("subnets", "device", $device->id);
        $cnt_addresses = $Tools->count_database_objects ("ipaddresses", "switch", $device->id);
        if($User->settings->enableNAT=="1")
        $cnt_nat       = $Tools->count_database_objects ("nat", "device", $device->id);
        if($User->settings->enablePSTN=="1")
        $cnt_pstn      = $Tools->count_database_objects ("pstnPrefixes", "deviceId", $device->id);
        if($User->settings->enableCircuits=="1") {
        $cnt_circuits  = $Tools->count_database_objects ("circuits", "device1", $device->id);
        $cnt_circuits  = $cnt_circuits + $Tools->count_database_objects ("circuits", "device2", $device->id);
        }
        ?>

        <?php if($device!==false) { ?>
        <h4>
            <?php print _("Device")." "._($device->hostname); ?></h4><hr>
        <?php } ?>

        <!-- tabs -->
        <ul class='nav nav-tabs' style='margin-bottom:20px;'>
            <li role='presentation' <?php if(!isset($_GET['sPage'])) print " class='active'"; ?>> <a href='<?php print create_link("tools", "devices", $device->id); ?>'><?php print _("Device details"); ?></a> </li>
            <?php if($User->settings->enableLocations==1 && $User->get_module_permissions ("locations")>0) { ?>
            <li role='presentation' <?php if(@$_GET['sPage']=="location") print "class='active'"; ?>> <a href='<?php print create_link("tools", "devices", $device->id, "location"); ?>'><?php print _("Location"); ?></a></li>
            <?php } ?>
            <li role='presentation' <?php if(@$_GET['sPage']=="subnets") print "class='active'"; ?>> <a href='<?php print create_link("tools", "devices", $device->id, "subnets", $subnet['id']); ?>'><?php print _("Subnets"); ?> <span class='badge badge1 badge5' style="margin-left:5px;display:inline;"><?php print $cnt_subnets; ?></span></a></li>
            <li role='presentation' <?php if(@$_GET['sPage']=="addresses") print "class='active'"; ?>> <a href='<?php print create_link("tools", "devices", $device->id, "addresses", $subnet['id']); ?>'><?php print _("Addresses"); ?> <span class='badge badge1 badge5' style="margin-left:5px;display:inline;"><?php print $cnt_addresses; ?></span></a></li>
            <?php if($User->settings->enableNAT==1 && $User->get_module_permissions ("nat")>0) { ?>
            <li role='presentation' <?php if(@$_GET['sPage']=="nat") print "class='active'"; ?>> <a href='<?php print create_link("tools", "devices", $device->id, "nat", $subnet['id']); ?>'><?php print _("NAT"); ?> <span class='badge badge1 badge5' style="margin-left:5px;display:inline;"><?php print $cnt_nat; ?></span></a></li>
            <?php } ?>
            <?php if($User->settings->enablePSTN==1 && $User->get_module_permissions ("pstn")>0) { ?>
            <li role='presentation' <?php if(@$_GET['sPage']=="pstn-prefixes") print "class='active'"; ?>> <a href='<?php print create_link("tools", "devices", $device->id, "pstn-prefixes"); ?>'><?php print _("PSTN prefixes"); ?> <span class='badge badge1 badge5' style="margin-left:5px;display:inline;"><?php print $cnt_pstn; ?></span></a></li>
            <?php } ?>
            <?php if($User->settings->enableCircuits==1 && $User->get_module_permissions ("circuits")>0) { ?>
            <li role='presentation' <?php if(@$_GET['sPage']=="circuits") print "class='active'"; ?>> <a href='<?php print create_link("tools", "devices", $device->id, "circuits"); ?>'><?php print _("Circuits"); ?> <span class='badge badge1 badge5' style="margin-left:5px;display:inline;"><?php print $cnt_circuits; ?></span></a></li>
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
        elseif(@$_GET['sPage']=="circuits") {
            include("device-details/device-circuits.php");
        }
    }
} else {
	include('all-devices.php');
}