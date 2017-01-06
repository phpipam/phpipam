<?php

/*
 * Script to display search results
 **********************************/

# verify that user is logged in
$User->check_user_session();

# set searchterm
if(isset($_REQUEST['ip'])) {
	// trim
	$_REQUEST['ip'] = trim($_REQUEST['ip']);
	// escape
	$_REQUEST['ip'] = htmlspecialchars($_REQUEST['ip']);

	$search_term = @$search_term=="search" ? "" : $_REQUEST['ip'];
}

# change * to % for database wildchar
$search_term = trim($search_term);
$search_term = str_replace("*", "%", $search_term);

// IP address low/high reformat
if (preg_match('/^[a-f0-9.:]+$/i', $search_term)) {
    // identify
    $type = $Addresses->identify_address( $search_term ); //identify address type

    # reformat if IP address for search
    if ($type == "IPv4") 		{ $search_term_edited = $Tools->reformat_IPv4_for_search ($search_term); }	//reformat the IPv4 address!
    elseif($type == "IPv6") 	{ $search_term_edited = $Tools->reformat_IPv6_for_search ($search_term); }	//reformat the IPv4 address!
}

# get all custom fields
$custom_address_fields = $_REQUEST['addresses']=="on" ? $Tools->fetch_custom_fields ("ipaddresses") : array();
$custom_subnet_fields  = $_REQUEST['subnets']=="on"   ? $Tools->fetch_custom_fields ("subnets") : array();
$custom_vlan_fields    = $_REQUEST['vlans']=="on"     ? $Tools->fetch_custom_fields ("vlans") : array();
$custom_vrf_fields     = $_REQUEST['vrf']=="on"       ? $Tools->fetch_custom_fields ("vrf") : array();
$custom_pstn_fields    = $_REQUEST['pstn']=="on"      ? $Tools->fetch_custom_fields ("pstnPrefixes") : array();
$custom_pstnM_fields   = $_REQUEST['pstn']=="on"      ? $Tools->fetch_custom_fields ("pstnNumbers") : array();

# set hidden custom fields
$hidden_fields = json_decode($User->settings->hiddenCustomFields, true);

$hidden_address_fields = is_array(@$hidden_fields['ipaddresses']) ? $hidden_fields['ipaddresses'] : array();
$hidden_subnet_fields  = is_array(@$hidden_fields['subnets']) ? $hidden_fields['subnets'] : array();
$hidden_vlan_fields    = is_array(@$hidden_fields['vlans']) ? $hidden_fields['vlans'] : array();
$hidden_vrf_fields     = is_array(@$hidden_fields['vrf']) ? $hidden_fields['vrf'] : array();
$hidden_pstn_fields    = is_array(@$hidden_fields['pstnPrefixes']) ? $hidden_fields['pstnPrefixes'] : array();
$hidden_pstnn_fields   = is_array(@$hidden_fields['pstnNumbers']) ? $hidden_fields['pstnNumbers'] : array();

# set selected address fields array
$selected_ip_fields = $User->settings->IPfilter;
$selected_ip_fields = explode(";", $selected_ip_fields);

# set col size
$fieldSize 	= sizeof($selected_ip_fields);
$mySize 	= sizeof($custom_address_fields);
$colSpan 	= $fieldSize + $mySize + 4;



/** search **/

# search addresses
if(@$_REQUEST['addresses']=="on" && strlen($_REQUEST['ip'])>0) 	{ $result_addresses = $Tools->search_addresses($search_term, $search_term_edited['high'], $search_term_edited['low'], $custom_address_fields); }
# search subnets
if(@$_REQUEST['subnets']=="on" && strlen($_REQUEST['ip'])>0) 	{ $result_subnets   = $Tools->search_subnets($search_term, $search_term_edited['high'], $search_term_edited['low'], $_REQUEST['ip'], $custom_subnet_fields); }
# search vlans
if(@$_REQUEST['vlans']=="on" && strlen($_REQUEST['ip'])>0) 		{ $result_vlans     = $Tools->search_vlans($search_term, $custom_vlan_fields); }
# search vrf
if(@$_REQUEST['vrf']=="on" && strlen($_REQUEST['ip'])>0) 		{ $result_vrf       = $Tools->search_vrfs($search_term, $custom_vrf_fields); }
# search pstn prefixes
if(@$_REQUEST['pstn']=="on" && strlen($_REQUEST['ip'])>0) 		{ $result_pstn      = $Tools->search_pstn_refixes($search_term, $custom_pstn_fields); }
# search pstn numbers
if(@$_REQUEST['pstn']=="on" && strlen($_REQUEST['ip'])>0) 		{ $result_pstnn     = $Tools->search_pstn_numbers($search_term, $custom_pstnn_fields); }

// all are off?
if(!isset($_REQUEST['addresses']) && !isset($_REQUEST['subnets']) && !isset($_REQUEST['vlans']) && !isset($_REQUEST['vrf']) && !isset($_REQUEST['pstn']) ) {
    include("search-tips.php");
}
// empty request
elseif(strlen($_REQUEST['ip'])==0)  {
    include("search-tips.php");
}
// ok, search results print
else {
if(sizeof($result_subnets)!=0 || sizeof($result_addresses)!=0 || sizeof($result_vlans)!=0 || sizeof($result_vrf)!=0 || sizeof($result_pstn)!=0) {
    // export
	print('<a href="'.create_link(null).'" id="exportSearch" rel="tooltip" data-post="'.$search_term.'" title="'._('Export All results to XLS').'"><button class="btn btn-xs btn-default"><i class="fa fa-download"></i> '._('Export All results to XLS').'</button></a>');
}
?>

<!-- !subnets -->
<?php if(@$_REQUEST['subnets']=="on") { ?>
<br>
<h4><?php print _('Search results (Subnet list)');?>:</h4>
<hr>

<table class="searchTable table table-striped table-condensed table-top">

<!-- headers -->
<tr id="searchHeader">
	<th><?php print _('Section');?></th>
	<th><?php print _('Subnet');?></th>
	<th><?php print _('Description');?></th>
	<th><?php print _('Master subnet');?></th>
	<th><?php print _('VLAN');?></th>
	<th><?php print _('VRF');?></th>
	<th><?php print _('Requests');?></th>
	<?php
	if(sizeof($custom_subnet_fields) > 0) {
		foreach($custom_subnet_fields as $field) {
			if(!in_array($field['name'], $hidden_subnet_fields)) {
				print "	<th class='hidden-xs hidden-sm'>$field[name]</th>";
			}
		}
	}
	?>
	<th style="width:5px;"></th>
</tr>
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
				print ' <td>'. @$vlan['number'] .'</td>' . "\n";
				//vrf
				print ' <td>'. @$vrf['name'] .'</td>' . "\n";
				//requests
				print ' <td>'. _($line['allowRequests']) .'</td>' . "\n";

				# custom fields
				if(sizeof($custom_subnet_fields) > 0) {
					foreach($custom_subnet_fields as $field) {
						if(!in_array($field['name'], $hidden_subnet_fields)) {
							$line[$field['name']] = $Result->create_links ($line[$field['name']], $field['type']);
							print "	<td class='hidden-xs hidden-sm'>".$line[$field['name']]."</td>";
						}
					}
				}

				#locked for writing
				if($subnet_permission > 1) {
					if(@$master_subnet['isFolder']==1) {
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
print "</table>";
# show text if no results
if($m==0) { $Result->show("info", _("No results"), false); }
}
?>



<!-- !addresses -->
<?php if(@$_REQUEST['addresses']=="on") { ?>
<br>
<h4> <?php print _('Search results (IP address list)');?>:</h4>
<hr>

<!-- search result table -->
<table class="searchTable table table-striped table-condensed table-top">

<!-- headers -->
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
	if(in_array('switch', $selected_ip_fields))										{ print '<th class="hidden-sm hidden-xs">'._('Device').'</th>'. "\n"; $address_span++; }
	# port
	if(in_array('port', $selected_ip_fields)) 										{ print '<th>'._('Port').'</th>'. "\n"; $address_span++; }
	# owner and note
	if( (in_array('owner', $selected_ip_fields)) && (in_array('note', $selected_ip_fields)) ) { print '<th colspan="2" class="hidden-sm hidden-xs">'._('Owner').'</th>'. "\n"; $address_span=$address_span+2; }
	else if (in_array('owner', $selected_ip_fields)) 								{ print '<th class="hidden-sm hidden-xs">'._('Owner').'</th>'. "\n"; $address_span++; }
	else if (in_array('note', $selected_ip_fields)) 								{ print '<th></th>'. "\n"; $address_span++; }

	# custom fields
	if(sizeof($custom_address_fields) > 0) {
		foreach($custom_address_fields as $field) {
			if(!in_array($field['name'], $hidden_address_fields)) 					{ print "<th class='hidden-xs hidden-sm'>".$field['name']."</th>"; $address_span++; }
		}
	}

	# actions
	print '<th class="actions"></th>';
?>
</tr>

<!-- IP addresses -->
<?php

$m = 0;		//for section change
$n = 0;		//fpr ermission and result count

/* if no result print nothing found */
if(sizeof($result_addresses) > 0) {
	/* print content */
	foreach ($result_addresses as $line) {
		# cast
		$line = (array) $line;

		# check permission
		$subnet_permission  = $Subnets->check_permission($User->user, $line['subnetId']);
		if($subnet_permission > 0) {
			$n++;

			//get the Subnet details
			$subnet  = (array) $Subnets->fetch_subnet (null, $line['subnetId']);
			//get section
			$section = (array) $Sections->fetch_section (null, $subnet['sectionId']);

			//detect section change and print headers
			if ($result_addresses[$m]->subnetId != @$result_addresses[$m-1]->subnetId) {
				print '<tr>' . "\n";
				print '	<th colspan="'. $address_span .'">'. $section['name'] . ' :: <a href="'.create_link("subnets",$subnet['sectionId'],$subnet['id']).'" style="font-weight:300">' . $subnet['description'] .' ('. $Subnets->transform_to_dotted($subnet['subnet']) .'/'. $subnet['mask'] .')</a></th>' . "\n";
				print '</tr>';
			}
			$m++;

			//print table
			print '<tr class="ipSearch" id="'. $line['id'] .'" subnetId="'. $line['subnetId'] .'" sectionId="'. $subnet['sectionId'] .'" link="'. $section['name'] .'|'. $subnet['id'] .'">'. "\n";
			//address
			print ' <td><a href="'.create_link("subnets",$subnet['sectionId'],$subnet['id'],"address-details",$line['id']).'">'. $Subnets->transform_to_dotted($line['ip_addr'])."</a>";
			//tag
			print $Addresses->address_type_format_tag($line['state']);
			print ' </td>' . "\n";
			//description
			print ' <td>'. $Result->shorten_text($line['description'], $chars = 50) .'</td>' . "\n";
			//dns
			print ' <td>'. $line['dns_name']  .'</td>' . "\n";
			//mac
			if(in_array('mac', $selected_ip_fields)) {
				print '	<td>'. "\n";
				if(strlen($line['mac']) > 0) {
					print '<i class="fa fa-sitemap fa-gray" rel="tooltip" title="MAC: '. $line['mac'] .'"></i>'. "\n";
				}
				print '	</td>'. "\n";
			}
			//device
			if(in_array('switch', $selected_ip_fields)) 										{
				if(strlen($line['switch'])>0 && $line['switch']!="0") {
					# get switch
					$switch = (array) $Tools->fetch_object("devices", "id", $line['switch']);
					$line['switch'] = $switch['hostname'];
				}
				else {
					$line['switch'] = "/";
				}

				print ' <td class="hidden-sm hidden-xs">'. $line['switch']  .'</td>' . "\n";
			}
			//port
			if(in_array('port', $selected_ip_fields)) 										{ print ' <td>'. $line['port']  .'</td>' . "\n"; }
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
			else if (in_array('owner', $selected_ip_fields)) 								{ print ' <td class="hidden-sm hidden-xs">'. $line['owner']  .'</td>' . "\n";	}
			//note only
			else if (in_array('note', $selected_ip_fields)) {
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
						$line[$field['name']] = $Result->create_links ($line[$field['name']], $field['type']);
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
	}
}
?>
</table>
<?php
if($n == 0) {
	$Result->show("info", _("No results"), false);
}
}
?>


<!-- !vlan -->
<?php if(@$_REQUEST['vlans']=="on") { ?>
<!-- search result table -->
<br>
<h4><?php print _('Search results (VLANs)');?>:</h4>
<hr>

<table class="searchTable table table-striped table-condensed table-top">

<!-- headers -->
<tr id="searchHeader">
	<th><?php print _('Name');?></th>
	<th><?php print _('Number');?></th>
	<th><?php print _('Description');?></th>
	<?php
	if(sizeof($custom_vlan_fields) > 0) {
		foreach($custom_vlan_fields as $field) {
			if(!in_array($field['name'], $hidden_vlan_fields)) {
				print "	<th class='hidden-xs hidden-sm'>$field[name]</th>";
			}
		}
	}
	?>
	<th></th>
</tr>


<?php
if(sizeof($result_vlans) > 0) {
	# print vlans
	foreach($result_vlans as $vlan) {
		# cast
		$vlan = (array) $vlan;

		print '<tr class="nolink">' . "\n";
		print ' <td><dd>'. $vlan['name']      .'</dd></td>' . "\n";
		print ' <td><dd><a href="'.create_link("tools","vlan",$vlan['domainId'],$vlan['vlanId']).'">'. $vlan['number']     .'</a></dd></td>' . "\n";
		print ' <td><dd>'. $vlan['description'] .'</dd></td>' . "\n";
		# custom fields
		if(sizeof($custom_vlan_fields) > 0) {
			foreach($custom_vlan_fields as $field) {
				if(!in_array($field['name'], $hidden_vlan_fields)) {
					$vlan[$field['name']] = $Result->create_links ($vlan[$field['name']], $field['type']);
					print "	<td class='hidden-xs hidden-sm'>".$vlan[$field['name']]."</td>";
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

</table>
<?php
if(sizeof($result_vlans) == 0) {
	$Result->show("info", _("No results"), false);
}
?>
<?php } ?>


<!-- !vrf -->
<?php if(@$_REQUEST['vrf']=="on") { ?>
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
?>
<?php } ?>



<!-- !pstn prefixes -->
<?php if(@$_REQUEST['pstn']=="on") { ?>
<!-- search result table -->
<br>
<h4><?php print _('Search results (PSTN Prefixes)');?>:</h4>
<hr>

<table class="searchTable table table-striped table-condensed table-top">

<!-- headers -->
<tr id="searchHeader">
	<th><?php print _('Name');?></th>
	<th><?php print _('Prefix');?></th>
	<th><?php print _('Range');?></th>
	<th><?php print _('Device');?></th>
	<?php
	if(sizeof($custom_pstn_fields) > 0) {
		foreach($custom_pstn_fields as $field) {
			if(!in_array($field['name'], $hidden_pstn_fields)) {
				print "	<th class='hidden-xs hidden-sm'>$field[name]</th>";
			}
		}
	}
	?>
	<th></th>
</tr>
<?php
if(sizeof($result_pstn) > 0) {
	# print vlans
	foreach($result_pstn as $pstn) {
		print "<tr class='nolink'>";
		print " <td><dd>$pstn->name</dd></td>";
		print " <td><dd><a href='".create_link("tools","pstn-prefixes",$pstn->id)."'>$pstn->prefix</a></dd></td>";
		print " <td><dd>".$pstn->prefix.$pstn->start." - ".$pstn->prefix.$pstn->stop."</dd></td>";
		//device										{
		if(strlen($pstn->deviceId)>0 && $pstn->deviceId!="0") {
			$switch = $Tools->fetch_object("devices", "id", $pstn->deviceId);
			$pstn->deviceId = $switch===false ? "/" : "<a href='".create_link("tools", "devices", $switch->id)."'>".$switch->hostname."</a>";
		}
		else {
			$pstn->deviceId = "/";
		}

		print ' <td class="hidden-sm hidden-xs">'. $pstn->deviceId  .'</td>' . "\n";

		# custom fields
		if(sizeof($custom_pstn_fields) > 0) {
			foreach($custom_pstn_fields as $field) {
				if(!in_array($field['name'], $hidden_pstn_fields)) {
					$pstn->{$field['name']} = $Result->create_links ($pstn->{$field['name']}, $field['type']);
					print "	<td class='hidden-xs hidden-sm'>".$pstn->{$field['name']}."</td>";
				}
			}
		}
		# for admins print link
		print " <td class='actions'>";
		if($User->is_admin(false)) {
		print '<div class="btn-group">';
		print '	<a class="btn btn-xs btn-default editPSTN" data-action="edit"   data-id="'.$pstn->id.'"><i class="fa fa-gray fa-pencil"></i></a>';
		print '	<a class="btn btn-xs btn-default editPSTN" data-action="delete" data-id="'.$pstn->id.'"><i class="fa fa-gray fa-times"></i></a>';
		print '</div>';
		}
		print "</td>";
		print '</tr>'. "\n";
    }
}
?>

</table>
<?php
if(sizeof($result_pstn) == 0) {
	$Result->show("info", _("No results"), false);
}
?>
<?php } ?>






<!-- !pstn numbers -->
<?php if(@$_REQUEST['pstn']=="on") { ?>
<!-- search result table -->
<br>
<h4><?php print _('Search results (PSTN Numbers)');?>:</h4>
<hr>

<table class="searchTable table table-striped table-condensed table-top">

<!-- headers -->
<tr id="searchHeader">
	<th><?php print _('Name');?></th>
	<th><?php print _('Number');?></th>
	<th><?php print _('Owner');?></th>
	<th><?php print _('Device');?></th>
	<?php
	if(sizeof($custom_pstnn_fields) > 0) {
		foreach($custom_pstnn_fields as $field) {
			if(!in_array($field['name'], $hidden_pstnn_fields)) {
				print "	<th class='hidden-xs hidden-sm'>$field[name]</th>";
			}
		}
	}
	?>
	<th></th>
</tr>
<?php
if(sizeof($result_pstnn) > 0) {
	# print vlans
	foreach($result_pstnn as $pstnn) {
		print "<tr class='nolink'>";
		print " <td><dd>$pstnn->name</dd></td>";
		print " <td><dd><a href='".create_link("tools","pstn-prefixes",$pstnn->prefix)."'>$pstnn->number</a></dd></td>";
		print " <td><dd>$pstnn->owner</dd></td>";
		//device										{
		if(strlen($pstnn->deviceId)>0 && $pstnn->deviceId!="0") {
			$switch = $Tools->fetch_object("devices", "id", $pstnn->deviceId);
			$pstnn->deviceId = $switch===false ? "/" : "<a href='".create_link("tools", "devices", $switch->id)."'>".$switch->hostname."</a>";
		}
		else {
			$pstnn->deviceId = "/";
		}
		print ' <td class="hidden-sm hidden-xs">'. $pstnn->deviceId  .'</td>' . "\n";

		# custom fields
		if(sizeof($custom_pstnn_fields) > 0) {
			foreach($custom_pstnn_fields as $field) {
				if(!in_array($field['name'], $hidden_pstnn_fields)) {
					$pstnn->{$field['name']} = $Result->create_links ($pstnn->{$field['name']}, $field['type']);
					print "	<td class='hidden-xs hidden-sm'>".$pstnn->{$field['name']}."</td>";
				}
			}
		}
		# for admins print link
		print " <td class='actions'>";
		if($User->is_admin(false)) {
		print '<div class="btn-group">';
		print '	<a class="btn btn-xs btn-default editPSTNnumber" data-action="edit"   data-id="'.$pstnn->id.'"><i class="fa fa-gray fa-pencil"></i></a>';
		print '	<a class="btn btn-xs btn-default editPSTNnumber" data-action="delete" data-id="'.$pstnn->id.'"><i class="fa fa-gray fa-times"></i></a>';
		print '</div>';
		}
		print "</td>";
		print '</tr>'. "\n";
    }
}
?>

</table>
<?php
if(sizeof($result_pstnn) == 0) {
	$Result->show("info", _("No results"), false);
}
?>
<?php } ?>


<?php } ?>


<!-- export holder -->
<div class="exportDIVSearch"></div>
