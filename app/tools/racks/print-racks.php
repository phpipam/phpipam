<?php

/**
 * Script to print racks
 ***************************/

# verify that user is logged in
$User->check_user_session();

# set admin
$admin = $User->is_admin(false);

?>
<h4><?php print _('RACK list'); ?></h4>
<hr>

<?php if($admin && $User->settings->enableRACK=="1") { ?>
<div class="btn-group">
    <?php if($_GET['page']=="administration") { ?>
	<a href="" class='btn btn-sm btn-default  editRack' data-action='add'   data-rackid='' style='margin-bottom:10px;'><i class='fa fa-plus'></i> <?php print _('Add rack'); ?></a>
	<?php } else { ?>
	<a href="<?php print create_link("administration", "racks") ?>" class='btn btn-sm btn-default' style='margin-bottom:10px;'><i class='fa fa-pencil'></i> <?php print _('Manage'); ?></a>
	<?php } ?>
</div>
<br>
<?php } ?>

<?php

# check that rack support isenabled
if ($User->settings->enableRACK!="1") {
    $Result->show("danger", _("RACK management disabled."), false);
}
else {
    # init racks object
    $Racks = new phpipam_rack ($Database);
    # fetch all racks
    $Racks->fetch_all_racks();

    // table
    print "<table class='table sorted table-striped table-top table-td-top'>";
    // headers
    print "<thead>";
    print "<tr>";
    print " <th style='width:80px'></th>";
    print " <th>"._('Name')."</th>";
    print " <th>"._('Size')."</th>";
    print " <th>"._('Description')."</th>";
    print " <th>"._('Devices')."</th>";
	if(sizeof($custom) > 0) {
		foreach($custom as $field) {
			if(!in_array($field['name'], $hidden_custom_fields)) {
				print "<th class='hidden-xs hidden-sm hidden-md'>$field[name]</th>";
			}
		}
	}
    print "</tr>";
    print "</thead>";

    print "<tbody>";
    # none
    if ($Racks->all_racks === false) {
        print "<tr>";
        print " <td colspan='5'>".$Result->show("info", _("No racks available"), false, false, true)."</td>";
        print "</tr>";
    }
    # print
    else {
        foreach ($Racks->all_racks as $r) {
            // fetch rack devices
            $rack_devices = $Racks->fetch_rack_devices ($r->id);

            // print
            print "<tr>";
            // links
    		print "	<td class='actions'>";
    		print "	<div class='btn-group'>";
    		print "		<a href='' class='btn btn-xs btn-default editRack' data-action='edit'   data-rackid='$r->id'><i class='fa fa-pencil'></i></a>";
    		print "		<a href='' class='btn btn-xs btn-default showRackPopup' data-rackId='$r->id' data-deviceId='0'><i class='fa fa-server'></i></a>";
    		print "		<a href='' class='btn btn-xs btn-default editRack' data-action='delete' data-rackid='$r->id'><i class='fa fa-times'></i></a>";
    		print "	</div>";
    		print " </td>";

            print " <td><a href='".create_link($_GET['page'], "racks", $r->id)."'>$r->name</a></td>";
            print " <td>$r->size U</td>";
            print " <td>$r->description</td>";
            // devices
            if ($rack_devices===false) {
                print "<td>";
                print " <span class='text-muted'>"._("Rack is empty")."</span>";
                if($admin) {
                    print " <hr>";
                    print "	<a href='' class='btn btn-xs btn-default editRackDevice' data-action='add' data-rackid='$r->id' data-deviceid='0'><i class='fa fa-plus'></i></a> "._("Add device");
                }
                print "</td>";
            }
            else {
                print "<td>";
                foreach ($rack_devices as $k=>$d) {
                    // validate diff
                    if ($k!=0) {
                        $error = $d->rack_start < ((int) $rack_devices[$k-1]->rack_start + (int) $rack_devices[$k-1]->rack_size) ? "alert-danger" : "";
                    }
                    if($admin) {
                        print "<a href='' class='btn btn-xs btn-default btn-danger editRackDevice' data-action='remove' rel='tooltip' data-html='true' data-placement='left' title='"._("Remove")."' data-action='remove' style='margin-bottom:2px;margin-right:5px;' data-rackid='$r->id' data-deviceid='$d->id' data-csrf='$csrf'><i class='fa fa-times'></i></a> ";
                        print "<span class='badge badge1 badge5 $error' style='margin-bottom:3px;margin-right:5px;'>"._("Position").": $d->rack_start, "._("Size").": $d->rack_size U</span>";
                        print " <a href='".create_link("tools", "devices", $d->id)."'>$d->hostname</a><br>";
                    }
                    else {
                        print "<span class='badge badge1 badge5 $error' style='margin-bottom:3px;margin-right:5px;'>"._("Position").": $d->rack_start, "._("Size").": $d->rack_size U</span>";
                        print " <a href='".create_link("tools", "devices", $d->id)."'>$d->hostname</a><br>";

                    }

                }

                //add / remove device from rack
                if($admin) {
                    print "<hr>";
            		print "	<a href='' class='btn btn-xs btn-default editRackDevice' data-action='add' data-rackid='$r->id' data-deviceid='0'><i class='fa fa-plus'></i></a> "._("Add device");
                }
                print "</td>";
            }

    		//custom
    		if(sizeof($custom) > 0) {
    			foreach($custom as $field) {
    				if(!in_array($field['name'], $hidden_custom_fields)) {
    					print "<td class='hidden-xs hidden-sm hidden-md'>";

    					// create links
    					$r->{$field['name']} = $Result->create_links ($r->{$field['name']}, $field['type']);

    					//booleans
    					if($field['type']=="tinyint(1)")	{
    						if($r->{$field['name']} == "0")		{ print _("No"); }
    						elseif($r->{$field['name']} == "1")	{ print _("Yes"); }
    					}
    					//text
    					elseif($field['type']=="text") {
    						if(strlen($r->{$field['name']})>0)	{ print "<i class='fa fa-gray fa-comment' rel='tooltip' data-container='body' data-html='true' title='".str_replace("\n", "<br>", $r->{$field['name']})."'>"; }
    						else								{ print ""; }
    					}
    					else {
    						print $r->{$field['name']};

    					}
    					print "</td>";
    				}
    			}
    		}

            print "</tr>";
        }
    }
    print "</tbody>";
    print "</table>";
}
?>