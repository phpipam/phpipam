<?php

/*
 * Script to display search results
 **********************************/

# verify that user is logged in
$User->check_user_session();

# get all custom fields
$custom_subnet_fields = $GET->subnets=="on"   ? $Tools->fetch_custom_fields ("subnets") : array();
$hidden_subnet_fields = isset($hidden_fields['subnets']) ? $hidden_fields['subnets'] : array();

# search subnets
$result_subnets = $Tools->search_subnets($searchTerm, $searchTerm_edited['high'], $searchTerm_edited['low'], $GET->ip, $custom_subnet_fields);
?>

<!-- !subnets -->
<br>
<h4><?php print _('Search results (Subnet list)');?>:</h4>
<hr>

<table class="searchTable table sorted table-striped table-condensed table-top" data-cookie-id-table="search_addresses">

<!-- headers -->
<thead>
<tr id="searchHeader">
	<th><?php print _('Section');?></th>
	<th><?php print _('Subnet');?></th>
	<th><?php print _('Description');?></th>
	<th><?php print _('Master subnet');?></th>
	<?php if($User->get_module_permissions ("vlan")>=User::ACCESS_R) { ?>
	<th><?php print _('VLAN');?></th>
	<?php } ?>
	<?php if($User->get_module_permissions ("vrf")>=User::ACCESS_R) { ?>
	<th><?php print _('VRF');?></th>
	<?php } ?>
	<th><?php print _('Requests');?></th>
	<?php
	if(sizeof($custom_subnet_fields) > 0) {
		foreach($custom_subnet_fields as $field) {
			if(!in_array($field['name'], $hidden_subnet_fields)) {
				print "	<th class='hidden-xs hidden-sm'>".$Tools->print_custom_field_name ($field['name'])."</th>";
			}
		}
	}
	?>
	<th style="width:5px;"></th>
</tr>
</thead>

<tbody>
<?php
	$m = 0;		//to count success subnets because of permissions

	/** subnet results **/
	if(sizeof($result_subnets) > 0) {
		# loop
		foreach($result_subnets as $line) {
			# cast
			$line = (array) $line;

			# check permission
			$subnet_permission  = $Subnets->check_permission($User->user, $line['id']);
			if($subnet_permission > 0) {
				$m++;

				//get section details
				$section = (array) $Sections->fetch_section(null, $line['sectionId']);
				//get vlan number
				$vlan 	 = (array) $Tools->fetch_object("vlans", "vlanId", $line['vlanId']);
				//get vrf name
				$vrf     = (array) $Tools->fetch_object("vrf", "vrfId", $line['vrfId']);
				//format requests
				$line['allowRequests'] = $line['allowRequests']==1 ? "enabled" : "disabled";

				//format master subnet
				if($line['masterSubnetId'] == 0) 		{ $master_text = "/"; }
				else {
					$master_subnet = (array) $Subnets->fetch_subnet (null, $line['masterSubnetId']);
					# folder?
					if($master_subnet['isFolder']==1) 	{ $master_text = "<i class='fa fa-folder-o fa fa-gray'></i> $master_subnet[description]"; }
					else 								{ $master_text = "$master_subnet[ip]/$master_subnet[mask]"; }
				}

				//tr
				print '<tr class="subnetSearch" subnetId="'. $line['id'] .'" sectionName="'. $section['name'] .'" sectionId="'. $section['id'] .'" link="'. $section['name'] .'|'. $line['id'] .'">'. "\n";

				//section
				print '	<td>'. $section['name'] . '</td>'. "\n";
				//folder?
				if($line['isFolder']==1) {
				print '	<td><a href="'.create_link("subnets",$line['sectionId'],$line['id']).'"><i class="fa fa-folder-o fa fa-gray"></i> '.$line['description'].'</a></td>'. "\n";
				} else {
				print '	<td><a href="'.create_link("subnets",$line['sectionId'],$line['id']).'">'. $Subnets->transform_to_dotted($line['subnet']) . '/'.$line['mask'].'</a></td>'. "\n";
				}
				print ' <td><a href="'.create_link("subnets",$line['sectionId'],$line['id']).'">'. $line['description'] .'</a></td>' . "\n";
				//master
				print ' <td>'. $master_text .'</td>' . "\n";
				//vlan
				if($User->get_module_permissions ("vlan")>=User::ACCESS_R)
				print ' <td>'. @$vlan['number'] .'</td>' . "\n";
				//vrf
				if($User->get_module_permissions ("vrf")>=User::ACCESS_R)
				print ' <td>'. @$vrf['name'] .'</td>' . "\n";
				//requests
				print ' <td>'. _($line['allowRequests']) .'</td>' . "\n";

				# custom fields
				if(sizeof($custom_subnet_fields) > 0) {
					foreach($custom_subnet_fields as $field) {
						if(!in_array($field['name'], $hidden_subnet_fields)) {
							$line[$field['name']] = $Tools->create_links ($line[$field['name']], $field['type']);
							print "	<td class='hidden-xs hidden-sm'>".$line[$field['name']]."</td>";
						}
					}
				}

				#locked for writing
				if($subnet_permission > 1) {
					if(@$line['isFolder']=="1") {
						print "	<td><button class='btn btn-xs btn-default add_folder' data-action='edit'  data-subnetId='$line[id]' data-sectionId='$line[sectionId]' href='#' rel='tooltip' data-container='body'  title='"._('Edit folder details')."'>		<i class='fa fa-gray fa fa-pencil'>  </i></a>";
					} else {
						print "	<td><button class='btn btn-xs btn-default edit_subnet' data-action='edit' data-subnetId='$line[id]' data-sectionId='$line[sectionId]' href='#' rel='tooltip' data-container='body'  title='"._('Edit subnet details')."'>		<i class='fa fa-gray fa fa-pencil'>  </i></a>";
					}
				}
				else {
					print "	<td><button class='btn btn-xs btn-default disabled' rel='tooltip' data-container='body'  title='"._('Edit subnet (disabled)')."'>	<i class='fa fa-gray fa fa-pencil'>  </i></button>";
				}
				print '</tr>'. "\n";
			}
		}
	}
print "</tbody>";
print "</table>";

# show text if no results
if($m==0) {
	$Result->show("info", _("No results"), false);
}