<?php

/*
 * Script to display search results
 **********************************/

# verify that user is logged in
$User->check_user_session();

# get all custom fields
$custom_vlan_fields = $_REQUEST['vlans']=="on"     ? $Tools->fetch_custom_fields ("vlans") : array();
$hidden_vlan_fields = is_array(@$hidden_fields['vlans']) ? $hidden_fields['vlans'] : array();

# search vlans
$result_vlans = $Tools->search_vlans($searchTerm, $custom_vlan_fields);
?>

<br>
<h4><?php print _('Search results (VLANs)');?>:</h4>
<hr>

<table class="searchTable table sorted table-striped table-condensed table-top" data-cookie-id-table="search_vlan">

<!-- headers -->
<thead>
<tr id="searchHeader">
	<th><?php print _('Number');?></th>
	<th><?php print _('Name');?></th>
	<th><?php print _('Description');?></th>
	<?php
	if(sizeof($custom_vlan_fields) > 0) {
		foreach($custom_vlan_fields as $field) {
			if(!in_array($field['name'], $hidden_vlan_fields)) {
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
if(sizeof($result_vlans) > 0) {
	# print vlans
	foreach($result_vlans as $vlan) {
		# cast
		$vlan = (array) $vlan;

		print '<tr class="nolink">' . "\n";
		print ' <td><dd><a class="btn btn-xs btn-default" href="'.create_link("tools","vlan",$vlan['domainId'],$vlan['vlanId']).'">'. $vlan['number']     .'</a></dd></td>' . "\n";
		print ' <td><dd>'. $vlan['name']      .'</dd></td>' . "\n";
		print ' <td><dd>'. $vlan['description'] .'</dd></td>' . "\n";
		# custom fields
		if(sizeof($custom_vlan_fields) > 0) {
			foreach($custom_vlan_fields as $field) {
				if(!in_array($field['name'], $hidden_vlan_fields)) {
					$vlan[$field['name']] = $Result->create_links ($vlan[$field['name']], $field['type']);
					print "	<td class='hidden-xs hidden-sm'>";
					$Tools->print_custom_field ($field['type'], $vlan[$field['name']]);
					print "</td>";
				}
			}
		}
		# for admins print link
		print " <td class='actions'>";
		if($User->is_admin(false)) {
		print '<div class="btn-group">';
		print '	<a class="btn btn-xs btn-default editVLAN" data-action="edit"   data-vlanid="'.$vlan['vlanId'].'"><i class="fa fa-gray fa-pencil"></i></a>';
		print '	<a class="btn btn-xs btn-default editVLAN" data-action="delete" data-vlanid="'.$vlan['vlanId'].'"><i class="fa fa-gray fa-times"></i></a>';
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
if(sizeof($result_vlans) == 0) {
	$Result->show("info", _("No results"), false);
}