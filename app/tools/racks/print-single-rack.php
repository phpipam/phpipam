<?php

/**
 * Script to print racks
 ***************************/

# verify that user is logged in
$User->check_user_session();

# set admin
$admin = $User->is_admin(false);

# check that rack support isenabled
if ($User->settings->enableRACK!="1") {
    $Result->show("danger", _("RACK management disabled."), false);
}
else {
    # validate integer
    if(!is_numeric($_GET['subnetId']))      { header("Location: ".create_link($_GET['page'], "racks")); $error =_("Invalid rack Id"); }
    # init racks object
    $Racks = new phpipam_rack ($Database);
    # fetch all racks
    $rack = $Racks->fetch_rack_details ($_GET['subnetId']);
    $rack_devices = $Racks->fetch_rack_devices ($_GET['subnetId']);

    // rack check
    if($rack===false)                       { header("Location: ".create_link($_GET['page'], "racks")); $error =_("Invalid rack Id"); }

    // get custom fields
    $cfields = $Tools->fetch_custom_fields ('racks');
}

# if error set print it, otherwise print rack
if (isset($error)) { ?>
    <h4><?php print _('RACK details'); ?></h4>
    <hr>

    <div class="btn-group">
    	<a href='javascript:history.back()' class='btn btn-sm btn-default' style='margin-bottom:10px;'><i class='fa fa-chevron-left'></i> <?php print _('Racks'); ?></a>
    </div>
    <br>
    <?php $Result->show("danger", $error, false); ?>
    <?php
}
else {
?>

<h4><?php print _('RACK details'); ?> (<?php print $rack->name; ?>)</h4>
<hr>

<div class="row">

    <!-- details -->
    <div class="col-xs-12 col-md-6">
        <div class="btn-group" style="margin-bottom: 20px;">
            <a href='javascript:history.back()' class='btn btn-sm btn-default' style='margin-bottom:10px;'><i class='fa fa-chevron-left'></i> <?php print _('Racks'); ?></a>
        </div>

        <!-- table -->
        <table class="ipaddress_subnet table-condensed table-auto">

        <tr>
            <th><?php print _("Name"); ?></th>
            <td><?php print $rack->name; ?></td>
        </tr>

        <tr>
            <th><?php print _("Size"); ?></th>
            <td><?php print $rack->size; ?> U</td>
        </tr>

        <tr>
            <th><?php print _("Description"); ?></th>
            <td><?php print $rack->description; ?> U</td>
        </tr>

        <!-- Location -->
        <?php if($User->settings->enableLocations=="1") { ?>
        <tr>
            <th><?php print _('Location'); ?></th>
            <td>
            <?php

            // Only show nameservers if defined for subnet
            if(!empty($rack->location) && $rack->location!=0) {
                # fetch recursive nameserver details
                $location2 = $Tools->fetch_object("locations", "id", $rack->location);
                if($location2!==false) {
                    print "<a href='".create_link("tools", "locations", $rack->location)."'>$location2->name</a>";
                }
            }

            else {
                print "<span class='text-muted'>/</span>";
            }
            ?>
            </td>
        </tr>
        <?php } ?>

        <?php
        # print custom subnet fields if any
        if(sizeof($cfields) > 0) {
            // divider
            print "<tr><td colspan='2'><hr></td></tr>";
            // fields
            foreach($cfields as $key=>$field) {
                $rack->{$key} = str_replace("\n", "<br>",$rack->{$key});
                // create links
                $rack->{$key} = $Result->create_links($rack->{$key});
                print "<tr>";
                print " <th>".$Tools->print_custom_field_name ($key)."</th>";
                print " <td style='vertical-align:top;align:left;'>".$rack->{$key}."</td>";
                print "</tr>";
            }
            // divider
            print "<tr><td colspan='2'><hr></td></tr>";
        }

        # action button groups
        print "<tr>";
        print " <th style='vertical-align:bottom;align:left;'>"._('Actions')."</th>";
        print " <td style='vertical-align:bottom;align:left;'>";

        print " <div class='btn-toolbar' style='margin-bottom:0px'>";
        print " <div class='btn-group'>";

        # permissions
        if($User->is_admin (false)) {
                print "     <a href='' class='btn btn-xs btn-default editRack' data-action='edit'   data-rackid='$rack->id'><i class='fa fa-pencil'></i></a>";
            print "     <a href='' class='btn btn-xs btn-default editRack' data-action='delete' data-rackid='$rack->id'><i class='fa fa-times'></i></a>";
        }

        print " </div>";
        print " </div>";

        print " </td>";
        print "</tr>";

        // divider
        print "<tr><td colspan='2'><hr></td></tr>";

        // attached devices
        print "<tr>";
        print " <th>"._('Devices')."</th>";
        print " <td style='padding-bottom:20px;'>";



        // devices
        if ($rack_devices===false) {
            print " <span class='text-muted'>"._("Rack is empty")."</span>";
            if($admin) {
                print " <hr>";
                print " <a href='' class='btn btn-xs btn-default editRackDevice' data-action='add' data-rackid='$rack->id' data-deviceid='0'><i class='fa fa-plus'></i></a> "._("Add device");
            }
        }
        else {
            $is_back =  false;
            foreach ($rack_devices as $k=>$d) {
                // validate diff
                if ($k!=0) {
                    $error = $d->rack_start < ((int) $rack_devices[$k-1]->rack_start + (int) $rack_devices[$k-1]->rack_size) ? "alert-danger" : "";
                }

                // first
                if($k==0 && $rack->hasBack!="0") {
                    print _("Front side").":<hr>";
                }
                // first in back
                if ($rack->hasBack!="0" && $d->rack_start>$rack->size && !$is_back) {
                    print "<br>"._("Back side").":<hr>";
                    $is_back = true;
                }

                // reformat front / back start position
                if($rack->hasBack!="0" && $d->rack_start>$rack->size) {
                    $d->rack_start_print = $d->rack_start - $rack->size;
                }
                else {
                    $d->rack_start_print = $d->rack_start;
                }

                if($admin) {
                    print "<a href='' class='btn btn-xs btn-default btn-danger editRackDevice' data-action='remove' rel='tooltip' data-html='true' data-placement='left' title='"._("Remove")."' data-action='remove' style='margin-bottom:2px;margin-right:5px;' data-rackid='$rack->id' data-deviceid='$d->id' data-csrf='".$User->Crypto->csrf_cookie ("create", "rack_devices_".$rack->id."_device_".$d->id)."'><i class='fa fa-times'></i></a> ";
                    print "<span class='badge badge1 badge5 $error' style='margin-bottom:3px;margin-right:5px;'>"._("Position").": $d->rack_start_print, "._("Size").": $d->rack_size U</span>";
                    print " <a href='".create_link("tools", "devices", $d->id)."'>$d->hostname</a><br>";
                }
                else {
                    print "<span class='badge badge1 badge5 $error' style='margin-bottom:3px;margin-right:5px;'>"._("Position").": $d->rack_start_print, "._("Size").": $d->rack_size U</span>";
                    print " <a href='".create_link("tools", "devices", $d->id)."'>$d->hostname</a><br>";

                }

            }

            //add / remove device from rack
            if($admin) {
                print "<hr>";
                print " <a href='' class='btn btn-xs btn-default editRackDevice' data-action='add' data-rackid='$rack->id' data-deviceid='0'><i class='fa fa-plus'></i></a> "._("Add device");
            }
        }
        print "</td>";
        print "</tr>";
        ?>

        <?php if($User->settings->enableLocations==1 && strlen($rack->location)>0 && $rack->location!=0) { ?>
        <tr><td colspan='2' style="padding-top:50px !important;"><hr></td></tr>
        <tr>
            <td colspan="2">
                <img src="<?php print $Tools->create_rack_link ($rack->id); ?>" style='width:200px;'>
                <?php if($rack->hasBack!="0") { ?>
                <img src="<?php print $Tools->create_rack_link ($rack->id, NULL, true); ?>" style='width:200px;margin-left:5px;'>
                <?php } ?>
            </td>
        </tr>
        <?php } ?>

        </table>
    </div>

    <!-- location -->
    <?php if($User->settings->enableLocations==1 && strlen($rack->location)>0 && $rack->location!=0) {  ?>
    <div class="col-xs-12 col-md-6">

        <div>
        <h4><?php print _('Location');?></h4><hr>
            <?php
                // fake data
                unset($location);
                $location_index = $rack->location;
                $resize = false;
                $height = "500px;";

                include(dirname(__FILE__).'/../locations/single-location-map.php');
            ?>
        </div>
    </div>
    <?php } ?>

    <!-- image -->
    <?php if(!($User->settings->enableLocations==1 && strlen($rack->location)>0 && $rack->location!=0)) { ?>
    <div class="col-xs-12 col-md-6">
        <?php if($rack->hasBack!="0") { ?>
        <img src="<?php print $Tools->create_rack_link ($rack->id, NULL, true); ?>" style='width:200px;margin-left:5px;float:right;'>
        <?php } ?>
        <img src="<?php print $Tools->create_rack_link ($rack->id); ?>" style='width:200px;float:right;'>
    </div>
    <?php } ?>

</div>


<?php } ?>
