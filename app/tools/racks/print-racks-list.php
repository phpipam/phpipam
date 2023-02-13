<?php

/**
 * Script to print racks
 ***************************/

# verify that user is logged in
$User->check_user_session();
# verify module permissions
$User->check_module_permissions ("racks", User::ACCESS_R, true);
?>

<?php

# check that rack support isenabled
if ($User->settings->enableRACK!="1") {
    $Result->show("danger", _("RACK management disabled."), false);
}
else {
    # init racks object
    $Racks = new phpipam_rack ($Database);

    // fetch racks
    $User->settings->enableLocations=="1" ? $Racks->fetch_all_racks(true) : $Racks->fetch_all_racks(false);

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
    if($User->settings->enableCustomers=="1") {
    print ' <th data-field="customer" data-sortable="true">'._('Customer').'</th>' . "\n";
    $colspan++;
    }
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
    # none
    if ($Racks->all_racks === false) {
        print "<tr>";
        print " <td colspan='5'>".$Result->show("info", _("No racks available"), false, false, true)."</td>";
        print "</tr>";
    }
    # print
    else {
        // set printed locations array
        $printed_locations = array ();

        // loop
        foreach ($Racks->all_racks as $r) {
            // back
            $r->back = $r->hasBack!="0" ? "Yes" : "No";
            // cht devices
            $cnt = $Tools->count_database_objects ("devices", "rack", $r->id) + $Tools->count_database_objects ("rackContents", "rack", $r->id);

            // fix possible null
            if(is_blank($r->location)) $r->location = 0;

            // print location ?
            if($User->settings->enableLocations=="1") {
                // if not printed print it
                if(!in_array($r->location, $printed_locations)) {
                    // no location
                    if($r->location==0) {
                        print "<tr><td colspan='$colspan' class='th'>"._("No location")."</td></tr>";
                    }
                    else {
                        $location = $Tools->fetch_object("locations", "id", $r->location);

                        if($location!==false) {
                            print "<tr><td colspan='$colspan' class='th'><a href='".create_link($_GET['page'], "locations", $location->id)."'> $location->name</a></td></tr>";
                        }
                        else {
                            print "<tr><td colspan='$colspan' class='th'>"._("Invalid location")."</td></tr>";
                        }
                    }
                    $printed_locations[] = $r->location;
                }
            }

            // print
            print "<tr>";

            print " <td><a class='btn btn-xs btn-default' href='".create_link($_GET['page'], "racks", $r->id)."'><i class='fa fa-bars prefix'></i> $r->name</a></td>";
            print " <td>$r->size U</td>";
            print " <td>"._($r->back)."</td>";
            print " <td>$cnt "._("devices")."</td>";
            print " <td>$r->description</td>";
            if($User->settings->enableCustomers=="1") {
                 $customer = $Tools->fetch_object ("customers", "id", $r->customer_id);
                 print $customer===false ? "<td></td>" : "<td>{$customer->title} <a target='_blank' href='".create_link("tools","customers",$customer->title)."'><i class='fa fa-external-link'></i></a></td>";
            }
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
            // print links
            print $User->print_actions($User->user->compress_actions, $links);
            print "</td>";

            print "</tr>";
        }
    }
    print "</tbody>";
    print "</table>";
}