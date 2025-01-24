<h4><?php print _("VLANs"); ?></h4>
<hr>
<span class="text-muted"><?php print _("All VLANs belonging to customer"); ?>.</span>

<script>
/* fix for ajax-loading tooltips */
$('body').tooltip({ selector: '[rel=tooltip]' });
</script>


<?php

# only if set
if (isset($objects["vlans"])) {

	# get all VLANs and subnet descriptions
	$vlans = $objects['vlans'];

	# get custom VLAN fields
	$custom_fields = (array) $Tools->fetch_custom_fields('vlans');

	# set hidden fields
	$hidden_fields = db_json_decode($User->settings->hiddenCustomFields, true);
	$hidden_fields = is_array(@$hidden_fields['vlans']) ? $hidden_fields['vlans'] : array();

	# size of custom fields
	$csize = sizeof($custom_fields) - sizeof($hidden_fields);

	# set disabled for non-admins
	$disabled = $User->is_admin(false)==true ? "" : "hidden";


	# table
	print "<table class='table sorted vlans table-condensed table-top' data-cookie-id-table='customer_vlans'>";

	# headers
	print "<thead>";
	print '<tr">' . "\n";
	print ' <th data-field="number" data-sortable="true">'._('Number').'</th>' . "\n";
	print ' <th data-field="vlname" data-sortable="true">'._('Name').'</th>' . "\n";
	print ' <th data-field="name" data-sortable="true">'._('L2domain').'</th>' . "\n";
	if(sizeof(@$custom_fields) > 0) {
		foreach($custom_fields as $field) {
			if(!in_array($field['name'], $hidden_fields)) {
				print "	<th class='hidden-xs hidden-sm hidden-md'>".$Tools->print_custom_field_name ($field['name'])."</th>";
			}
		}
	}
    print "<th></th>";
	print "</tr>";
	print "</thead>";

	// body
	print "<tbody>";
	$m = 0;
	foreach ($vlans as $vlan) {

		// fixes
		$vlan->description = !is_blank($vlan->description) ? " <span class='text-muted'>( ".$vlan->description." )</span>" : "";
		$vlan->domainDescription = !is_blank($vlan->domainDescription) ? " <span class='text-muted'>( ".$vlan->domainDescription." )</span>" : "";

		// l2 domain
		$domain = $Tools->fetch_object ("vlanDomains", "id", $vlan->domainId);
		$domain_text = $domain===false ? "" : $domain->name." (".$domain->description.")";

		// start - VLAN details
		print "<tr class='$class change'>";
		print "	<td><a class='btn btn-xs btn-default' href='".create_link($GET->page, "vlan", $vlan->domainId, $vlan->vlanId)."'><i class='fa fa-cloud prefix'></i> ".$vlan->number."</a></td>";
		print "	<td><a href='".create_link($GET->page, "vlan", $vlan->domainId, $vlan->vlanId)."'>".$vlan->name."</a>".$vlan->description."</td>";
		print "	<td>".$domain_text."</td>";
        // custom fields - no subnets
        if(sizeof(@$custom_fields) > 0) {
	   		foreach($custom_fields as $field) {
		   		# hidden
		   		if(!in_array($field['name'], $hidden_fields)) {
					print "<td class='hidden-xs hidden-sm hidden-md'>";
					$Tools->print_custom_field ($field['type'], $vlan->{$field['name']});
					print "</td>";
				}
	    	}
	    }

        // actions
        print "<td class='actions'>";
        $links = [];
        if($User->get_module_permissions ("vlan")>=User::ACCESS_R) {
            $links[] = ["type"=>"header", "text"=>_("View")];
            $links[] = ["type"=>"link", "text"=>_("Show VLAN"), "href"=>create_link("tools", "vlan", $vlan->domainId, $vlan->vlanId), "icon"=>"eye", "visible"=>"dropdown"];
            $links[] = ["type"=>"divider"];
        }
        if($User->get_module_permissions ("vlan")>=User::ACCESS_RW) {
            $links[] = ["type"=>"header", "text"=>_("Manage")];
            $links[] = ["type"=>"link", "text"=>_("Edit VLAN"), "href"=>"", "class"=>"open_popup", "dataparams"=>" data-script='app/admin/vlans/edit.php' data-action='edit' data-vlanid='$vlan->vlanId'", "icon"=>"pencil"];
        }
        if($User->get_module_permissions ("vlan")>=User::ACCESS_RWA) {
            $links[] = ["type"=>"link", "text"=>_("Delete VLAN"), "href"=>"", "class"=>"open_popup", "dataparams"=>" data-script='app/admin/vlans/edit.php' data-action='delete' data-vlanid='$vlan->vlanId'", "icon"=>"times"];
        }
		if($User->get_module_permissions ("customers")>=User::ACCESS_RW) {
            $links[] = ["type"=>"divider"];
	        $links[] = ["type"=>"header", "text"=>_("Unlink")];
            $links[] = ["type"=>"link", "text"=>_("Unlink object"), "href"=>"", "class"=>"open_popup", "dataparams"=>" data-script='app/admin/customers/unlink.php' data-class='700' data-object='vlans' data-id='$vlan->vlanId'", "icon"=>"unlink"];
		}
        // print links
        print $User->print_actions($User->user->compress_actions, $links);
        print "</td>";
		print '</tr>'. "\n";

		# next index
		$m++;
	}
	print "</tbody>";

	print '</table>';

}
else {
	$Result->show("info", _("No objects"));
}