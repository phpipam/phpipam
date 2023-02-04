<h4><?php print _("Racks"); ?></h4>
<hr>
<span class="text-muted"><?php print _("All Racks belonging to customer"); ?>.</span>

<script>
/* fix for ajax-loading tooltips */
$('body').tooltip({ selector: '[rel=tooltip]' });
</script>


<?php

# only if set
if (isset($objects["racks"])) {

	# assign racks
	$racks = $objects['racks'];

	# fetch custom fields
	$custom = $Tools->fetch_custom_fields('racks');

	# get hidden fields
	$hidden_custom_fields = pf_json_decode($User->settings->hiddenCustomFields, true);
	$hidden_custom_fields = is_array(@$hidden_custom_fields['racks']) ? $hidden_custom_fields['racks'] : array();

    // table
    print "<table class='table sorted table-striped table-top table-td-top' data-cookie-id-table='rack_list'>";
    // headers
    print "<thead>";
    print "<tr>";
    print " <th>"._('Name')."</th>";
    print " <th>"._('Size')."</th>";
    print " <th>"._('Back side')."</th>";
    print " <th>"._('Devices')."</th>";
    print " <th>"._('Description')."</th>";
    $colspan = 6;
	if(sizeof($custom) > 0) {
		foreach($custom as $field) {
			if(!in_array($field['name'], $hidden_custom_fields)) {
				print "<th class='hidden-xs hidden-sm hidden-md'>".$Tools->print_custom_field_name ($field['name'])."</th>";
                $colspan++;
			}
		}
	}
    print " <th style='width:80px'></th>";
    print "</tr>";
    print "</thead>";

    print "<tbody>";

    // loop
    foreach ($racks	 as $r) {
        // back
        $r->back = $r->hasBack!="0" ? "Yes" : "No";
        // cht devices
        $cnt = $Tools->count_database_objects ("devices", "rack", $r->id);

        // fix possible null
        if(is_blank($r->location)) $r->location = 0;

        // print
        print "<tr>";

        print " <td><a class='btn btn-xs btn-default' href='".create_link($_GET['page'], "racks", $r->id)."'><i class='fa fa-bars prefix'></i> $r->name</a></td>";
        print " <td>$r->size U</td>";
        print " <td>"._($r->back)."</td>";
        print " <td>$cnt "._("devices")."</td>";
        print " <td>$r->description</td>";

		//custom
		if(sizeof($custom) > 0) {
			foreach($custom as $field) {
				if(!in_array($field['name'], $hidden_custom_fields)) {
					print "<td class='hidden-xs hidden-sm hidden-md'>";
                    $Tools->print_custom_field ($field['type'], $r->{$field['name']});
					print "</td>";
				}
			}
		}

        // links
        print "<td class='actions'>";
        $links = [];
        if($User->get_module_permissions ("racks")>=User::ACCESS_R) {
            $links[] = ["type"=>"header", "text"=>_("Show rack")];
            $links[] = ["type"=>"link", "text"=>_("Show rack"), "href"=>create_link($_GET['page'], "racks", $r->id), "icon"=>"eye", "visible"=>"dropdown"];
            $links[] = ["type"=>"link", "text"=>_("Show popup"), "href"=>"", "class"=>"showRackPopup", "dataparams"=>"data-rackId='$r->id' data-deviceId='0'", "icon"=>"server"];
            $links[] = ["type"=>"divider"];
        }
        if($User->get_module_permissions ("racks")>=User::ACCESS_RW) {
            $links[] = ["type"=>"header", "text"=>_("Manage rack")];
            $links[] = ["type"=>"link", "text"=>_("Edit rack"), "href"=>"", "class"=>"editRack", "dataparams"=>" data-action='edit' data-rackid='$r->id'", "icon"=>"pencil"];
        }
        if($User->get_module_permissions ("racks")>=User::ACCESS_RWA) {
            $links[] = ["type"=>"link", "text"=>_("Delete rack"), "href"=>"", "class"=>"editRack", "dataparams"=>" data-action='delete' data-rackid='$r->id'", "icon"=>"times"];
            $links[] = ["type"=>"divider"];
        }
        if($User->get_module_permissions ("customers")>=User::ACCESS_RW) {
            $links[] = ["type"=>"divider"];
            $links[] = ["type"=>"header", "text"=>_("Unlink")];
            $links[] = ["type"=>"link", "text"=>_("Unlink object"), "href"=>"", "class"=>"open_popup", "dataparams"=>" data-script='app/admin/customers/unlink.php' data-class='700' data-object='racks' data-id='$r->id'", "icon"=>"unlink"];
        }
        // print links
        print $User->print_actions($User->user->compress_actions, $links);
        print "</td>";

        print "</tr>";
    }
	print "</tbody>";
	print "</table>";
}

else {
	$Result->show("info", _("No objects"));
}