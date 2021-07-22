<?php

# Check we have been included and not called directly
require( dirname(__FILE__) . '/../../../../functions/include-only.php' );
?>
<script>
/* fix for ajax-loading tooltips */
$('body').tooltip({ selector: '[rel=tooltip]' });
</script>

<?php

/**
 * Script to display circuits
 *
 */

# verify that user is logged in
$User->check_user_session();
# perm check
$User->check_module_permissions ("circuits", User::ACCESS_R, true, false);

# filter circuits or fetch print all?
$circuit_providers = $Tools->fetch_all_objects("circuitProviders", "name");

# strip tags - XSS
$_GET = $User->strip_input_tags ($_GET);

# get custom fields
$custom_fields = $Tools->fetch_custom_fields('circuitProviders');
# get hidden fields */
$hidden_fields = json_decode($User->settings->hiddenCustomFields, true);
$hidden_fields = is_array(@$hidden_fields['circuitProviders']) ? $hidden_fields['circuitProviders'] : array();

# title
print "<h4>"._('List of Circuit providers')."</h4>";
print "<hr>";

# print link to manage
print "<div class='btn-group'>";
	// add
	if($User->get_module_permissions ("circuits")>=User::ACCESS_RWA) {
    print "<a href='' class='btn btn-sm btn-default open_popup' data-script='app/admin/circuits/edit-provider.php' data-class='700' data-action='add' data-providerid='' style='margin-bottom:10px;'><i class='fa fa-plus'></i> "._('Add provider')."</a>";
	}
print "</div>";

# table
print '<table id="circuitManagement" class="table sorted table-striped table-top" data-cookie-id-table="circuit_providers">';

#headers
print "<thead>";
print '<tr>';
print "	<th><span rel='tooltip' data-container='body' title='"._('Sort by Name')."'>"._('Name')."</span></th>";
print "	<th><span rel='tooltip' data-container='body' title='"._('Sort by Description')."'>"._('Description').'</span></th>';
print "	<th><span rel='tooltip' data-container='body' title='"._('Sort by Circuits')."'>"._('Circuits').'</span></th>';
print "	<th><span rel='tooltip' data-container='body' title='"._('Sort by Contact')."'>"._('Contact').'</span></th>';
$colspanCustom = 0;
if(sizeof(@$custom_fields) > 0) {
	foreach($custom_fields as $field) {
		if(!in_array($field['name'], $hidden_fields)) {
			print "<th class='hidden-sm hidden-xs hidden-md'><span rel='tooltip' data-container='body' title='"._('Sort by')." ".$Tools->print_custom_field_name ($field['name'])."'>".$Tools->print_custom_field_name ($field['name'])."</th>";
			$colspanCustom++;
		}
	}
}
print '	<th class="actions"></th>';
print '</tr>';
print "</thead>";

// no circuits
if($circuit_providers===false) {
	$colspan = 3 + $colspanCustom;
	print "<tr>";
	print "	<td colspan='$colspan'>".$Result->show('info', _('No results')."!", false, false, true)."</td>";
	print "</tr>";
}
// result
else {
	foreach ($circuit_providers as $provider) {
		// count items belonging to provider
		$cnt = $Database->numObjectsFilter("circuits", "provider", $provider->id);
		//print details
		print '<tr>'. "\n";
		print "	<td><strong><a class='btn btn-xs btn-default' href='".create_link($_GET['page'],"circuits","providers",$provider->id)."'>$provider->name</a></strong></td>";
		print "	<td>$provider->description</td>";
		print "	<td>$cnt "._("Circuits")."</td>";
		print " <td>$provider->contact</td>";
		//custom
		if(sizeof(@$custom_fields) > 0) {
			foreach($custom_fields as $field) {
				if(!in_array($field['name'], $hidden_fields)) {
					// create html links
					$provider->{$field['name']} = $User->create_links($provider->{$field['name']}, $field['type']);

					print "<td class='hidden-sm hidden-xs hidden-md'>".$provider->{$field['name']}."</td>";
				}
			}
		}

		// actions
        print "<td class='actions'>";
        $links = [];
        $links[] = ["type"=>"header", "text"=>_("View")];
        $links[] = ["type"=>"link", "text"=>_("Show provider"), "href"=>create_link($_GET['page'], "circuits","providers",$provider->id), "icon"=>"eye", "visible"=>"dropdown"];
        $links[] = ["type"=>"divider"];
        if($User->get_module_permissions ("circuits")>=User::ACCESS_RW) {
            $links[] = ["type"=>"header", "text"=>_("Manage provider")];
            $links[] = ["type"=>"link", "text"=>_("Edit provider"), "href"=>"", "class"=>"open_popup", "dataparams"=>"  data-script='app/admin/circuits/edit-provider.php' data-class='700' data-action='edit' data-providerid='$provider->id'", "icon"=>"pencil"];
        }
        if($User->get_module_permissions ("circuits")>=User::ACCESS_RWA) {
            $links[] = ["type"=>"link", "text"=>_("Delete provider"), "href"=>"", "class"=>"open_popup", "dataparams"=>"  data-script='app/admin/circuits/edit-provider.php' data-class='700' data-action='delete' data-providerid='$provider->id'", "icon"=>"times"];
            $links[] = ["type"=>"divider"];
        }
        // print links
        print $User->print_actions($User->user->compress_actions, $links);
        print "</td>";

		print '</tr>';
	}
}

print '</table>';
