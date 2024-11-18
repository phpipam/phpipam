<?php

/**
 * Script to print nats
 ***************************/

# verify that user is logged in
$User->check_user_session();
?>

<h4><?php print _('NAT translations'); ?></h4>
<hr>


<?php if($User->settings->enableNAT=="1" && $User->get_module_permissions ("nat")>=User::ACCESS_RWA) { ?>
<div class="btn-group">
	<a href="" class='btn btn-sm btn-default open_popup' data-script='app/admin/nat/edit.php' data-class='700' data-action='add' data-id='' style='margin-bottom:10px;'><i class='fa fa-plus'></i> <?php print _('Add nat'); ?></a>
    <a class='btn btn-sm btn-default open_popup' data-script='app/admin/nat/cleanup.php' data-class='700'><i class="fa fa-legal"></i> <?php print _('Cleanup'); ?></a>
</div>
<br>
<?php } ?>

<?php
# check that nat support isenabled
if ($User->settings->enableNAT!="1") {
    $Result->show("danger", _("NAT module disabled."), false);
}
# no access
elseif ($User->check_module_permissions ("nat", User::ACCESS_R, false, false)===false) {
    $Result->show("danger", _("You do not have permissions to access this module"), false);
}
else {
    # fetch all nats
    $all_nats = $Tools->fetch_all_objects("nat", "name");

	# get custom device fields
	$custom_fields = (array) $Tools->fetch_custom_fields('nat');

	# set hidden fields
	$hidden_fields = db_json_decode($User->settings->hiddenCustomFields, true);
	$hidden_fields = is_array(@$hidden_fields['nat']) ? $hidden_fields['nat'] : array();

	# size of custom fields
	$csize = sizeof($custom_fields) - sizeof($hidden_fields);

    // check if we have any policy nat !
    $policy_nat_found = false;
    if($all_nats !== false) {
    	foreach ($all_nats as $n) {
            if ($n->policy=="Yes") {
                $policy_nat_found = true;
                break;
            }
        }
    }

    // table
    print "<table class='table sorted table-striped table-top table-td-top' data-cookie-id-table='nat_table'>";
    // headers
    print "<thead>";
    print "<tr>";
    print " <th>"._('Name')."</th>";
    print " <th>"._('Type')."</th>";
    print " <th>"._('Translation')."</th>";
    print " <th></th>";
    print " <th></th>";
    if($policy_nat_found)
    print " <th>"._('Policy SRC/DST IP')."</th>";
    print " <th>"._('Device')."</th>";
    print " <th>"._('Src Port')."</th>";
    print " <th>"._('Dst Port')."</th>";
    print " <th>"._('Description')."</th>";

	if(sizeof(@$custom_fields) > 0) {
		foreach($custom_fields as $field) {
			if(!in_array($field['name'], $hidden_fields)) {
				print "	<th class='hidden-xs hidden-sm hidden-md'>".$Tools->print_custom_field_name ($field['name'])."</th>";
			}
		}
	}

    if($User->get_module_permissions ("nat")>=User::ACCESS_RW)
    print " <th style='width:80px'></th>";
    print "</tr>";
    print "</thead>";

    print "<tbody>";

    // init array
    $nats_reordered = array("source"=>array(), "static"=>array(), "destination"=>array());

    # rearrange based on type
    if($all_nats !== false) {
        foreach ($all_nats as $n) {
            # policy
            if($n->policy=="Yes") { $n->type = $n->type . " policy"; }
            # save
            $nats_reordered[$n->type][] = $n;
        }
    }

    # reorder
    ksort($nats_reordered);

    # loop
    foreach ($nats_reordered as $k=>$nats) {
        # header
        $colspan = $policy_nat_found ? 11 :10;
        $colspan += $csize;
        print "<tr>";
        print " <td colspan='$colspan' class='th'><i class='fa fa-exchange'></i> "._(ucwords($k)." NAT")."</td>";
        print "</tr>";

        # if none than print
        if(sizeof($nats)==0) {
            print "<tr>";
            print " <td colspan='$colspan'>".$Result->show("info",_("No")." $k "._("NAT configured"), false, false, true)."</td>";
            print "</tr>";
        }
        else {
            foreach ($nats as $n) {
                // translate json to array, links etc
                $sources      = $Tools->translate_nat_objects_for_display ($n->src);
                $destinations = $Tools->translate_nat_objects_for_display ($n->dst);

                // no src/dst
                if ($sources===false)
                    $sources = array("<span class='badge badge1 badge5 alert-danger'>"._("None")."</span>");
                if ($destinations===false)
                    $destinations = array("<span class='badge badge1 badge5 alert-danger'>"._("None")."</span>");

                // device
                if (strlen($n->device)) {
                    if($n->device !== 0) {
                        $device = $Tools->fetch_object ("devices", "id", $n->device);
                        $n->device = $device===false ? "/" : "<a href='".create_link("tools", "devices", $device->id)."'>$device->hostname</a>";
                    }
                }
                else {
                    $n->device = "/";
                }

                // icon
                $icon =  $n->type=="static" ? "fa-arrows-h" : "fa-long-arrow-right";

                // append policy
                $policy_dst = $n->policy=="Yes" ? $n->policy_dst : "/";

                // description
                $n->description = is_null($n->description) ? "" : str_replace("\n", "<br>", $n->description);

                // port
                if(is_blank($n->src_port)) $n->src_port = "/";
                if(is_blank($n->dst_port)) $n->dst_port = "/";

                // print
                print "<tr>";
                print " <td><strong><a href='".create_link($GET->page, "nat", $n->id)."'>$n->name</a></strong></td>";
                print " <td><span class='badge badge1 badge5'>".ucwords($n->type)."</span></td>";
                print " <td>".implode("<br>", $sources)."</td>";
                print " <td style='width:10px;'><i class='fa $icon'></i></td>";
                print " <td>".implode("<br>", $destinations)."</td>";
                if($policy_nat_found)
                print " <td>$policy_dst</td>";
                print " <td>$n->device</td>";
                print " <td>$n->src_port</td>";
                print " <td>$n->dst_port</td>";
                print " <td><span class='text-muted'>$n->description</span></td>";
                //custom fields
                if(sizeof(@$custom_fields) > 0) {
                	foreach($custom_fields as $field) {
                		# hidden
                		if(!in_array($field['name'], $hidden_fields)) {
                			print "<td class='hidden-xs hidden-sm hidden-md'>";
                			$Tools->print_custom_field ($field['type'], $n->{$field['name']});
                			print "</td>";
                		}
                	}
                }
                // actions
                if($User->get_module_permissions ("nat")>=User::ACCESS_RW) {
        		print "	<td class='actions'>";
                $links = [];
                $links[] = ["type"=>"header", "text"=>_("Manage NAT")];
                $links[] = ["type"=>"link", "text"=>_("Edit NAT"), "href"=>"", "class"=>"open_popup", "dataparams"=>" data-script='app/admin/nat/edit.php' data-class='700' data-action='edit' data-id='$n->id'", "icon"=>"pencil"];
                $links[] = ["type"=>"link", "text"=>_("Delete NAT"), "href"=>"", "class"=>"open_popup", "dataparams"=>" data-script='app/admin/nat/edit.php' data-class='700' data-action='delete' data-id='$n->id'", "icon"=>"times"];
                // print links
                print $User->print_actions($User->user->compress_actions, $links);
        		print " </td>";
        		}

                print "</tr>";
            }
        }
    }
    print "</tbody>";
    print "</table>";
}
