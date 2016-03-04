<script type="text/javascript">
/* fix for ajax-loading tooltips */
$('body').tooltip({ selector: '[rel=tooltip]' });
</script>

<?php
/**
 * Print sorted IP addresses
 ***********************************************************************/


# direct call, set default direction for sorting
if(!isset($_POST['direction'])) {

	# verify that user is logged in
	$User->check_user_session();
}

# We need DNS object
$DNS = new DNS ($Database, $User->settings);

/* verifications */
# checks
if(sizeof($subnet)==0) 					{ $Result->show("danger", _('Subnet does not exist'), true); }									//subnet doesnt exist
if($subnet_permission == 0)				{ $Result->show("danger", _('You do not have permission to access this network'), true); }		//not allowed to access
if(!is_numeric($_REQUEST['subnetId'])) 	{ $Result->show("danger", _('Invalid ID'), true); }												//subnet id must be numeric

/* selected and hidden fields */

# reset custom fields to ip addresses
$custom_fields = $Tools->fetch_custom_fields ('ipaddresses');
# set hidden custom fields
$hidden_cfields = json_decode($User->settings->hiddenCustomFields, true);
$hidden_cfields = is_array($hidden_cfields['ipaddresses']) ? $hidden_cfields['ipaddresses'] : array();

# set selected address fields array
$selected_ip_fields = explode(";", $User->settings->IPfilter);  																	//format to array
// if fw not set remove!
if($User->settings->enableFirewallZones != 1) { unset($selected_ip_fields['firewallAddressObject']); }
// set size
$selected_ip_fields_size = in_array('state', $selected_ip_fields) ? sizeof($selected_ip_fields)-1 : sizeof($selected_ip_fields);	//set size of selected fields
if($selected_ip_fields_size==1 && strlen($selected_ip_fields[0])==0) { $selected_ip_fields_size = 0; }								//fix for 0


/* Addresses and fields manupulations */

# save for visual display !
$addresses_visual = $addresses;
# new compress functions
$Addresses->addresses_types_fetch();
foreach($Addresses->address_types as $t) {
	if($t['compress']=="Yes" && $User->user->compressOverride!="Uncompress") {
		if(sizeof($addresses)>0 && $addresses!==false) {
			$addresses = $Addresses->compress_address_ranges ($addresses, $t['id']);
		}
	}
}

# remove custom fields if all are empty!
foreach($custom_fields as $field) {
	$sizeMyFields[$field['name']] = 0;				// default value
	# check against each IP address
	if($addresses!==false) {
		$addresses = (array) $addresses;
		foreach($addresses as $ip) {
			$ip = (array) $ip;
			if(strlen($ip[$field['name']]) > 0) {
				$sizeMyFields[$field['name']]++;		// +1
			}
		}
		# unset if value == 0
		if($sizeMyFields[$field['name']] == 0) {
			unset($custom_fields[$field['name']]);
		}
	}
}

# hidden custom
if(sizeof($custom_fields) > 0) {
	foreach($custom_fields as $ck=>$myField) 	{
		if(in_array($myField['name'], $hidden_cfields)) {
			unset($custom_fields[$ck]);
		}
	}
}

# set colspan for output
$colspan['empty']  = $selected_ip_fields_size + sizeof($custom_fields) +4;		//empty colspan
$colspan['unused'] = $selected_ip_fields_size + sizeof($custom_fields) +3;		//unused colspan
$colspan['dhcp']   = $selected_ip_fields_size + sizeof($custom_fields);			//dhcp colspan
$colspan['dhcp']   = in_array("firewallAddressObject", $selected_ip_fields) ? $colspan['dhcp']-1 : $colspan['dhcp'];
$colspan['dhcp']   = ($colspan['dhcp'] < 0) ? 0 : $colspan['dhcp'];				//dhcp colspan negative fix

# set ping statuses for warning and offline
$statuses = explode(";", $User->settings->pingStatus);
?>

<!-- print title and pagenum -->
<h4 style="margin-top:40px;">
<?php
if(!$slaves)		{ print _("IP addresses in $location "); }
elseif(@$orphaned)	{ print "<div class='alert alert-warning alert-block'>"._('Orphaned IP addresses for subnet')." <strong>$subnet[description]</strong> (".sizeof($addresses)." orphaned) <br><span class='text-muted' style='font-size:12px;margin-top:10px;'>"._('This happens if subnet contained IP addresses when new child subnet was created')."'<span><hr><a class='btn btn-sm btn-default' id='truncate' href='' data-subnetid='".$subnet['id']."'><i class='fa fa-times'></i> "._("Remove all")."</a></div>"; }
else 				{ print _("IP addresses belonging to ALL nested subnets"); }
?>
</h4>

<!-- table -->
<table class="ipaddresses sorted normalTable table table-striped table-condensed table-hover table-full table-top">

<!-- headers -->
<thead>
<tr class="th">

	<?php
	# IP address - mandatory
												  print "<th class='s_ipaddr'><span rel='tooltip' title='"._('Sort by IP address')."'>"._('IP address')."</span></th>";
	# hostname - mandatory
												  print "<th><span rel='tooltip' title='"._('Sort by hostname')."'>"._('Hostname')."</span</th>";
	# firewall address object - mandatory if enabled
	if(in_array('firewallAddressObject', $selected_ip_fields)) {
			# class
			$Zones = new FirewallZones ($Database);
			$zone = $Zones->get_zone_subnet_info ($subnet['id']);

			if($zone) {							  print "<th><span rel='tooltip' title='"._('Sort by firewall address object')."'>"._('FW object')."</span</th>"; }
	}
	# Description - mandatory
												  print "<th><span rel='tooltip' title='"._('Sort by description')."'			>"._('Description')."</span</th>";
	# MAC address
	if(in_array('mac', $selected_ip_fields)) 	{
    	$mac_title = $User->settings->enableMulticast=="1" ? "<th><span rel='tooltip' title='"._('Sort by MAC')."'>MAC</th>" : "<th></th>";
    	                                        { print "$mac_title"; }
    }
	# note
	if(in_array('note', $selected_ip_fields)) 	{ print "<th></th>"; }
	# switch
	if(in_array('switch', $selected_ip_fields)) { print "<th class='hidden-xs hidden-sm hidden-md'><span rel='tooltip' title='"._('Sort by device')."'>"._('Device')."</span</th>"; }
	# port
	if(in_array('port', $selected_ip_fields)) 	{ print "<th class='hidden-xs hidden-sm hidden-md'><span rel='tooltip' title='"._('Sort by port')."'>"._('Port')."</span</th>"; }
	# owner
	if(in_array('owner', $selected_ip_fields)) 	{ print "<th class='hidden-xs hidden-sm'><span rel='tooltip' title='"._('Sort by owner')."'>"._('Owner')."</span</th>"; }

	# custom fields
	if(sizeof($custom_fields) > 0) {
		foreach($custom_fields as $myField) 	{
			print "<th class='hidden-xs hidden-sm hidden-md'><span rel='tooltip' data-container='body' title='"._('Sort by')." $myField[name]'	>$myField[name]</span</th>";
		}
	}
	?>

	<!-- actions -->
	<th class="actions"></th>
</tr>
</thead>

<tbody>
<?php
/* Addresses content print */
$n = 0;							//addresses index
$m = sizeof($addresses) -1;		//last address index

# if no IP is configured only display free subnet!
if ($addresses===false || sizeof($addresses)==0) {
	if($User->user->hideFreeRange!=1 && $subnet['isFull']!="1") {
    	$unused = $Addresses->find_unused_addresses($Subnets->transform_to_decimal($subnet_detailed['network']), $Subnets->transform_to_decimal($subnet_detailed['broadcast']), $subnet['mask'], $empty=true );
		print '<tr class="th"><td colspan="'.$colspan['empty'].'" class="unused">'.$unused['ip'].' (' .$Subnets->reformat_number($unused['hosts']).')</td></tr>'. "\n";
    }
}
# print IP address
else {
	$n = 0;		//count for IP addresses - $n++ per IP address
	$g = 0;		//count for compress consecutive class

		foreach($addresses as $dummy) {

	       	#
	       	# first check for gaps from network to first host
	       	#

	       	# check gap between network address and first IP address
	       	if ( $n == 0 ) 											{ $unused = $Addresses->find_unused_addresses ( $Subnets->transform_to_decimal($subnet_detailed['network']), $addresses[$n]->ip_addr, $subnet['mask']); }
	       	# check unused space between IP addresses
	       	else {
	       		// compressed and dhcp?
	       		if($addresses[$n-1]->class=="compressed-range") 	{ $unused = $Addresses->find_unused_addresses ( $addresses[$n-1]->stopIP, $addresses[$n]->ip_addr, $subnet['mask'] );  }
	       		//uncompressed
	       		else 												{ $unused = $Addresses->find_unused_addresses ( $addresses[$n-1]->ip_addr, $addresses[$n]->ip_addr, $subnet['mask'] );  }
	       	}

	       	# if there is some result for unused print it - if sort == ip_addr
	       	if($User->user->hideFreeRange!=1 && $subnet['isFull']!="1") {
			    if ( $unused) {
	        		print "<tr class='th'>";
	        		print "	<td></td>";
	        		print "	<td colspan='$colspan[unused]' class='unused'>$unused[ip] ($unused[hosts])</td>";
	        		print "</tr>";
	        	}
        	}


			#
		    # print IP address
		    #

		    # ip - range
		    if(@$addresses[$n]->class=="compressed-range")
		    {
		    	print "<tr class='dhcp'>";
			    print "	<td>";
			    # status icon
			    if($subnet['pingSubnet']=="1") {
			    print "		<span class='status status-padded'></span>";
				}
			    print 		$Subnets->transform_to_dotted( $addresses[$n]->ip_addr).' - '.$Subnets->transform_to_dotted( $addresses[$n]->stopIP)." (".$addresses[$n]->numHosts.")";
			    print 		$Addresses->address_type_format_tag($addresses[$n]->state);
			    print "	</td>";
				print "	<td>".$Addresses->address_type_index_to_type($addresses[$n]->state)." ("._("range").")</td>";
        		if(in_array('firewallAddressObject', $selected_ip_fields) && $zone) {
        			print "	<td class=fw'>".$addresses[$n]->firewallAddressObject."</td>";
        		}
        		print "	<td>".$addresses[$n]->description."</td>";
        		if($colspan['dhcp']!=0)
        		print "	<td colspan='$colspan[dhcp]' class='unused'></td>";
			    // tr ends after!

		    }
		    # ip - normal
		    else
		    {
		 		print "<tr>";

			    # status icon
			    if($subnet['pingSubnet']=="1") {
				    //calculate
				    $tDiff = time() - strtotime($addresses[$n]->lastSeen);
				    if($addresses[$n]->excludePing=="1" ) { $hStatus = "padded"; $hTooltip = ""; }
				    elseif($tDiff < $statuses[0])	{ $hStatus = "success";	$hTooltip = "rel='tooltip' data-container='body' data-html='true' data-placement='left' title='"._("Device is alive")."<hr>"._("Last seen").": ".$addresses[$n]->lastSeen."'"; }
				    elseif($tDiff < $statuses[1])	{ $hStatus = "warning"; $hTooltip = "rel='tooltip' data-container='body' data-html='true' data-placement='left' title='"._("Device warning")."<hr>"._("Last seen").": ".$addresses[$n]->lastSeen."'"; }
				    elseif($tDiff < 2592000)		{ $hStatus = "error"; 	$hTooltip = "rel='tooltip' data-container='body' data-html='true' data-placement='left' title='"._("Device is offline")."<hr>"._("Last seen").": ".$addresses[$n]->lastSeen."'";}
				    elseif($addresses[$n]->lastSeen == "0000-00-00 00:00:00") { $hStatus = "neutral"; 	$hTooltip = "rel='tooltip' data-container='body' data-html='true' data-placement='left' title='"._("Device is offline")."<hr>"._("Last seen").": "._("Never")."'";}
				    else							{ $hStatus = "neutral"; $hTooltip = "rel='tooltip' data-container='body' data-html='true' data-placement='left' title='"._("Device status unknown")."'";}
			    }
			    else {
				    $hStatus = "hidden";
				    $hTooltip = "";
			    }

				# search for DNS records
				if($User->settings->enablePowerDNS==1 && $subnet['DNSrecords']==1 ) {
					# for ajax-loaded subnets
					if(!isset($PowerDNS)) { $PowerDNS = new PowerDNS ($Database); }

                    // search for hostname records
					$records = $PowerDNS->search_records ("name", $addresses[$n]->dns_name, 'name', true);
					$ptr	 = $PowerDNS->fetch_record ($addresses[$n]->PTR);
					unset($dns_records);
					if ($records !== false || $ptr!==false) {
						$dns_records[] = "<hr>";
						$dns_records[] = "<ul class='submenu-dns'>";
						if($records!==false) {
							foreach ($records as $r) {
								if($r->type!="SOA" && $r->type!="NS")
								$dns_records[]   = "<li><i class='icon-gray fa fa-gray fa-angle-right'></i> <span class='badge badge1 badge2 editRecord' data-action='edit' data-id='$r->id' data-domain_id='$r->domain_id'>$r->type</span> $r->content </li>";
							}
						}
						if($ptr!==false) {
								$dns_records[]   = "<li><i class='icon-gray fa fa-gray fa-angle-right'></i> <span class='badge badge1 badge2 editRecord' data-action='edit' data-id='$ptr->id' data-domain_id='$ptr->domain_id'>$ptr->type</span> $ptr->name </li>";
						}
						$dns_records[] = "</ul>";
						// if none ignore
						$dns_records = sizeof($dns_records)==3 ? "" : implode(" ", $dns_records);
					} else {
						$dns_records = "";
					}

					// search for IP records
					$records2 = $PowerDNS->search_records ("content", $addresses[$n]->ip, 'content', true);
					unset($dns_records2);
					if ($records2 !== false) {
                        $dns_cname_unique = array();        // unique CNAME records to prevent multiple
                        unset($cname);
						$dns_records2[] = "<hr>";
						$dns_records2[] = "<ul class='submenu-dns'>";
						foreach ($records2 as $r) {
							if($r->type!="SOA" && $r->type!="NS")
							$dns_records2[]   = "<li><i class='icon-gray fa fa-gray fa-angle-right'></i> <span class='badge badge1 badge2 editRecord' data-action='edit' data-id='$r->id' data-domain_id='$r->domain_id'>$r->type</span> $r->name </li>";
                            //search also for CNAME records
                            $dns_records_cname = $PowerDNS->seach_aliases ($r->name);
                            if($dns_records_cname!==false) {
                                foreach ($dns_records_cname as $cn) {
                                    if (!in_array($cn->name, $dns_cname_unique)) {
                                        $cname[] = "<li><i class='icon-gray fa fa-gray fa-angle-right'></i> <span class='badge badge1 badge2 editRecord' data-action='edit' data-id='$cn->id' data-domain_id='$cn->domain_id'>$cn->type</span> $cn->name </li>";
                                        $dns_cname_unique[] = $cn->name;
                                    }
                                }
                            }
					    }
                        // merge cnames
                        if (isset($cname)) {
                            foreach ($cname as $cna) {
                                $dns_records2[] = $cna;
                            }
                        }
						$dns_records2[] = "</ul>";
						// if none ignore
						$dns_records2 = sizeof($dns_records2)==3 ? "" : implode(" ", $dns_records2);
					} else {
						$dns_records2 = "";
					}
				}
				// disabled
				else {
					$dns_records = "";
					$dns_records2 = "";
					$button = "";
				}
				// add button
				if ($User->settings->enablePowerDNS==1) {
				// add new button
				if ($Subnets->validate_hostname($addresses[$n]->dns_name, false) && ($User->isadmin || @$User->user->pdns=="Yes"))
				$button = "<i class='fa fa-plus-circle fa-gray fa-href editRecord' data-action='add' data-id='".$Addresses->transform_address($addresses[$n]->ip_addr, "dotted")."' data-domain_id='".$addresses[$n]->dns_name."'></i>";
				else
				$button = "";
				}


			    // gateway
			    $gw = $addresses[$n]->is_gateway==1 ? "gateway" : "";

			    print "	<td class='ipaddress $gw'><span class='status status-$hStatus' $hTooltip></span><a href='".create_link("subnets",$subnet['sectionId'],$_REQUEST['subnetId'],"address-details",$addresses[$n]->id)."'>".$Subnets->transform_to_dotted( $addresses[$n]->ip_addr)."</a>";
			    if($addresses[$n]->is_gateway==1)						{ print " <i class='fa fa-info-circle fa-gateway' rel='tooltip' title='"._('Address is marked as gateway')."'></i>"; }
			    print $Addresses->address_type_format_tag($addresses[$n]->state);
			    print $dns_records2."</td>";

			    # resolve dns name
			    $resolve = $DNS->resolve_address($addresses[$n]->ip_addr, $addresses[$n]->dns_name, false, $subnet['nameserverId']);
																		{ print "<td class='$resolve[class] hostname'>$resolve[name] $button $dns_records</td>"; }

				# print firewall address object - mandatory if enabled
				if(in_array('firewallAddressObject', $selected_ip_fields) && $zone) {
					                                                    { print "<td class='fwzone'>".$addresses[$n]->firewallAddressObject."</td>"; }
				}

				# print description - mandatory
	        													  		  print "<td class='description'>".$addresses[$n]->description."</td>";
				# Print mac address icon!
				if(in_array('mac', $selected_ip_fields)) {
            	    # multicast check
            	    if ($User->settings->enableMulticast==1 && $Subnets->is_multicast ($addresses[$n]->ip_addr)) {
                	    $mtest = $Subnets->validate_multicast_mac ($addresses[$n]->mac, $subnet['sectionId'], $subnet['vlanId'], MCUNIQUE, $addresses[$n]->id);
                	    if ($mtest !== true) { $mclass = "text-danger"; $minfo = "<i class='fa fa-exclamation-triangle' rel='tooltip' title='"._('Duplicate MAC')."'></i>"; }
                	    else                 { $mclass = ""; $minfo = ""; }
                    }
					// multicast ?
					if ($User->settings->enableMulticast=="1" && $Subnets->is_multicast ($addresses[$n]->ip_addr))          { print "<td class='$mclass' style='white-space:nowrap;'>".$addresses[$n]->mac." $minfo</td>"; }
					elseif(!empty($addresses[$n]->mac)) 				{ print "<td class='narrow'><i class='info fa fa-gray fa-sitemap' rel='tooltip' data-container='body' title='"._('MAC').": ".$addresses[$n]->mac."'></i></td>"; }
					else 												{ print "<td class='narrow'></td>"; }
				}


	       		# print info button for hover
	       		if(in_array('note', $selected_ip_fields)) {
	        		if(!empty($addresses[$n]->note)) 					{ print "<td class='narrow'><i class='fa fa-gray fa-comment-o' rel='tooltip' data-container='body' data-html='true' title='".str_replace("\n", "<br>",$addresses[$n]->note)."'></td>"; }
	        		else 												{ print "<td class='narrow'></td>"; }
	        	}

	        	# print device
	        	if(in_array('switch', $selected_ip_fields)) {
		        	# get device details
		        	$device = (array) $Tools->fetch_device(null, $addresses[$n]->switch);
		        	# set rack
		        	if ($User->settings->enableRACK=="1")
		        	$rack = $device['rack']>0 ? "<i class='btn btn-default btn-xs fa fa-server showRackPopup' data-rackid='$device[rack]' data-deviceid='$device[id]'></i>" : "";
																		  print "<td class='hidden-xs hidden-sm hidden-md'>$rack <a href='".create_link("tools","devices","hosts",@$device['id'])."'>". @$device['hostname'] ."</a></td>";
				}

				# print port
				if(in_array('port', $selected_ip_fields)) 				{ print "<td class='hidden-xs hidden-sm hidden-md'>".$addresses[$n]->port."</td>"; }

				# print owner
				if(in_array('owner', $selected_ip_fields)) 				{ print "<td class='hidden-xs hidden-sm'>".$addresses[$n]->owner."</td>"; }

				# print custom fields
				if(sizeof($custom_fields) > 0) {
					foreach($custom_fields as $myField) 					{
						if(!in_array($myField['name'], $hidden_cfields)) 	{
							print "<td class='customField hidden-xs hidden-sm hidden-md'>";

							// create html links
							$addresses[$n]->$myField['name'] = $Result->create_links($addresses[$n]->$myField['name'], $myField['type']);

							//booleans
							if($myField['type']=="tinyint(1)")	{
								if($addresses[$n]->$myField['name'] == "0")		{ print _("No"); }
								elseif($addresses[$n]->$myField['name'] == "1")	{ print _("Yes"); }
							}
							//text
							elseif($myField['type']=="text") {
								if(strlen($addresses[$n]->$myField['name'])>0)	{ print "<i class='fa fa-gray fa-comment' rel='tooltip' data-container='body' data-html='true' title='".str_replace("\n", "<br>", $addresses[$n]->$myField['name'])."'>"; }
								else											{ print ""; }
							}
							else {
								print $addresses[$n]->$myField['name'];

							}
							print "</td>";
						}
					}
				}
		    }

			# print action links if user can edit
			print "<td class='btn-actions hidden-print'>";
			print "	<div class='btn-group'>";
			# orphaned
			if(@$orphaned && $subnet_permission > 1) {
				print "		<a class='move_ipaddress   btn btn-xs btn-default moveIPaddr' data-action='move'   data-subnetId='$subnet[id]' data-id='".$addresses[$n]->id."' href='#' rel='tooltip' title='"._('Move to different subnet')."'>		<i class='fa fa-gray fa-pencil'> </i></a>";
				print "		<a class='delete_ipaddress btn btn-xs btn-default modIPaddr'  data-action='delete' data-subnetId='$subnet[id]' data-id='".$addresses[$n]->id."' href='#' rel='tooltip' title='"._('Delete IP address')."'>				<i class='fa fa-gray fa-times'>  </i></a>";
			}
			# write permitted
			elseif( $subnet_permission > 1) {
				if(@$addresses[$n]->class=="compressed-range")
				{
					print "<a class='edit_ipaddress   btn btn-xs btn-default modIPaddr' data-action='edit'   data-subnetId='".$addresses[$n]->subnetId."' data-id='".$addresses[$n]->id."' data-stopIP='".$addresses[$n]->stopIP."' href='#'>				<i class='fa fa-gray fa-pencil'></i></a>";
					print "<a class='				   btn btn-xs btn-default disabled' href='#'>																																									<i class='fa fa-gray fa-cogs'></i></a>";
					print "<a class='				   btn btn-xs btn-default disabled' href='#'>																																									<i class='fa fa-gray fa-search'></i></a>";
					print "<a class='				   btn btn-xs btn-default disabled' href='#'>																																									<i class='fa fa-gray fa-envelope-o'></i></a>";
					print "<a class='delete_ipaddress btn btn-xs btn-default modIPaddr' data-action='delete' data-subnetId='".$addresses[$n]->subnetId."' data-id='".$addresses[$n]->id."' data-stopIP='".$addresses[$n]->stopIP."' href='#' id2='".$Subnets->transform_to_dotted($addresses[$n]->ip_addr)."'>		<i class='fa fa-gray fa-times'></i></a>";
				}
				else
				{
					print "<a class='edit_ipaddress   btn btn-xs btn-default modIPaddr' data-action='edit'   data-subnetId='".$addresses[$n]->subnetId."' data-id='".$addresses[$n]->id."' href='#' >															<i class='fa fa-gray fa-pencil'></i></a>";
					print "<a class='ping_ipaddress   btn btn-xs btn-default' data-subnetId='".$addresses[$n]->subnetId."' data-id='".$addresses[$n]->id."' href='#' rel='tooltip' data-container='body' title='"._('Check availability')."'>					<i class='fa fa-gray fa-cogs'></i></a>";
					print "<a class='search_ipaddress btn btn-xs btn-default         "; if(strlen($resolve['name']) == 0) { print "disabled"; } print "' href='".create_link("tools","search","on","off","off","off",$resolve['name'])."' "; if(strlen($resolve['name']) != 0)   { print "rel='tooltip' data-container='body' title='"._('Search same hostnames in db')."'"; } print ">	<i class='fa fa-gray fa-search'></i></a>";
					print "<a class='mail_ipaddress   btn btn-xs btn-default          ' href='#' data-id='".$addresses[$n]->id."' rel='tooltip' data-container='body' title='"._('Send mail notification')."'>																																		<i class='fa fa-gray fa-envelope-o'></i></a>";
					if(in_array('firewallAddressObject', $selected_ip_fields)) { if($zone) { print "<a class='fw_autogen	   	  btn btn-default btn-xs          ' href='#' data-subnetid='".$addresses[$n]->subnetId."' data-action='adr' data-ipid='".$addresses[$n]->id."' data-dnsname='".$addresses[$n]->dns_name."' rel='tooltip' data-container='body' title='"._('Gegenerate or regenerate a firewall addres object of this ip address.')."'><i class='fa fa-gray fa-repeat'></i></a>"; }}
					print "<a class='delete_ipaddress btn btn-xs btn-default modIPaddr' data-action='delete' data-subnetId='".$addresses[$n]->subnetId."' data-id='".$addresses[$n]->id."' href='#' id2='".$Subnets->transform_to_dotted($addresses[$n]->ip_addr)."'>		<i class='fa fa-gray fa-times'>  </i></a>";
				}
			}
			# write not permitted
			else {
				if($addresses[$n]->class=="compressed-range")
				{
					print "<a class='edit_ipaddress   btn btn-xs btn-default disabled' rel='tooltip' data-container='body' title='"._('Edit IP address details (disabled)')."'>	<i class='fa fa-gray fa-pencil'></i></a>";
					print "<a class='				   btn btn-xs btn-default disabled' href='#'>																					<i class='fa fa-gray fa-cogs'></i></a>";
					print "<a class='				   btn btn-xs btn-default disabled' href='#'>																					<i class='fa fa-gray fa-search'></i></a>";
					print "<a class='				   btn btn-xs btn-default disabled' href='#'>																					<i class='fa fa-gray fa-envelope-o'></i></a>";
					print "<a class='delete_ipaddress btn btn-xs btn-default disabled' rel='tooltip' data-container='body' title='"._('Delete IP address (disabled)')."'>			<i class='fa fa-gray fa-times'></i></a>";
				}
				else
				{
					print "<a class='edit_ipaddress   btn btn-xs btn-default disabled' rel='tooltip' data-container='body' title='"._('Edit IP address details (disabled)')."'>													<i class='fa fa-gray fa-pencil'></i></a>";
					print "<a class='				   btn btn-xs btn-default disabled'  data-id='".$addresses[$n]->id."' href='#' rel='tooltip' data-container='body' title='"._('Check availability')."'>					<i class='fa fa-gray fa-cogs'></i></a>";
					print "<a class='search_ipaddress btn btn-xs btn-default         "; if(strlen($resolve['name']) == 0) { print "disabled"; } print "' href='".create_link("tools","search","on","off","off",$resolve['name'])."' "; if(strlen($resolve['name']) != 0) { print "rel='tooltip' data-container='body' title='"._('Search same hostnames in db')."'"; } print ">	<i class='fa fa-gray fa-search'></i></a>";
					print "<a class='mail_ipaddress   btn btn-xs btn-default          ' href='#' data-id='".$addresses[$n]->id."' rel='tooltip' data-container='body' title='"._('Send mail notification')."'>				<i class='fa fa-gray fa-envelope-o'></i></a>";
					print "<a class='delete_ipaddress btn btn-xs btn-default disabled' rel='tooltip' data-container='body' title='"._('Delete IP address (disabled)')."'>														<i class='fa fa-gray fa-times'></i></a>";
				}
			}
			print "	</div>";
			print "</td>";

			print '</tr>'. "\n";

			/*	if last one return ip address and broadcast IP
			****************************************************/
			if ( $n == $m )
			{
				if($User->user->hideFreeRange!=1 && $subnet['isFull']!="1") {
					# compressed?
					if(isset($addresses[$n]->stopIP))	{ $unused = $Addresses->find_unused_addresses ( $addresses[$n]->stopIP,  $Subnets->transform_to_decimal($subnet_detailed['broadcast']), $subnet['mask'] ); }
					else 								{ $unused = $Addresses->find_unused_addresses ( $addresses[$n]->ip_addr, $Subnets->transform_to_decimal($subnet_detailed['broadcast']), $subnet['mask'] ); }

	            	if ( $unused  ) {
		        		print "<tr class='th'>";
		        		print "	<td></td>";
		        		print "	<td colspan='$colspan[unused]' class='unused'>$unused[ip] ($unused[hosts])</td>";
		        		print "</tr>";
	            	}
            	}
            }
        /* next IP address for free check */
        $n++;
        }
}
?>

</tbody>
</table>	<!-- end IP address table -->