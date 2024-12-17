<?php

# verify that user is logged in
$User->check_user_session();

# get all custom fields
$custom_address_fields = $GET->addresses=="on" ? $Tools->fetch_custom_fields ("ipaddresses") : array();
$hidden_address_fields = isset($hidden_fields['ipaddresses']) ? $hidden_fields['ipaddresses'] : array();

# search addresses
$result_addresses = $Tools->search_addresses($searchTerm, $searchTerm_edited['high'], $searchTerm_edited['low'], $custom_address_fields);
?>

<br>
<h4> <?php print _('Search results (IP address list)');?>:</h4>
<hr>

<!-- search result table -->
<table class="searchTable table sorted table-striped table-condensed table-top" data-cookie-id-table="search_subnets">

<!-- headers -->
<thead>
<tr id="searchHeader">
<?php
	$address_span = 4;
	print '<th>'._('IP address').'</th>'. "\n";
	# description
	print '<th>'._('Description').'</th>'. "\n";
	print '<th>'._('Hostname').'</th>'. "\n";
	# mac
	if(in_array('mac', $selected_ip_fields)) 										{ print '<th></th>'. "\n"; $address_span++; }
	# switch
	if($User->get_module_permissions ("devices")>=User::ACCESS_R) {
	if(in_array('switch', $selected_ip_fields))										{ print '<th class="hidden-sm hidden-xs">'._('Device').'</th>'. "\n"; $address_span++; }
	}
	# port
	if(in_array('port', $selected_ip_fields)) 										{ print '<th>'._('Port').'</th>'. "\n"; $address_span++; }
	# location
	if($User->get_module_permissions ("locations")>=User::ACCESS_R) {
	if(in_array('location', $selected_ip_fields)) 										{ print '<th>'._('Location').'</th>'. "\n"; $address_span++; }
	}
	# owner and note
	if( (in_array('owner', $selected_ip_fields)) && (in_array('note', $selected_ip_fields)) ) { print '<th class="hidden-sm hidden-xs">'._('Owner').'</th><th></th>'. "\n"; $address_span=$address_span+2; }
	elseif (in_array('owner', $selected_ip_fields)) 								{ print '<th class="hidden-sm hidden-xs">'._('Owner').'</th>'. "\n"; $address_span++; }
	elseif (in_array('note', $selected_ip_fields)) 								{ print '<th></th>'. "\n"; $address_span++; }

	# custom fields
	if(sizeof($custom_address_fields) > 0) {
		foreach($custom_address_fields as $field) {
			if(!in_array($field['name'], $hidden_address_fields)) 					{ print "<th class='hidden-xs hidden-sm'>".$Tools->print_custom_field_name ($field['name'])."</th>"; $address_span++; }
		}
	}

	# actions
	print '<th class="actions"></th>';
?>
</tr>
</thead>


<tbody>
<!-- IP addresses -->
<?php

$m = 0;		//for section change
$n = 0;		//for permission and result count

/* if no result print nothing found */
if(is_array($result_addresses)) {
	/* print content */
	foreach ($result_addresses as $line) {
		# cast
		$line = (array) $line;

		# check permission
		$subnet_permission  = $Subnets->check_permission($User->user, $line['subnetId']);
		if($subnet_permission > 0 && $Addresses->validate_address($line['ip_addr'])) {
			$n++;

			//get the Subnet details
			$subnet  = (array) $Subnets->fetch_subnet (null, $line['subnetId']);
			//get section
			$section = (array) $Sections->fetch_section (null, $subnet['sectionId']);

			//detect section change and print headers
			if ($result_addresses[$m]->subnetId != @$result_addresses[$m-1]->subnetId) {
				print '<tr>' . "\n";
				if($subnet['isFolder']) {
				print '	<td class="th" colspan="'. $address_span .'">'. $section['name'] . ' :: <a href="'.create_link("subnets",$subnet['sectionId'],$subnet['id']).'" style="font-weight:300">' . $subnet['description'].'</a></td>';
				}
				else {
				print '	<td class="th" colspan="'. $address_span .'">'. $section['name'] . ' :: <a href="'.create_link("subnets",$subnet['sectionId'],$subnet['id']).'" style="font-weight:300">' . $subnet['description'] .' ('. $Subnets->transform_to_dotted($subnet['subnet']) .'/'. $subnet['mask'] .')</a></td>';
				}
				print '</tr>';
			}

			//print table
			print '<tr class="ipSearch" id="'. $line['id'] .'" subnetId="'. $line['subnetId'] .'" sectionId="'. $subnet['sectionId'] .'" link="'. $section['name'] .'|'. $subnet['id'] .'">'. "\n";
			//address
			print ' <td class="ip"><a href="'.create_link("subnets",$subnet['sectionId'],$subnet['id'],"address-details",$line['id']).'">'. $Subnets->transform_to_dotted($line['ip_addr'])."</a>";
			//tag
			print $Addresses->address_type_format_tag($line['state']);
			print ' </td>' . "\n";
			//description
			print ' <td>'. $Addresses->shorten_text($line['description'], $chars = 50) .'</td>' . "\n";
			//dns
			print ' <td>'. $line['hostname']  .'</td>' . "\n";
			//mac
			if(in_array('mac', $selected_ip_fields)) {
				print '	<td>'. "\n";
				if(!is_blank($line['mac'])) {
					print "<i class='info fa fa-gray fa-sitemap' rel='tooltip' data-container='body' data-html='true' title='".$User->show_mac_and_vendor($line['mac'])."'></i>";
				}
				print '	</td>'. "\n";
			}
			//device
			if(in_array('switch', $selected_ip_fields) && $User->get_module_permissions ("devices")>=User::ACCESS_R) {
				# get device details
				$device = $Tools->fetch_object("devices", "id", $line['switch']);
				if (is_object($device)) {
					$rack = "";
					if ($User->settings->enableRACK == "1" && $User->get_module_permissions("racks") >= User::ACCESS_R && $device->rack > 0) {
						$rack = "<i class='btn btn-default btn-xs fa fa-server showRackPopup' data-rackid='" . $device->rack . "' data-deviceid='" . $device->id . "'></i>";
					}
					print "<td class='hidden-xs hidden-sm hidden-md'>$rack<a href='" . create_link("tools", "devices", $device->id) . "'>" . $device->hostname . "</a></td>";
				} else {
					print "<td class='hidden-xs hidden-sm hidden-md'></td>";
				}
			}
			//port
			if(in_array('port', $selected_ip_fields)) 										{ print ' <td>'. $line['port']  .'</td>' . "\n"; }
			//location
			if(in_array('location', $selected_ip_fields) && $User->get_module_permissions ("locations")>=User::ACCESS_R) {
				$location_name = $Tools->fetch_object("locations", "id", $line['location']);
				print ' <td>' . (is_object($location_name) ? $location_name->name : '') . '</td>' . "\n";
			}
			//owner and note
			if((in_array('owner', $selected_ip_fields)) && (in_array('note', $selected_ip_fields)) ) {
				print ' <td class="hidden-sm hidden-xs">'. $line['owner']  .'</td>' . "\n";
				print ' <td class="note hidden-sm hidden-xs">' . "\n";
				if(!empty($line['note'])) {
					$line['note'] = str_replace("\n", "<br>",$line['note']);
					print '<i class="fa fa-gray fa fa-comment-o" rel="tooltip" title="'. $line['note']. '"></i>' . "\n";
				}
				print '</td>'. "\n";
			}
			//owner only
			elseif (in_array('owner', $selected_ip_fields)) 								{ print ' <td class="hidden-sm hidden-xs">'. $line['owner']  .'</td>' . "\n";	}
			//note only
			elseif (in_array('note', $selected_ip_fields)) {
				print '<td class="note">' . "\n";
				if(!empty($line['note'])) {
					$line['note'] = str_replace("\n", "<br>",$line['note']);
					print '	<i class="fa fa-gray fa fa-comment-o" rel="tooltip" title="'. $line['note']. '"></i>' . "\n";
				}
				print '</td>'. "\n";
			}
			//custom fields
			if(sizeof($custom_address_fields) > 0) {
				foreach($custom_address_fields as $field) {
					if(!in_array($field['name'], $hidden_address_fields)){
						$line[$field['name']] = $Tools->create_links ($line[$field['name']], $field['type']);
						print '<td class="customField hidden-sm hidden-xs hidden-md">'. $line[$field['name']] .'</td>'. "\n";
					}
				}
			}

			# print action links if user can edit
			print "<td class='actions'>";
			print "	<div class='btn-group'>";

			if($subnet_permission > 1) {
				print "		<a class='edit_ipaddress   btn btn-xs btn-default modIPaddr' data-action='edit'   data-subnetId='$subnet[id]' data-id='".$line['id']."' href='#' 	rel='tooltip' data-container='body'  title='"._('Edit IP address details')."'>		<i class='fa fa-gray fa fa-pencil'>  </i></a>";
				print "		<a class='mail_ipaddress   btn btn-xs btn-default          ' href='#' data-id='".$line['id']."' rel='tooltip' data-container='body'  title='"._('Send mail notification')."'>														<i class='fa fa-gray fa fa-envelope-o'></i></a>";
				print "		<a class='delete_ipaddress btn btn-xs btn-default modIPaddr' data-action='delete' data-subnetId='$subnet[id]' data-id='".$line['id']."' href='#'  rel='tooltip' data-container='body'  title='"._('Delete IP address')."'>			<i class='fa fa-gray fa fa-times'>  </i></a>";
			}
			# unlocked
			else {
				print "		<a class='edit_ipaddress   btn btn-xs btn-default disabled' rel='tooltip' data-container='body'  title='"._('Edit IP address details (disabled)')."'>										<i class='fa fa-gray fa fa-pencil'>  </i></a>";
				print "		<a class='mail_ipaddress   btn btn-xs btn-default          ' href='#' data-id='".$line['id']."' rel='tooltip' data-container='body'  title='"._('Send mail notification')."'>				<i class='fa fa-gray fa fa-envelope'></i></a>";
				print "		<a class='delete_ipaddress btn btn-xs btn-default disabled' rel='tooltip' data-container='body'  title='"._('Delete IP address (disabled)')."'>												<i class='fa fa-gray fa fa-times'>  </i></a>";
			}
			print "	</div>";
			print "</td>";

			print '</tr>' . "\n";
		}
		$m++;
	}
}
?>
</tbody>
</table>
<?php
if($n == 0) {
	$Result->show("info", _("No results"), false);
}
