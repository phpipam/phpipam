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
    if(!is_numeric($_GET['subnetId']))      { $error = _("Invalid rack Id"); }
    # init racks object
    $Racks = new phpipam_rack ($Database);
    # fetch all racks
    $rack = $Racks->fetch_rack_details ($_GET['subnetId']);
    $rack_devices = $Racks->fetch_rack_devices ($_GET['subnetId']);

    // rack check
    if($rack===false)                       { $error =_("Invalid rack Id"); }

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
} else {
?>
<h4><?php print _('RACK details'); ?> (<?php print $rack->name; ?>)</h4>
<hr>

<div class="btn-group" style="margin-bottom: 20px;">
	<a href='javascript:history.back()' class='btn btn-sm btn-default' style='margin-bottom:10px;'><i class='fa fa-chevron-left'></i> <?php print _('Racks'); ?></a>
</div>

    <!-- draw -->
    <img src="<?php print $Tools->create_rack_link ($rack->id); ?>" class='pull-right' style='width:200px;'>

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

	<?php
	# print custom subnet fields if any
	if(sizeof($cfields) > 0) {
		// divider
		print "<tr><td colspan='2'><hr></td></tr>";
		// fields
		foreach($cfields as $key=>$field) {
			$rack->$key = str_replace("\n", "<br>",$rack->$key);
			// create links
			$rack->$key = $Result->create_links($rack->$key);
			print "<tr>";
			print "	<th>$key</th>";
			print "	<td style='vertical-align:top;align:left;'>".$rack->$key."</td>";
			print "</tr>";
		}
		// divider
		print "<tr><td colspan='2'><hr></td></tr>";
	}

	# action button groups
	print "<tr>";
	print "	<th style='vertical-align:bottom;align:left;'>"._('Actions')."</th>";
	print "	<td style='vertical-align:bottom;align:left;'>";

	print "	<div class='btn-toolbar' style='margin-bottom:0px'>";
	print "	<div class='btn-group'>";

	# permissions
	if($User->is_admin (false)) {
   		print "		<a href='' class='btn btn-xs btn-default editRack' data-action='edit'   data-rackid='$rack->id'><i class='fa fa-pencil'></i></a>";
        print "		<a href='' class='btn btn-xs btn-default editRack' data-action='delete' data-rackid='$rack->id'><i class='fa fa-times'></i></a>";
	}

	print "	</div>";
	print "	</div>";

	print "	</td>";
	print "</tr>";

	// divider
	print "<tr><td colspan='2'><hr></td></tr>";

	// attached devices
	print "<tr>";
	print " <th>"._('Devices')."</th>";
	print " <td>";

    // devices
    if ($rack_devices===false) {
        print " <span class='text-muted'>"._("Rack is empty")."</span>";
    }
    else {
        foreach ($rack_devices as $k=>$d) {
            // validate diff
            if ($k!=0) {
                $error = $d->rack_start < ((int) $rack_devices[$k-1]->rack_start + (int) $rack_devices[$k-1]->rack_size) ? "alert-danger" : "";
            }
            if ($admin) {
                print "<a href='' class='btn btn-xs btn-default btn-danger editRackDevice' rel='tooltip' data-html='true' data-placement='left' title='"._("Remove")."' data-action='remove' style='margin-bottom:2px;margin-right:5px;' data-rackid='$rack->id' data-deviceid='$d->id' data-csrf='$csrf'><i class='fa fa-times'></i></a>";
                print "<span class='badge badge1 badge5 $error' style='margin-bottom:3px;margin-right:5px;'>"._("Position").": $d->rack_start, "._("Size").": $d->rack_size U</span>";
                print " <a href='".create_link("tools", "devices", "hosts", $d->id)."'>$d->hostname</a><br>";
            }
            else {
                print "<span class='badge badge1 badge5 $error' style='margin-bottom:3px;margin-right:5px;'>"._("Position").": $d->rack_start, "._("Size").": $d->rack_size U</span>";
                print " <a href='".create_link("tools", "devices", "hosts", $d->id)."'>$d->hostname</a><br>";
            }
        }
    }
    // add
    if ($admin) {
        print " <hr>";
        print "	<a href='' class='btn btn-xs btn-default editRackDevice' data-action='add' data-rackid='$rack->id' data-deviceid='0'><i class='fa fa-plus'></i></a> "._("Add device");
    }

	print " </td>";
	print "</tr>";
	?>

    </table>

</div>

<?php } ?>
