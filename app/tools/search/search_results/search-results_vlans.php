<?php

/*
 * Script to display search results
 **********************************/

# verify that user is logged in
$User->check_user_session();

# get all custom fields
$custom_vlan_fields = $GET->vlans=="on"     ? $Tools->fetch_custom_fields ("vlans") : array();
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
					$vlan[$field['name']] = $Tools->create_links ($vlan[$field['name']], $field['type']);
					print "	<td class='hidden-xs hidden-sm'>";
					$Tools->print_custom_field ($field['type'], $vlan[$field['name']]);
					print "</td>";
				}
			}
		}
        // actions
        print "<td class='actions'>";
        $links = [];
        if($User->get_module_permissions ("vlan")>=User::ACCESS_R) {
            $links[] = ["type"=>"header", "text"=>_("View")];
            $links[] = ["type"=>"link", "text"=>_("Show VLAN"), "href"=>create_link("tools", "vlan", $vlan['domainId'], $vlan['vlanId']), "icon"=>"eye", "visible"=>"dropdown"];
            $links[] = ["type"=>"divider"];
        }
        if($User->get_module_permissions ("vlan")>=User::ACCESS_RW) {
            $links[] = ["type"=>"header", "text"=>_("Manage")];
            $links[] = ["type"=>"link", "text"=>_("Edit VLAN"), "href"=>"", "class"=>"open_popup", "dataparams"=>" data-script='app/admin/vlans/edit.php' data-action='edit' data-vlanid='$vlan[vlanId]'", "icon"=>"pencil"];
        }
        if($User->get_module_permissions ("vlan")>=User::ACCESS_RWA) {
            $links[] = ["type"=>"link", "text"=>_("Delete VLAN"), "href"=>"", "class"=>"open_popup", "dataparams"=>" data-script='app/admin/vlans/edit.php' data-action='delete' data-vlanid='$vlan[vlanId]'", "icon"=>"times"];
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
if(sizeof($result_vlans) == 0) {
	$Result->show("info", _("No results"), false);
}