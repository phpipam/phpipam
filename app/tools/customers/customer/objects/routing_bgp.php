<h4><?php print _("BGP routing"); ?></h4>
<hr>
<span class="text-muted"><?php print _("All BGPs belonging to customer"); ?>.</span>

<script>
/* fix for ajax-loading tooltips */
$('body').tooltip({ selector: '[rel=tooltip]' });
</script>


<?php

# only if set
if (isset($objects["routing_bgp"])) {
    // fetch all BGP entries
    $all_bgp_entries = $objects["routing_bgp"];

	# fetch custom fields
	$custom_bgp = $Tools->fetch_custom_fields('routing_bgp');
	$hidden_custom_fields_bgp = pf_json_decode($User->settings->hiddenCustomFields, true);
	$hidden_custom_fields_bgp = is_array(@$hidden_custom_fields['routing_bgp']) ? $hidden_custom_fields['routing_bgp'] : array();

    // colspan
    $colspan = 7;
    // table
    print "<table class='table sorted table-striped table-top table-td-top' data-cookie-id-table='rack_list'>";
    // headers
    print "<thead>";
    print "<tr>";
    print " <th>"._('Peer name')."</th>";
    print " <th>"._('Peer AS')."</th>";
    print " <th>"._('Local AS')."</th>";
    print " <th>"._('Peer address')."</th>";
    print " <th>"._('Local address')."</th>";
    print " <th>"._('BGP type')."</th>";
    print " <th>"._('Subnets')."</th>";
	if(sizeof($custom_bgp) > 0) {
		foreach($custom_bgp as $field) {
			if(!in_array($field['name'], $hidden_custom_fields_bgp)) {
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
    if ($all_bgp_entries === false) {
        print "<tr>";
        print " <td colspan='$colspan'>".$Result->show("info", _("No BGP information available"), false, false, true)."</td>";
        print "</tr>";
    }
    # print
    else {
        // set printed locations array
        $printed_locations = array ();

        // loop
        foreach ($all_bgp_entries as $bgp) {

            // print
            print "<tr>";

            print " <td><a class='btn btn-xs btn-default' href='".create_link($_GET['page'], "routing", "bgp", $bgp->id)."'><i class='fa fa-exchange prefix'></i> $bgp->peer_name</a></td>";
            print " <td>$bgp->peer_as</td>";
            print " <td>$bgp->local_as</td>";
            print " <td>$bgp->peer_address</td>";
            print " <td>$bgp->local_address</td>";
            print " <td>$bgp->bgp_type</td>";
            // subnets
            print " <td>".$Tools->fetch_routing_subnets ("bgp", $bgp->id, true)[0]->cnt."</td>";

            //custom
            if(sizeof($custom_bgp) > 0) {
                foreach($custom_bgp as $field) {
                    if(!in_array($field['name'], $hidden_custom_fields_bgp)) {
                        print "<td class='hidden-xs hidden-sm hidden-md'>";
                        $Tools->print_custom_field ($field['type'], $bgp->{$field['name']});
                        print "</td>";
                    }
                }
            }

            // links
            print "<td class='actions'>";
            $links = [];
            if($User->get_module_permissions ("routing")>=User::ACCESS_R) {
                $links[] = ["type"=>"header", "text"=>_("Show BGP")];
                $links[] = ["type"=>"link", "text"=>_("Show BGP"), "href"=>create_link($_GET['page'], "routing", "bgp", $bgp->id), "icon"=>"eye", "visible"=>"dropdown"];
                $links[] = ["type"=>"divider"];
            }
            if($User->get_module_permissions ("routing")>=User::ACCESS_RW) {
                $links[] = ["type"=>"header", "text"=>_("Manage BGP")];
                $links[] = ["type"=>"link", "text"=>_("Edit BGP"), "href"=>"", "class"=>"open_popup", "dataparams"=>"data-script='app/admin/routing/edit-bgp.php' data-action='edit' data-class='700' data-bgpid='$bgp->id'", "icon"=>"pencil"];
            }
            if($User->get_module_permissions ("routing")>=User::ACCESS_RWA) {
                $links[] = ["type"=>"link", "text"=>_("Delete BGP"), "href"=>"", "class"=>"open_popup", "dataparams"=>"data-script='app/admin/routing/edit-bgp.php' data-action='delete' data-class='700' data-bgpid='$bgp->id'", "icon"=>"times"];
                $links[] = ["type"=>"link", "text"=>_("Subnet mapping"), "href"=>"", "class"=>"open_popup", "dataparams"=>"data-script='app/admin/routing/edit-bgp-mapping.php' data-class='700' data-bgpid='$bgp->id'", "icon"=>"plus"];
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
else {
	$Result->show("info", _("No objects"));
}