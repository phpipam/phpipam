<?php

/*
 * Script to display search results
 **********************************/

# verify that user is logged in
$User->check_user_session();

# get all custom fields
$custom_vrf_fields = $_REQUEST['vrf']=="on"       ? $Tools->fetch_custom_fields ("vrf") : array();
$hidden_vrf_fields = is_array(@$hidden_fields['vrf']) ? $hidden_fields['vrf'] : array();

# search vrfs
$result_vrf = $Tools->search_vrfs($searchTerm, $custom_vrf_fields);
?>

<!-- !vrf -->
<br>
<h4><?php print _('Search results (VRFs)');?>:</h4>
<hr>

<table class="searchTable table table-striped table-condensed table-top">

<!-- headers -->
<tr id="searchHeader">
	<th><?php print _('Name');?></th>
	<th><?php print _('RD');?></th>
	<th><?php print _('Description');?></th>
	<?php
	if(sizeof($custom_vrf_fields) > 0) {
		foreach($custom_vrf_fields as $field) {
			if(!in_array($field['name'], $hidden_vrf_fields)) {
				print "	<th class='hidden-xs hidden-sm'>$field[name]</th>";
			}
		}
	}
	?>
	<th></th>
</tr>


<?php
if(sizeof($result_vrf) > 0) {
	# print vlans
	foreach($result_vrf as $vrf) {
		# cast
		$vrf = (array) $vrf;

		print '<tr class="nolink">' . "\n";
		print ' <td><dd>'. $vrf['name']      .'</dd></td>' . "\n";
		print ' <td><dd>'. $vrf['rd']     .'</dd></td>' . "\n";
		print ' <td><dd>'. $vrf['description'] .'</dd></td>' . "\n";
		# custom fields
		if(sizeof($custom_vrf_fields) > 0) {
			foreach($custom_vrf_fields as $field) {
				if(!in_array($field['name'], $hidden_vrf_fields)) {
					$vrf[$field['name']] = $Result->create_links ($vrf[$field['name']], $field['type']);
					print "	<td class='hidden-xs hidden-sm'>".$vrf[$field['name']]."</td>";
				}
			}
		}
		# for admins print link
		print " <td class='actions'>";
		if($User->is_admin(false)) {
		print '<div class="btn-group">';
		print '	<a class="btn btn-xs btn-default vrfManagement" data-action="edit"   data-vrfid="'.$vrf['vrfId'].'"><i class="fa fa-gray fa-pencil"></i></a>';
		print '	<a class="btn btn-xs btn-default vrfManagement" data-action="delete" data-vrfid="'.$vrf['vrfId'].'"><i class="fa fa-gray fa-times"></i></a>';
		print '</div>';
		}
		print "</td>";
		print '</tr>'. "\n";
    }
}
?>

</table>
<?php
if(sizeof($result_vrf) == 0) {
	$Result->show("info", _("No results"), false);
}