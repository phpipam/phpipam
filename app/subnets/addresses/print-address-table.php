<script>
/* fix for ajax-loading tooltips */
$('body').tooltip({ selector: '[rel=tooltip]' });
</script>

<?php
/**
 * Print sorted IP addresses
 ***********************************************************************/

/**
 * Unset a value from an array if it exists
 *
 * @param   array  $array
 * @param   mixed  $value
 *
 * @return  void
 */
function unset_array_value(&$array, $value) {
	if (!is_array($array))
		return;

	$index = array_search($value, $array);
	if ($index === false)
		return;

	unset($array[$index]);
}

# direct call, set default direction for sorting
if(!isset($_POST['direction'])) {

	# verify that user is logged in
	$User->check_user_session();
}

# We need DNS object
$DNS = new DNS ($Database, $User->settings, true);

/* verifications */
# checks
if ($location!=="customers") {
if(sizeof($subnet)==0) 					{ $Result->show("danger", _('Subnet does not exist'), true); }									//subnet doesnt exist
if($subnet_permission == 0)				{ $Result->show("danger", _('You do not have permission to access this network'), true); }		//not allowed to access
if(!is_numeric($_GET['subnetId'])) 		{ $Result->show("danger", _('Invalid ID'), true); }												//subnet id must be numeric
}

/* selected and hidden fields */

# reset custom fields to ip addresses
$custom_fields = $Tools->fetch_custom_fields ('ipaddresses');
# set hidden custom fields
$hidden_cfields = json_decode($User->settings->hiddenCustomFields, true) ? : ['ipaddresses'=>null];
$hidden_cfields = is_array($hidden_cfields['ipaddresses']) ? $hidden_cfields['ipaddresses'] : array();

# set selected address fields array
$selected_ip_fields = $Tools->explode_filtered(";", $User->settings->IPfilter);  																	//format to array
// Remove state
unset_array_value($selected_ip_fields, 'state');
// if modules not enabled - remove
if($User->settings->enableFirewallZones != 1) { unset_array_value($selected_ip_fields, 'firewallAddressObject'); }
if($User->settings->enableLocations != 1)     { unset_array_value($selected_ip_fields, 'location'); }
if($User->settings->enableCustomers != 1)     { unset_array_value($selected_ip_fields, 'customer_id'); }

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
# remove port, owner, device, note, mac etc if none is set to preserve space
$cnt_obj = ["port"=>0, "switch"=>0, "owner"=>0, "note"=>0, "mac"=>0, "customer_id"=>0, "location"=>0, "firewallAddressObject"=>0];
foreach ($addresses as $a) {
	foreach($cnt_obj as $field => $c) {
		// Remove field from $cnt_obj if we find a match
		if (strlen($a->{$field})>0) { unset($cnt_obj[$field]); }
	}
}
// remove empty fields in $cnt_obj
foreach ($cnt_obj as $field=>$c)
	unset_array_value($selected_ip_fields, $field);

$selected_ip_fields = array_values($selected_ip_fields);  //Clean up array index

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

# set ping statuses for warning and offline
$statuses = explode(";", $User->settings->pingStatus);

# Set $zone
if(in_array('firewallAddressObject', $selected_ip_fields)) {
	# class
	if(!is_object($Zones)) $Zones = new FirewallZones ($Database);
	$zone = $Zones->get_zone_subnet_info($subnet['id']);
} else {
	$zone = false;
}
?>

<!-- print title and pagenum -->
<h4 style="margin-top:40px;">
<?php
if($location==="customers") {}
elseif(!$slaves)		{ print _("IP addresses in")." $location "; }
elseif(@$orphaned)	{ print "<div class='alert alert-warning alert-block'>"._('Orphaned IP addresses for subnet')." <strong>$subnet[description]</strong> (".sizeof($addresses)." orphaned) <br><span class='text-muted' style='font-size:12px;margin-top:10px;'>"._('This happens if subnet contained IP addresses when new child subnet was created')."'<span><hr><a class='btn btn-sm btn-default' id='truncate' href='' data-subnetid='".$subnet['id']."'><i class='fa fa-times'></i> "._("Remove all")."</a></div>"; }
else 				{ print _("IP addresses belonging to ALL nested subnets"); }
?>
</h4>

<!-- table -->
<table class="ipaddresses sortable sorted normalTable table table-condensed table-full table-top" data-cookie-id-table="ipaddresses">

<!-- headers -->
<thead>
<tr class="th">
	<?php
	print "<th class='s_ipaddr'>"._('IP address')."</th>";
	print "<th>"._('Hostname')."</th>";
	// firewall address object - mandatory if enabled
	if($zone) {
		print "<th>"._('FW object')."</th>";
	}
	// description
	print "<th>"._('Description')."</th>";
	// mac
	if(in_array('mac', $selected_ip_fields)) 	{
    	                                        { print "<th>"._('MAC')."</th>"; }
    }
	# note, device, port, owner, location
	if(in_array('note', $selected_ip_fields)) 	{ print "<th></th>"; }
	if(in_array('switch', $selected_ip_fields) && $User->get_module_permissions ("devices")>=User::ACCESS_R) { print "<th class='hidden-xs hidden-sm hidden-md'>"._('Device')."</th>"; }
	if(in_array('port', $selected_ip_fields)) 	{ print "<th class='hidden-xs hidden-sm hidden-md'>"._('Port')."</th>"; }
	if(in_array('location', $selected_ip_fields) && $User->get_module_permissions ("locations")>=User::ACCESS_R) 	{ print "<th class='hidden-xs hidden-sm hidden-md'>"._('Location')."</th>"; }
	if(in_array('owner', $selected_ip_fields)) 	{ print "<th class='hidden-xs hidden-sm'>"._('Owner')."</th>"; }
	if($User->settings->enableCustomers=="1" && $cnt_obj["customer_id"]>0 && $User->get_module_permissions ("customers")>=User::ACCESS_R)	{ print "<th class='hidden-xs hidden-sm'>"._('Customer')."</th>"; }
	// custom fields
	if(sizeof($custom_fields) > 0) {
		foreach($custom_fields as $myField) 	{
			print "<th class='hidden-xs hidden-sm hidden-md'>".$Tools->print_custom_field_name ($myField['name'])."</th>";
		}
	}
	?>
	<!-- actions -->
	<th class="actions"></th>
</tr>
</thead>

<tbody>
<?php

# set colspan for output
$colspan['empty']  = sizeof($selected_ip_fields) + sizeof($custom_fields) + 4;	//empty colspan
$colspan['unused'] = sizeof($selected_ip_fields) + sizeof($custom_fields) + 3;	//unused colspan
$colspan['dhcp']   = sizeof($selected_ip_fields) + sizeof($custom_fields) - in_array('firewallAddressObject', $selected_ip_fields);		//dhcp colspan

# if no IP is configured only display free subnet!
if ($addresses===false || sizeof($addresses)==0) {
	if($User->user->hideFreeRange!=1 && $subnet['isFull']!="1") {
    	$unused = $Subnets->find_unused_addresses ($subnet, false, false);
		print '<tr class="th"><td colspan="'.$colspan['empty'].'" class="unused">'.$unused['ip'].' (' .$Subnets->reformat_number($unused['hosts']).')</td></tr>'. "\n";
    }
    elseif ($subnet['isFull']=="1") {
		print '<tr class="th"><td colspan="'.$colspan['empty'].'" class="dhcp"><div class="alert alert-info"><i class="fa fa-info-circle"></i> '._(" Subnet is marked as full").'</div></td></tr>'. "\n";
    }
}
# print IP address
else {
	$n = 0;		//count for IP addresses - $n++ per IP address
	$m = sizeof($addresses) -1;		//last address index
	$g = 0;		//count for compress consecutive class

		foreach($addresses as $dummy) {

	       	#
	       	# first check for gaps from network to first host
	       	#

	       	# check gap between network address and first IP address
	       	if ( $n == 0) 											{ $unused = $Subnets->find_unused_addresses ($subnet, false, $addresses[$n]->ip_addr); }
	       	# check unused space between IP addresses
	       	else {
	       		// compressed and dhcp?
	       		if($addresses[$n-1]->class=="compressed-range") 	{ $unused = $Subnets->find_unused_addresses ($subnet, $addresses[$n-1]->stopIP, $addresses[$n]->ip_addr);  }
	       		// ignore /31 networks and /127
	       		elseif($subnet['mask']!=31 && $subnet['mask']!=127) { $unused = $Subnets->find_unused_addresses ($subnet, $addresses[$n-1]->ip_addr, $addresses[$n]->ip_addr);  }
	       	}

	       	# if there is some result for unused print it - if sort == ip_addr
	       	if($User->user->hideFreeRange!=1 && $subnet['isFull']!="1") {
			    if ( $unused) {
	        		print "<tr class='th1'>";
	        		print "	<td class='unused'></td>";
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
        		if($zone) {
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
				    elseif(is_null($addresses[$n]->lastSeen))   { $hStatus = "neutral"; $hTooltip = "rel='tooltip' data-container='body' data-html='true' data-placement='left' title='"._("Address was never online")."'"; }
				    elseif($tDiff < $statuses[0])	{ $hStatus = "success";	$hTooltip = "rel='tooltip' data-container='body' data-html='true' data-placement='left' title='"._("Address is alive")."<hr>"._("Last seen").": ".$addresses[$n]->lastSeen."'"; }
				    elseif($tDiff < $statuses[1])	{ $hStatus = "warning"; $hTooltip = "rel='tooltip' data-container='body' data-html='true' data-placement='left' title='"._("Address warning")."<hr>"._("Last seen").": ".$addresses[$n]->lastSeen."'"; }
				    elseif($tDiff > $statuses[1])	{ $hStatus = "error"; 	$hTooltip = "rel='tooltip' data-container='body' data-html='true' data-placement='left' title='"._("Address is offline")."<hr>"._("Last seen").": ".$addresses[$n]->lastSeen."'";}
				    elseif($addresses[$n]->lastSeen == "0000-00-00 00:00:00") { $hStatus = "neutral"; 	$hTooltip = "rel='tooltip' data-container='body' data-html='true' data-placement='left' title='"._("Address is offline")."<hr>"._("Last seen").": "._("Never")."'";}
				    elseif($addresses[$n]->lastSeen == "1970-01-01 00:00:01") { $hStatus = "neutral"; 	$hTooltip = "rel='tooltip' data-container='body' data-html='true' data-placement='left' title='"._("Address is offline")."<hr>"._("Last seen").": "._("Never")."'";}
				    else							{ $hStatus = "neutral"; $hTooltip = "rel='tooltip' data-container='body' data-html='true' data-placement='left' title='"._("Address status unknown")."'";}
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
					$records = $PowerDNS->search_records ("name", $addresses[$n]->hostname, 'name', true);
					$ptr	 = $PowerDNS->fetch_record ($addresses[$n]->PTR);
					$ptr_name = $PowerDNS->get_ip_ptr_name($Tools->transform_to_dotted($addresses[$n]->ip_addr));
					if(! $ptr || $ptr_name != $ptr->name) {
					        $ptr = $PowerDNS->search_records("name", $ptr_name);
					        if($ptr) {
					                $ptr = array_pop($ptr);
					                $Addresses->ptr_link($addresses[$n]->id, $ptr->id);
					        } else { $Addresses->ptr_link($addresses[$n]->id, 0); }
					}
					unset($dns_records);
					if (is_array($records) || $ptr!==false) {
						$dns_records[] = "<br>";
						$dns_records[] = "<ul class='submenu-dns text-muted'>";
						if(is_array($records)) {
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
					if (is_array($records2)) {
                        $dns_cname_unique = array();        // unique CNAME records to prevent multiple
                        unset($cname);
						$dns_records2[] = "<br>";
						$dns_records2[] = "<ul class='submenu-dns text-muted'>";
						foreach ($records2 as $r) {
							if($r->type!="SOA" && $r->type!="NS")
							$dns_records2[]   = "<li><i class='icon-gray fa fa-gray fa-angle-right'></i> <span class='badge badge1 badge2 editRecord' data-action='edit' data-id='$r->id' data-domain_id='$r->domain_id'>$r->type</span> $r->name </li>";
                            //search also for CNAME records
                            $dns_records_cname = $PowerDNS->seach_aliases ($r->name);
                            if(is_array($dns_records_cname)) {
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
				if ($Subnets->validate_hostname($addresses[$n]->hostname, false) && $User->check_module_permissions ("pdns", User::ACCESS_RWA, false, false))
				$button = "<i class='fa fa-plus-circle fa-gray fa-href editRecord' data-action='add' data-id='".$Addresses->transform_address($addresses[$n]->ip_addr, "dotted")."' data-domain_id='".$addresses[$n]->hostname."'></i>";
				else
				$button = "";
				}


			    // gateway
			    $gw = $addresses[$n]->is_gateway==1 ? "gateway" : "";

			    print "	<td class='ipaddress $gw'><span class='status status-$hStatus' $hTooltip></span><a href='".create_link("subnets",$subnet['sectionId'],$_GET['subnetId'],"address-details",$addresses[$n]->id)."'>".$Subnets->transform_to_dotted( $addresses[$n]->ip_addr)."</a>";
			    if($addresses[$n]->is_gateway==1)						{ print " <i class='fa fa-info-circle fa-gateway' rel='tooltip' title='"._('Address is marked as gateway')."'></i>"; }
			    print $Addresses->address_type_format_tag($addresses[$n]->state);

                # set subnet nat
                if($User->get_module_permissions ("nat")>=User::ACCESS_R) {
	                $Addresses->print_nat_link($all_nats, $all_nats_per_object, $subnet, $addresses[$n]);
	            }

			    print $dns_records2."</td>";

			    # resolve dns name
			    $resolve = $DNS->resolve_address($addresses[$n]->ip_addr, $addresses[$n]->hostname, false, $subnet['nameserverId']);
				# update database
				if($subnet['resolveDNS']=="1" && $resolve['class']=="resolved") {
					$Addresses->update_address_hostname ($addresses[$n]->ip_addr, $addresses[$n]->id, $resolve['name']);
					$addresses[$n]->hostname = $resolve['name'];
				}
																		{ print "<td class='$resolve[class] hostname'>$resolve[name] $button $dns_records</td>"; }

				# print firewall address object - mandatory if enabled
				if($zone) {
					                                                    { print "<td class='fwzone'>".$addresses[$n]->firewallAddressObject."</td>"; }
				}

				# print description - mandatory
	        													  		  print "<td class='description'>".$addresses[$n]->description."</td>";
				# Print mac address icon!
				if(in_array('mac', $selected_ip_fields)) {
                    # normalize MAC address
                	if(strlen(@$addresses[$n]->mac)>0) {
                    	if($User->validate_mac ($addresses[$n]->mac)!==false) {
                        	$addresses[$n]->mac = $User->reformat_mac_address ($addresses[$n]->mac, 1);
                    	}
                	}

            	    # multicast check
            	    if ($User->settings->enableMulticast==1 && $Subnets->is_multicast ($addresses[$n]->ip_addr)) {
                	    $mtest = $Subnets->validate_multicast_mac ($addresses[$n]->mac, $subnet['sectionId'], $subnet['vlanId'], MCUNIQUE, $addresses[$n]->id);
                	    // if duplicate
                	    if ($mtest !== true) {
                            // find duplicate
                            $duplicates = $Subnets->find_duplicate_multicast_mac ($addresses[$n]->id, $addresses[$n]->mac);

                            $mclass = "text-danger";
                            $minfo = "<i class='fa fa-exclamation-triangle' rel='tooltip' title='"._('Duplicate MAC')."'></i>";
                            // formulate object
                            if ($duplicates!==false) {
                                $mobjects = array();
                                $mobjects[] = "<hr><p class='muted' style='font-size:11px'>Duplicated addresses:</p>";
                                $mobjects[] = "<ul class='submenu-dns'> ";
                                foreach ($duplicates as $d) {
                                    $type = $d->isFolder==1 ? "folder" : "subnets";
                                    $mobjects[] = "<li><i class='icon-gray fa fa-gray fa-angle-right'></i><span style='color:#999'> $d->name / $d->description: </span> <a href='".create_link($type,$d->sectionId,$d->subnetId)."'>".$Subnets->transform_address($d->ip_addr, "dotted")."</a><br>";
                                }
                                $mobjects = implode("\n", $mobjects);
                            }
                            else {
                                $mobjects = "";
                            }
                	    }
                	    else {
                    	    $mclass = "";
                    	    $minfo = "";
                    	    $mobjects = "";
                	    }
                    }
                    // get MAC vendor
                    if($User->settings->decodeMAC=="1") {
	                    $mac_vendor = $User->get_mac_address_vendor_details ($addresses[$n]->mac);
	                    $mac_vendor = $mac_vendor==""||is_bool($mac_vendor) ? "" : "<hr>"._("Vendor").": ".$mac_vendor;
	                }
	                else {
	                	$mac_vendor = "";
	                }
					// multicast ?
					if ($User->settings->enableMulticast=="1" && $Subnets->is_multicast ($addresses[$n]->ip_addr))          { print "<td class='$mclass' style='white-space:nowrap;'>".$addresses[$n]->mac." $minfo $mobjects</td>"; }
					elseif(!empty($addresses[$n]->mac)) 				{ print "<td class='narrow'><i class='info fa fa-gray fa-sitemap' rel='tooltip' data-container='body' data-html='true' title='"._('MAC').": ".$addresses[$n]->mac.$mac_vendor."'></i></td>"; }
					else 												{ print "<td class='narrow'></td>"; }
				}


	       		# print info button for hover
	       		if(in_array('note', $selected_ip_fields)) {

	       			$addresses[$n]->note = str_replace("'", "&#39;", $addresses[$n]->note);

	        		if(!empty($addresses[$n]->note)) 					{ print "<td class='narrow'><i class='fa fa-gray fa-comment-o' rel='tooltip' data-container='body' data-html='true' title='".str_replace("\n", "<br>",addslashes($addresses[$n]->note))."'></i></td>"; }
	        		else 												{ print "<td class='narrow'></td>"; }
	        	}

	        	# print device
	        	if(in_array('switch', $selected_ip_fields) && $User->get_module_permissions ("devices")>=User::ACCESS_R) {
		        	# get device details
		        	$device = (array) $Tools->fetch_object("devices", "id", $addresses[$n]->switch);
		        	# set rack
		        	if ($User->settings->enableRACK=="1" && $User->get_module_permissions ("racks")>=User::ACCESS_RW) {
		        	$rack = $device['rack']>0 ? "<i class='btn btn-default btn-xs fa fa-server showRackPopup' data-rackid='$device[rack]' data-deviceid='$device[id]'></i>" : "";
																		  print "<td class='hidden-xs hidden-sm hidden-md'>$rack <a href='".create_link("tools","devices",@$device['id'])."'>". @$device['hostname'] ."</a></td>";
					}
					else {
						print "<td class='hidden-xs hidden-sm hidden-md'> <a href='".create_link("tools","devices",@$device['id'])."'>". @$device['hostname'] ."</a></td>";
					}
				}

				# print port
				if(in_array('port', $selected_ip_fields)) {
					print "<td class='hidden-xs hidden-sm hidden-md'>".$addresses[$n]->port."</td>";
				}

			    # print location
			    if(in_array('location', $selected_ip_fields) && $User->get_module_permissions ("locations")>=User::ACCESS_R) {
			    	$location_name = $Tools->fetch_object("locations", "id", $addresses[$n]->location);
			    	print "<td class='hidden-xs hidden-sm hidden-md'>".$location_name->name."</td>";
			    }

				# print owner
				if(in_array('owner', $selected_ip_fields)) {
					print "<td class='hidden-xs hidden-sm'>".$addresses[$n]->owner."</td>";
				}

				# customer_id
				if($User->settings->enableCustomers=="1" && $cnt_obj["customer_id"] && $User->get_module_permissions ("customers")>=User::ACCESS_R) {
					$customer = $Tools->fetch_object ("customers", "id", $addresses[$n]->customer_id);
					print $customer===false ? "<td></td>" : "<td>$customer->title <a target='_blank' href='".create_link("tools","customers",$customer->title)."'><i class='fa fa-external-link'></i></a></td>";
				}

				# print custom fields
				if(sizeof($custom_fields) > 0) {
					foreach($custom_fields as $myField) 					{
						if(!in_array($myField['name'], $hidden_cfields)) 	{
							print "<td class='customField hidden-xs hidden-sm hidden-md'>";

							// create html links
							$addresses[$n]->{$myField['name']} = $Tools->create_links($addresses[$n]->{$myField['name']}, $myField['type']);

							//booleans
							if($myField['type']=="tinyint(1)")	{
								if($addresses[$n]->{$myField['name']} == "0")		{ print _("No"); }
								elseif($addresses[$n]->{$myField['name']} == "1")	{ print _("Yes"); }
							}
							//text
							elseif($myField['type']=="text") {
								if(strlen($addresses[$n]->{$myField['name']})>0)	{ print "<i class='fa fa-gray fa-comment' rel='tooltip' data-container='body' data-html='true' title='".str_replace("\n", "<br>", $addresses[$n]->{$myField['name']})."'>"; }
								else											{ print ""; }
							}
							else {
								print $addresses[$n]->{$myField['name']};

							}
							print "</td>";
						}
					}
				}
		    }

			# print action links if user can edit
			print "<td class='btn-actions'>";
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
					print "<a class='search_ipaddress btn btn-xs btn-default         "; if(strlen($resolve['name']) == 0) { print "disabled"; } print "' href='".create_link("tools","search", $resolve['name'])."' "; if(strlen($resolve['name']) != 0)   { print "rel='tooltip' data-container='body' title='"._('Search same hostnames in db')."'"; } print ">	<i class='fa fa-gray fa-search'></i></a>";
					print "<a class='mail_ipaddress   btn btn-xs btn-default          ' href='#' data-id='".$addresses[$n]->id."' rel='tooltip' data-container='body' title='"._('Send mail notification')."'>																																		<i class='fa fa-gray fa-envelope-o'></i></a>";
					if($zone) { print "<a class='fw_autogen	   	  btn btn-default btn-xs          ' href='#' data-subnetid='".$addresses[$n]->subnetId."' data-action='adr' data-ipid='".$addresses[$n]->id."' data-dnsname='".$addresses[$n]->hostname."' rel='tooltip' data-container='body' title='"._('Generate or regenerate a firewall address object of this ip address.')."'><i class='fa fa-gray fa-repeat'></i></a>"; }
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
					print "<a class='search_ipaddress btn btn-xs btn-default         "; if(strlen($resolve['name']) == 0) { print "disabled"; } print "' href='".create_link("tools","search",$resolve['name'])."' "; if(strlen($resolve['name']) != 0) { print "rel='tooltip' data-container='body' title='"._('Search same hostnames in db')."'"; } print ">	<i class='fa fa-gray fa-search'></i></a>";
					print "<a class='mail_ipaddress   btn btn-xs btn-default          ' href='#' data-id='".$addresses[$n]->id."' rel='tooltip' data-container='body' title='"._('Send mail notification')."'>				<i class='fa fa-gray fa-envelope-o'></i></a>";
					print "<a class='delete_ipaddress btn btn-xs btn-default disabled' rel='tooltip' data-container='body' title='"._('Delete IP address (disabled)')."'>														<i class='fa fa-gray fa-times'></i></a>";
				}
			}
			print "	</div>";
			print "</td>";

			print '</tr>'. "\n";

			// now search for similar addresses if chosen
			if (strlen($User->settings->link_field)>0) {
    			// search
    			$similar = $Addresses->search_similar_addresses ($addresses[$n], $User->settings->link_field, $addresses[$n]->{$User->settings->link_field});

    			if($similar!==false) {
        			$link_field_print = $User->settings->link_field == "ip_addr" ? $Subnets->transform_to_dotted($addresses[$n]->{$User->settings->link_field}) : $addresses[$n]->{$User->settings->link_field};

        			print "<tr class='similar similar-title'>";
        			print " <td colspan='$colspan[unused]'>"._('Addresses linked with')." ".$User->settings->link_field." <strong>".$link_field_print."</strong>:</td>";
        			print "</tr>";

                    foreach ($similar as $k=>$s) {

                        $last = sizeof($similar)-1 == $k ? "similar-last" : "";

                        // fetch subnet
                        $sn = $Subnets->fetch_subnet("id", $s->subnetId);

        		 		print "<tr class='similar $last'>";
        			    print "	<td class='ipaddress'><i class='fa fa-angle-right'></i> <a href='".create_link("subnets", $sn->sectionId, $sn->id)."'>".$Subnets->transform_to_dotted( $s->ip_addr)."</a>";
        			    print $Addresses->address_type_format_tag($s->state);
        			    print "</td>";

        			    # resolve dns name
        			    $resolve = $DNS->resolve_address($s->ip_addr, $s->hostname, false, $sn->nameserverId);
        																		{ print "<td class='$resolve[class] hostname'>$resolve[name]</td>"; }
        				# print firewall address object - mandatory if enabled
        				if($zone) {
        					                                                    { print "<td class='fwzone'>".$s->firewallAddressObject."</td>"; }
        				}
        				# print description - mandatory
        	        													  		  print "<td class='description'>".$s->description."</td>";
        				# Print mac address icon!
        				if(in_array('mac', $selected_ip_fields)) {
                            if(!empty($s->mac)) 				                { print "<td class='narrow'><i class='info fa fa-gray fa-sitemap' rel='tooltip' data-container='body' title='"._('MAC').": ".$s->mac."'></i></td>"; }
        					else 												{ print "<td class='narrow'></td>"; }
        				}
        	       		# print info button for hover
        	       		if(in_array('note', $selected_ip_fields)) {
        	        		if(!empty($s->note)) 					            { print "<td class='narrow'><i class='fa fa-gray fa-comment-o' rel='tooltip' data-container='body' data-html='true' title='".str_replace("\n", "<br>",$s->note)."'></i></td>"; }
        	        		else 												{ print "<td class='narrow'></td>"; }
        	        	}
        	        	# print device
        	        	if(in_array('switch', $selected_ip_fields)) {
        		        	# get device details
        		        	$device = (array) $Tools->fetch_object("devices", "id", $s->switch);
        		        	# set rack
        		        	if ($User->settings->enableRACK=="1")
        		        	$rack = $device['rack']>0 ? "<i class='btn btn-default btn-xs fa fa-server showRackPopup' data-rackid='$device[rack]' data-deviceid='$device[id]'></i>" : "";
        																		  print "<td class='hidden-xs hidden-sm hidden-md'>$rack <a href='".create_link("tools","devices",@$device['id'])."'>". @$device['hostname'] ."</a></td>";
        				}
        				# print port
        				if(in_array('port', $selected_ip_fields)) 				{ print "<td class='hidden-xs hidden-sm hidden-md'>".$s->port."</td>"; }
        				# print location
						if(in_array('location', $selected_ip_fields) && $User->get_module_permissions ("locations")>=User::ACCESS_R) {
							$location_name = $Tools->fetch_object("locations", "id", $s->location);
							print "<td class='hidden-xs hidden-sm hidden-md'>".$location_name->name."</td>";
						}
			    		# print owner
        				if(in_array('owner', $selected_ip_fields)) 				{ print "<td class='hidden-xs hidden-sm'>".$s->owner."</td>"; }
        				# print custom fields
        				if(sizeof($custom_fields) > 0) {
        					foreach($custom_fields as $myField) {
        						if(!in_array($myField['name'], $hidden_cfields)) 	{
									print "<td class='customField hidden-xs hidden-sm hidden-md'>";
									$Tools->print_custom_field ($myField['type'], $addresses[$n]->{$myField['name']});
									print "</td>";
        						}
        				    }
                        }
        				# actions
        				print " <td></td>";
                        print "</tr>";


                    }
    			}
			}

			/*	if last one return ip address and broadcast IP
			****************************************************/
			if ( $n == $m )
			{
				if($User->user->hideFreeRange!=1 && $subnet['isFull']!="1") {
					# compressed?
					if(isset($addresses[$n]->stopIP))	{ $unused = $Subnets->find_unused_addresses ($subnet, $addresses[$n]->stopIP,  false); }
					else 								{ $unused = $Subnets->find_unused_addresses ($subnet, $addresses[$n]->ip_addr, false); }

	            	if ( $unused  ) {
		        		print "<tr>";
		        		print "	<td class='unused success'></td>";
		        		print "	<td colspan='$colspan[unused]' class='unused success'>$unused[ip] ($unused[hosts])</td>";
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
