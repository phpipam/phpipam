<?php

/*
 * Script to display search results
 **********************************/

# verify that user is logged in
$User->check_user_session();

# get all custom fields
$custom_vrf_fields = $GET->vrf=="on"       ? $Tools->fetch_custom_fields ("vrf") : array();
$hidden_vrf_fields = is_array(@$hidden_fields['vrf']) ? $hidden_fields['vrf'] : array();

# search vrfs
$result_vrf = $Tools->search_vrfs($searchTerm, $custom_vrf_fields);
?>

<!-- !vrf -->
<br>
<h4><?php print _('Search results (VRFs)');?>:</h4>
<hr>

<table class="searchTable sorted table table-striped table-condensed table-top" data-cookie-id-table="search_vrf">

<!-- headers -->
<thead>
<tr id="searchHeader">
	<th><?php print _('Name');?></th>
	<th><?php print _('RD');?></th>
	<th><?php print _('Description');?></th>
	<?php
	if(sizeof($custom_vrf_fields) > 0) {
		foreach($custom_vrf_fields as $field) {
			if(!in_array($field['name'], $hidden_vrf_fields)) {
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
if(sizeof($result_vrf) > 0) {
	# print vlans
	foreach($result_vrf as $vrf) {
		# cast
		$vrf = (array) $vrf;

		print '<tr class="nolink">' . "\n";
		print ' <td><dd><a class="btn btn-xs btn-default" href="'.create_link("tools","vrf",$vrf['vrfId']).'">'. $vrf['name']      .'</a></dd></td>' . "\n";
		print ' <td><dd>'. $vrf['rd']     .'</dd></td>' . "\n";
		print ' <td><dd>'. $vrf['description'] .'</dd></td>' . "\n";
		# custom fields
		if(sizeof($custom_vrf_fields) > 0) {
			foreach($custom_vrf_fields as $field) {
				if(!in_array($field['name'], $hidden_vrf_fields)) {
					$vrf[$field['name']] = $Tools->create_links ($vrf[$field['name']], $field['type']);
					print "	<td class='hidden-xs hidden-sm'>";
					$Tools->print_custom_field ($field['type'], $vrf[$field['name']]);
					print "	</td>";
				}
			}
		}
		# for admins print link
		print " <td class='actions'>";
		if($User->is_admin(false)) {
		print '<div class="btn-group">';
		print "		<button class='btn btn-xs btn-default open_popup' data-script='app/admin/vrf/edit.php' data-class='700' data-action='edit' data-vrfid='$vrf[vrfId]'><i class='fa fa-pencil'></i></button>";
		print "		<button class='btn btn-xs btn-default open_popup' data-script='app/admin/vrf/edit.php' data-class='700' data-action='delete' data-vrfid='$vrf[vrfId]'><i class='fa fa-times'></i></button>";
		print '</div>';
		}
		print "</td>";
		print '</tr>'. "\n";
    }
}
?>
</tbody>
</table>
<?php
if(sizeof($result_vrf) == 0) {
	$Result->show("info", _("No results"), false);
}