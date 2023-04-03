<?php

/**
 * Script to print racks
 ***************************/

# verify that user is logged in
$User->check_user_session();

# verify module permissions
$User->check_module_permissions ("racks", User::ACCESS_R, true);

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
    $rack_contents = $Racks->fetch_rack_contents ($_GET['subnetId']);
    $Racks->add_rack_start_print($rack_devices);
    $Racks->add_rack_start_print($rack_contents);

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


# customer
if ($User->settings->enableCustomers=="1" && $User->get_module_permissions ("customers")>=User::ACCESS_R) {
    $customer = $Tools->fetch_object ("customers", "id", $rack->customer_id);
}
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
            <td><?php print $rack->description; ?></td>
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

        <?php if ($User->settings->enableCustomers=="1" &&  $User->get_module_permissions ("customers")>=User::ACCESS_R) { ?>
        <tr>
            <td colspan='2'><hr></td>
        </tr>
        <tr>
            <th><?php print _('Customer'); ?></th>
            <td>
                <?php
                if($customer!==false && $User->get_module_permissions ("customers")>=User::ACCESS_R)
                print $customer->title . " <a target='_blank' href='".create_link("tools","customers",$customer->title)."'><i class='fa fa-external-link'></i></a>";
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
                $rack->{$key} = $Tools->create_links($rack->{$key});
                print "<tr>";
                print " <th>".$Tools->print_custom_field_name ($key)."</th>";
                print " <td style='vertical-align:top;align-content:left;'>".$rack->{$key}."</td>";
                print "</tr>";
            }
            // divider
            print "<tr><td colspan='2'><hr></td></tr>";
        }

        # action button groups
        if($User->get_module_permissions ("racks")>=User::ACCESS_RW) {
            print "<tr>";
            print " <th style='vertical-align:bottom;align-content:left;'>"._('Actions')."</th>";
            print "<td class='actions'>";


            $links = [];
            # permissions
            if($User->get_module_permissions ("racks")>=User::ACCESS_RW) {
                $links[] = ["type"=>"header", "text"=>_("Manage")];
                $links[] = ["type"=>"link", "text"=>_("Edit rack"), "href"=>"", "class"=>"editRack", "dataparams"=>" data-action='edit' data-rackid='$rack->id'", "icon"=>"pencil"];
            }
            if($User->get_module_permissions ("racks")>=User::ACCESS_RWA) {
                $links[] = ["type"=>"link", "text"=>_("Delete rack"), "href"=>"", "class"=>"editRack", "dataparams"=>" data-action='delete' data-rackid='$rack->id'", "icon"=>"times"];
            }
            // print links
            print $User->print_actions($User->user->compress_actions, $links, true, true);
            print "</td>";



            print "</tr>";

            // divider
            print "<tr><td colspan='2'><hr></td></tr>";
        }


        // attached devices
        if($User->get_module_permissions ("devices")>=User::ACCESS_R) {
        print "<tr>";
        print " <th>"._('Devices')."</th>";
        print " <td style='padding-bottom:20px;'>";

        // devices
        if ($rack_devices===false && $rack_contents===false) {
            print " <span class='text-muted'>"._("Rack is empty")."</span>";
            if($User->get_module_permissions ("racks")>=User::ACCESS_RW) {
                print " <hr>";
                print " <a href='' class='btn btn-xs btn-default btn-success editRackDevice' data-action='add' data-rackid='$rack->id' data-deviceid='0' data-devicetype='device'><i class='fa fa-plus'></i></a> "._("Add device");
                print "<br>";
                print " <a href='' class='btn btn-xs btn-default btn-success editRackDevice' data-action='add' data-rackid='$rack->id' data-deviceid='0' data-devicetype='content'><i class='fa fa-plus'></i></a> "._("Add custom equipment");
            }
        }
        else {
            if ($rack_devices===false) $rack_devices = array();
            if ($rack_contents===false) $rack_contents = array();

            reset($rack_devices);
            reset($rack_contents);
            $prev = false;
            $is_back =  false;
            $error = "";
            do {
                if (!($cd = current($rack_devices))) {
                    $cur = current($rack_contents);
                    next($rack_contents);
                    $ctype = 'content';
                } elseif (!($cc = current($rack_contents))) {
                    $cur = current($rack_devices);
                    next($rack_devices);
                    $ctype = 'device';
                } else {
                    if ($cd->rack_start < $cc->rack_start) {
                        $cur = $cd;
                        $ctype = 'device';
                        next($rack_devices);
                    } else {
                        $cur = $cc;
                        next($rack_contents);
                        $ctype = 'content';
                    }
                }
                if ($cur === false) break; # done here

                // validate diff
                if ($prev!==false) {
                    $error = $cur->rack_start < ((int) $prev->rack_start + (int) $prev->rack_size) ? "alert-danger" : "";
                }

                // first
                if($prev===false && $rack->hasBack!="0") {
                    print _("Front side").":<hr>";
                }

                // first in back
                if ($rack->hasBack!="0" && $cur->rack_start>$rack->size && !$is_back) {
                    print "<br>"._("Back side").":<hr>";
                    $is_back = true;
                }

                if($User->get_module_permissions ("racks")>=User::ACCESS_RW) {
                    print "<a href='' class='btn btn-xs btn-default btn-danger editRackDevice' data-action='remove' rel='tooltip' data-html='true' data-placement='left' title='"._("Remove")."' data-action='remove' style='margin-bottom:2px;margin-right:5px;' data-rackid='$rack->id' data-deviceid='$cur->id' data-devicetype='$ctype' data-csrf='".$User->Crypto->csrf_cookie ("create-if-not-exists", "rack_devices_".$rack->id."_device_".$cur->id)."'><i class='fa fa-times'></i></a> ";
                }
                print "<span class='badge badge1 badge5 $error' style='margin-bottom:3px;margin-right:5px;'>"._("Position").": $cur->rack_start_print, "._("Size").": $cur->rack_size U</span>";
                if ($ctype == 'device') {
                    print " <a href='".create_link("tools", "devices", $cur->id)."'>$cur->hostname</a><br>";
                } else {
                    print " $cur->name<br>";
                }

                # next
                $prev = $cur;
            } while ($cur);

            //add / remove device from rack
            if($User->get_module_permissions ("racks")>=User::ACCESS_RW) {
                print "<hr>";
                print " <a href='' class='btn btn-xs btn-default btn-success editRackDevice' data-action='add' data-rackid='$rack->id' data-deviceid='0' data-devicetype='device'><i class='fa fa-plus'></i></a> "._("Add device");
                print "<br>";
                print " <a href='' class='btn btn-xs btn-default btn-success editRackDevice' data-action='add' data-rackid='$rack->id' data-deviceid='0' data-devicetype='content'><i class='fa fa-plus'></i></a> "._("Add custom equipment");
            }
        }
        print "</td>";
        print "</tr>";
        }
        ?>

        <?php if($User->settings->enableLocations==1 && !is_blank($rack->location) && $rack->location!=0) { ?>
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
    <?php if($User->settings->enableLocations==1 && !is_blank($rack->location) && $rack->location!=0) {  ?>
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
    <?php if(!($User->settings->enableLocations==1 && !is_blank($rack->location) && $rack->location!=0)) { ?>
    <div class="col-xs-12 col-md-6">
        <?php if($rack->hasBack!="0") { ?>
        <img src="<?php print $Tools->create_rack_link ($rack->id, NULL, true); ?>" style='width:200px;margin-left:5px;float:right;'>
        <?php } ?>
        <img src="<?php print $Tools->create_rack_link ($rack->id); ?>" style='width:200px;float:right;'>
    </div>
    <?php } ?>

</div>


<?php } ?>
