<?php

/*
 * Script to display search results
 **********************************/

# verify that user is logged in
$User->check_user_session();

# get all custom fields
$custom_location_fields = $GET->locations=="on"     ? $Tools->fetch_custom_fields ("locations") : array();
$hidden_location_fields = is_array(@$hidden_fields['locations']) ? $hidden_fields['locations'] : array();

# search locations
$result_locations = $Tools->search_locations($searchTerm, $custom_location_fields);
?>

<br>
<h4><?php print _('Search results (Locations)');?>:</h4>
<hr>

<table class="searchTable table sorted table-striped table-condensed table-top" data-cookie-id-table="search_location">

<!-- headers -->
<thead>
<tr id="searchHeader">
	<th><?php print _('Name');?></th>
	<th><?php print _('Description');?></th>
	<?php
	if(sizeof($custom_location_fields) > 0) {
		foreach($custom_location_fields as $field) {
			if(!in_array($field['name'], $hidden_location_fields)) {
				print "	<th class='hidden-xs hidden-sm'>".$Tools->print_custom_field_name ($field['name'])."</th>";
			}
		}
	}
	?>
	<th></th>
</tr>
</thead>

<tbody>
<?php
if(sizeof($result_locations) > 0) {
	# print locations
	foreach($result_locations as $location) {
		# cast
		$location = (array) $location;

		print '<tr class="nolink">' . "\n";
		print ' <td><dd><a class="btn btn-xs btn-default" href="'.create_link("tools","locations",$location['id']).'">'. $location['name']     .'</a></dd></td>' . "\n";
		print ' <td><dd>'. $location['description'] .'</dd></td>' . "\n";
		# custom fields
		if(sizeof($custom_location_fields) > 0) {
			foreach($custom_location_fields as $field) {
				if(!in_array($field['name'], $hidden_location_fields)) {
					$location[$field['name']] = $Tools->create_links ($location[$field['name']], $field['type']);
					print "	<td class='hidden-xs hidden-sm'>";
					$Tools->print_custom_field ($field['type'], $location[$field['name']]);
					print "</td>";
				}
			}
		}
        // actions
        print "<td class='actions'>";
        $links = [];
        if($User->get_module_permissions ("locations")>=User::ACCESS_R) {
            $links[] = ["type"=>"header", "text"=>_("View")];
            $links[] = ["type"=>"link", "text"=>_("Show Location"), "href"=>create_link("tools", "locations", $location['id']), "icon"=>"eye", "visible"=>"dropdown"];
            $links[] = ["type"=>"divider"];
        }
        if($User->get_module_permissions ("locations")>=User::ACCESS_RW) {
            $links[] = ["type"=>"header", "text"=>_("Manage")];
            $links[] = ["type"=>"link", "text"=>_("Edit Location"), "href"=>"", "class"=>"open_popup", "dataparams"=>" data-script='app/admin/locations/edit.php' data-action='edit' data-id='$location[id]'", "icon"=>"pencil"];
        }
        if($User->get_module_permissions ("locations")>=User::ACCESS_RWA) {
            $links[] = ["type"=>"link", "text"=>_("Delete Location"), "href"=>"", "class"=>"open_popup", "dataparams"=>" data-script='app/admin/locations/edit.php' data-action='delete' data-id='$location[id]'", "icon"=>"times"];
        }
        // print links
        print $User->print_actions($User->user->compress_actions, $links);
        print "</td>";
		print '</tr>'. "\n";
    }
}
?>
</tbody>
</table>
<?php
if(sizeof($result_locations) == 0) {
	$Result->show("info", _("No results"), false);
}