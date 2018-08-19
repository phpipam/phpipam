<h4><?php print _("VRF"); ?></h4>
<hr>
<span class="text-muted"><?php print _("All VRF belonging to customer"); ?>.</span>

<script type="text/javascript">
/* fix for ajax-loading tooltips */
$('body').tooltip({ selector: '[rel=tooltip]' });
</script>


<?php

# only if set
if (isset($objects["vrf"])) {

	# get all VLANs and subnet descriptions
	$vrfs = $objects['vrf'];

	# get custom VLAN fields
	$custom_fields = (array) $Tools->fetch_custom_fields('vrf');

	# set hidden fields
	$hidden_fields = json_decode($User->settings->hiddenCustomFields, true);
	$hidden_fields = is_array(@$hidden_fields['vrf']) ? $hidden_fields['vrf'] : array();

	# size of custom fields
	$csize = sizeof($custom_fields) - sizeof($hidden_fields);

	# set disabled for non-admins
	$disabled = $User->is_admin(false)==true ? "" : "hidden";


	# table
	print "<table class='table sorted vrf table-condensed table-top' data-cookie-id-table='customer_vrfs'>";

	# headers
	print "<thead>";
	print '<tr">' . "\n";
	print ' <th data-field="vlname" data-sortable="true">'._('Name').'</th>' . "\n";
	print ' <th data-field="name" data-sortable="true">'._('Description').'</th>' . "\n";
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
	foreach ($vrfs as $vrf) {

		// start - VLAN details
		print "<tr class='$class change'>";
		print "	<td><a class='btn btn-xs btn-default' href='".create_link($_GET['page'], "vrf", $vrf->vrfId)."'><i class='fa fa-cloud prefix'></i> ".$vrf->name."</a></td>";
		print "	<td>".$vrf->description."</td>";
        // custom fields - no subnets
        if(sizeof(@$custom_fields) > 0) {
	   		foreach($custom_fields as $field) {
		   		# hidden
		   		if(!in_array($field['name'], $hidden_fields)) {
					print "<td class='hidden-xs hidden-sm hidden-md'>";
					$Tools->print_custom_field ($field['type'], $vrf->{$field['name']});
					print "</td>";
				}
	    	}
	    }

        // actions
		print "	<td class='actions'>";
		print "	<div class='btn-group'>";
		if($User->user->edit_vlan=="Yes"||$User->is_admin(false)) {
		print "		<button class='btn btn-xs btn-default open_popup' rel='tooltip' title='Edit' data-script='app/admin/vrfs/edit.php' data-class='700' data-action='edit' data-vrfid='$vrf->vrfId'><i class='fa fa-pencil'></i></button>";
		print "		<button class='btn btn-xs btn-default open_popup' rel='tooltip' title='Delete' data-script='app/admin/vrfs/edit.php' data-class='700' data-action='delete' data-vrfid='$vrf->vrfId'><i class='fa fa-times'></i></button>";
		}
		if($User->get_module_permissions ("customers")>1)
		print "		<button class='btn btn-xs btn-default open_popup' rel='tooltip' title='Unlink object' data-script='app/admin/customers/unlink.php' data-class='700' data-object='vrf' data-id='$vrf->vrfId'><i class='fa fa-unlink'></i></button>";
		print "	</div>";
		print "	</td>";

        print "</tr>";

		# next index
		$m++;
	}
	print "</tbody>";

	print '</table>';
}
else {
	$Result->show("info", _("No objects"));
}