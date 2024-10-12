<?php

/**
 * Script to display devices
 *
 */

# verify that user is logged in
$User->check_user_session();


# perm check
if ($User->get_module_permissions ("devices")==User::ACCESS_NONE) {
    $Result->show("danger", _("You do not have permissions to access this module"), false);
}
# details
elseif(isset($GET->subnetId)) {
    # device by types ?
    if ($GET->subnetId=="type" ||
        $GET->subnetId=="section" ||
        $GET->subnetId=="rack" ||
        $GET->subnetId=="location") {
        include('all-devices.php');
    }
    # by id
    else {
        # check
        is_numeric($GET->subnetId) ? : $Result->show("danger", _("Invalid ID"), true);

        # fetch device
        $device = $Tools->fetch_object ("devices", "id", $GET->subnetId);

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
            <li role='presentation' <?php if(!isset($GET->sPage)) print " class='active'"; ?>> <a href='<?php print create_link("tools", "devices", $device->id); ?>'><?php print _("Device details"); ?></a> </li>
            <?php if($User->settings->enableLocations==1 && $User->get_module_permissions ("locations")>=User::ACCESS_R) { ?>
            <li role='presentation' <?php if($GET->sPage=="location") print "class='active'"; ?>> <a href='<?php print create_link("tools", "devices", $device->id, "location"); ?>'><?php print _("Location"); ?></a></li>
            <?php } ?>
            <li role='presentation' <?php if($GET->sPage=="subnets") print "class='active'"; ?>> <a href='<?php print create_link("tools", "devices", $device->id, "subnets"); ?>'><?php print _("Subnets"); ?> <span class='badge badge1 badge5' style="margin-left:5px;display:inline;"><?php print $cnt_subnets; ?></span></a></li>
            <li role='presentation' <?php if($GET->sPage=="addresses") print "class='active'"; ?>> <a href='<?php print create_link("tools", "devices", $device->id, "addresses"); ?>'><?php print _("Addresses"); ?> <span class='badge badge1 badge5' style="margin-left:5px;display:inline;"><?php print $cnt_addresses; ?></span></a></li>
            <?php if($User->settings->enableNAT==1 && $User->get_module_permissions ("nat")>=User::ACCESS_R) { ?>
            <li role='presentation' <?php if($GET->sPage=="nat") print "class='active'"; ?>> <a href='<?php print create_link("tools", "devices", $device->id, "nat"); ?>'><?php print _("NAT"); ?> <span class='badge badge1 badge5' style="margin-left:5px;display:inline;"><?php print $cnt_nat; ?></span></a></li>
            <?php } ?>
            <?php if($User->settings->enablePSTN==1 && $User->get_module_permissions ("pstn")>=User::ACCESS_R) { ?>
            <li role='presentation' <?php if($GET->sPage=="pstn-prefixes") print "class='active'"; ?>> <a href='<?php print create_link("tools", "devices", $device->id, "pstn-prefixes"); ?>'><?php print _("PSTN prefixes"); ?> <span class='badge badge1 badge5' style="margin-left:5px;display:inline;"><?php print $cnt_pstn; ?></span></a></li>
            <?php } ?>
            <?php if($User->settings->enableCircuits==1 && $User->get_module_permissions ("circuits")>=User::ACCESS_R) { ?>
            <li role='presentation' <?php if($GET->sPage=="circuits") print "class='active'"; ?>> <a href='<?php print create_link("tools", "devices", $device->id, "circuits"); ?>'><?php print _("Circuits"); ?> <span class='badge badge1 badge5' style="margin-left:5px;display:inline;"><?php print $cnt_circuits; ?></span></a></li>
            <?php } ?>
        </ul>

        <!-- details -->
        <?php
        if(!isset($GET->sPage)) {
        	include("device-details/device-details.php");
        }
        elseif($GET->sPage=="subnets") {
            include("device-details/device-subnets.php");
        }
        elseif($GET->sPage=="addresses") {
            include("device-details/device-addresses.php");
        }
        elseif($User->settings->enableNAT==1 && $GET->sPage=="nat") {
            include("device-details/device-nat.php");
        }
        elseif($GET->sPage=="location") {
            include("device-details/device-location.php");
        }
        elseif($GET->sPage=="pstn-prefixes") {
            include("device-details/device-pstn.php");
        }
        elseif($GET->sPage=="circuits") {
            include("device-details/device-circuits.php");
        }
    }
} else {
	include('all-devices.php');
}