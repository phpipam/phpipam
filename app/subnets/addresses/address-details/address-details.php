<?php

/**
 * Script to display IP address info and history
 ***********************************************/

# verify that user is logged in
$User->check_user_session();

# get subnet calculation
$subnet_detailed = $Subnets->get_network_boundaries ($subnet['subnet'], $subnet['mask']);           //set network boundaries
$gateway         = $Subnets->find_gateway($subnet['id']);
$gateway_ip      = $gateway===false ? "/" : $Subnets->transform_to_dotted($gateway->ip_addr);

# check if it exists, otherwise print error
if(sizeof($address)>1) {

    $address['description'] = str_replace("\n", "<br>", $address['description']);

    print "<table style='width:100%'>";
    print "<tr>";

    # device
    print "<td>";

	print "<table class='ipaddress_subnet table-condensed table-full'>";
	    print "<tr><td colspan='2'><h4>"._('General')."</h4></tr>";
    	# ip
    	print "<tr>";
    	print "	<th>"._('IP address')."</th>";
    	print "	<td><strong>$address[ip]</strong></td>";
    	print "</tr>";

        # mask
        print "<tr>";
        print " <th>"._('Netmask')."</th>";
        print " <td>$subnet_detailed[netmask] (/$subnet[mask])</td>";
        print "</tr>";

        # hierarchy
        print "<tr>";
        print " <th>"._('Hierarchy')."</th>";
        print " <td>";
        $Subnets->print_breadcrumbs ($Sections, $Subnets, $_GET, $Addresses);
        print "</td>";
        print "</tr>";

        # subnet
        print "<tr>";
        print " <th>"._('Subnet')."</th>";
        print " <td>$subnet[ip]/$subnet[mask] ($subnet[description])</td>";
        print "</tr>";

        # gateway
        print "<tr>";
        print " <th>"._('Gateway')."</th>";
        print " <td>$gateway_ip</td>";
        print "</tr>";

        # mac
        if(in_array('mac', $selected_ip_fields)) {
        print "<tr>";
        print " <th>"._('MAC address')."</th>";
        print " <td>$address[mac]</td>";
        print "</tr>";
        }

        # state
        print "<tr>";
        print " <th>"._('IP status')."</th>";
        print " <td>";

        if ($address['state'] == "0")     { $stateClass = _("Offline"); }
        else if ($address['state'] == "2") { $stateClass = _("Reserved"); }
        else if ($address['state'] == "3") { $stateClass = _("DHCP"); }
        else                          { $stateClass = _("Online"); }

        print $Addresses->address_type_index_to_type ($address['state']);
        print $Addresses->address_type_format_tag ($address['state']);

        print " </td>";
        print "</tr>";


        # divider
        print "<tr><td></td><td><hr></td></tr>";

    	# description
    	print "<tr>";
    	print "	<th>"._('Description')."</th>";
    	print "	<td>$address[description]</td>";
    	print "</tr>";

    	# hostname
    	$resolve1['name'] = strlen($resolve['name'])==0 ? "<span class='text-muted'>/</span>" : $resolve['name'];

    	print "<tr>";
    	print "	<th>"._('Hostname')."</th>";
    	print "	<td>$resolve1[name]</td>";
    	print "</tr>";

    	# firewall address object
    	if(in_array('firewallAddressObject', $selected_ip_fields)) {
    		if($User->settings->enableFirewallZones == 1) {
    			# class
    			$Zones = new FirewallZones ($Database);
    			$zone = $Zones->get_zone_subnet_info ($address['subnetId']);

    			if($zone) {
    				print "<tr>";
    				print "	<th>"._('Firewall address object')."</th>";
    				print "	<td>$address[firewallAddressObject]</td>";
    				print "</tr>";
    			}
    		}
    	}

        # customer
        if($User->settings->enableCustomers=="1" && $User->get_module_permissions ("customers")>=User::ACCESS_R) {
        $customer= $Tools->fetch_object ("customers", "id", $address['customer_id']);
        print "<tr>";
        print " <th>"._('Customer')."</th>";
        if($customer!==false)
        print " <td>$customer->title <a target='_blank' href='".create_link("tools","customers",$customer->title)."'><i class='fa fa-external-link'></i></a></td>";
        else
        print " <td>"._("None")."</td>";
        print "</tr>";
        }

    	# mac
    	if(in_array('owner', $selected_ip_fields)) {
    	print "<tr>";
    	print "	<th>"._('Owner')."</th>";
    	print "	<td>$address[owner]</td>";
    	print "</tr>";
    	}

    	# note
    	if(in_array('note', $selected_ip_fields)) {
    	print "<tr>";
    	print "	<th>"._('Note')."</th>";
    	print "	<td>$address[note]</td>";
    	print "</tr>";
    	}

    	# switch
    	if(in_array('switch', $selected_ip_fields) && $User->get_module_permissions ("devices")>=User::ACCESS_R) {
    	print "<tr>";
    	print "	<th>"._('Device')."</th>";
    	if(is_numeric($address['switch']) && $address['switch']>0) {
    		# get device
    		$device = (array) $Tools->fetch_object("devices", "id", $address['switch']);
    		$device = $Addresses->reformat_empty_array_fields($device, "");
    		print "	<td><a href='".create_link("tools","devices",$device['id'])."'>".@$device['hostname']."</a> ".@$device['description']."</td>";
    	} else {
    		print "	<td>$address[switch]</td>";
    	}
    	print "</tr>";
    	}

        # port
        if(in_array('port', $selected_ip_fields)) {
        print "<tr>";
        print " <th>"._('Port')."</th>";
        print " <td>$address[port]</td>";
        print "</tr>";
        }

    	if($User->settings->enableLocations=="1" && $User->get_module_permissions ("locations")>=User::ACCESS_R) { ?>
    	<tr>
    		<th><?php print _('Location'); ?></th>
    		<td>
    		<?php

    		// Only show nameservers if defined for subnet
    		if(!empty($address['location']) && $address['location']!=0) {
    			# fetch recursive nameserver details
    			$location2 = $Tools->fetch_object("locations", "id", $address['location']);
                if($location2!==false) {
                    print "<a href='".create_link("subnets", $subnet['sectionId'], $subnet['id'], "address-details", $address['id'], "location")."'>$location2->name</a>";
                }
    		}

    		else {
    			print "<span class='text-muted'>/</span>";
    		}
    		?>
    		</td>
    	</tr>
        <?php }

    	# last edited
    	print "<tr>";
    	print "	<th>"._('Last edited')."</th>";
    	if(strlen($address['editDate'])>1) {
    		print "	<td>$address[editDate]</td>";
    	} else {
    		print "	<td>"._('Never')."</td>";
    	}
    	print "</tr>";


    	# availability
        print "<tr><td colspan='2'><h4 style='padding-top:20px;'>"._('Availability')."</h4></tr>";
    	print "<tr>";

    	# calculate
    	$tDiff = time() - strtotime($address['lastSeen']);
    	if($address['excludePing']==1)		 					{ $seen_status = ""; 			$seen_text = ""; }
    	elseif($tDiff < $statuses[0])							{ $seen_status = "success";		$seen_text = _("Device is alive")."<br>"._("Last seen").": ".$address['lastSeen']; }
    	elseif($tDiff < $statuses[1])							{ $seen_status = "warning"; 	$seen_text = _("Device warning")."<br>"._("Last seen").": ".$address['lastSeen']; }
    	elseif($tDiff > $statuses[1])							{ $seen_status = "error"; 		$seen_text = _("Device is offline")."<br>"._("Last seen").": ".$address['lastSeen'];}
    	elseif($address['lastSeen'] == "0000-00-00 00:00:00") 	{ $seen_status = "neutral"; 	$seen_text = _("Device is offline")."<br>"._("Last seen").": "._("Never");}
    	elseif($address['lastSeen'] == "1970-01-01 00:00:01") 	{ $seen_status = "neutral"; 	$seen_text = _("Device is offline")."<br>"._("Last seen").": "._("Never");}
    	else													{ $seen_status = "neutral"; 	$seen_text = _("Device status unknown");}

    	print "	<th>"._('Availability')."<br><span class='status status-ip status-$seen_status' style='pull-right'></span></th>";
    	print "	<td>";
    	print "$seen_text";

    	print "	</td>";
    	print "</tr>";

    	# search for DNS records
    	if($User->settings->enablePowerDNS==1 && $subnet['DNSrecords']==1 ) {
    		$records = $PowerDNS->search_records ("name", $address['hostname'], 'name', true);
    		$ptr	 = $PowerDNS->fetch_record ($address['PTR']);
    		if ($records !== false || $ptr!==false) {

        	    print "<tr><td colspan='2'><h4 style='padding-top:20px;'>"._('DNS info')."</h4></tr>";
    			print "<tr>";
    			print "<th>"._('DNS records')."</th>";
    			print "<td>";
    			if($records!==false) {
    				foreach ($records as $r) {
    					print "<span class='badge badge1 badge3'>$r->type</span> $r->content <br>";
    				}
    			}
    			if($ptr!==false) {
    					print "<span class='badge badge1 badge3'>$ptr->type</span> $ptr->name <br>";
    			}
    			print "</td>";
    			print "</tr>";
    		}
    	}


    	# custom device fields
    	if(sizeof($custom_fields) > 0) {
        	print "<tr><td colspan='2'><h4 style='padding-top:20px;'>"._('Custom fields')."</h4></tr>";

    		foreach($custom_fields as $key=>$field) {
    			if(strlen($address[$key])>0) {
    			$address[$key] = str_replace(array("\n", "\r\n"), "<br>",$address[$key]);
    			print "<tr>";
    			print "	<th>$key</th>";
    			print "	<td>";
    			#booleans
    			if($field['type']=="tinyint(1)")	{
    				if($address[$key] == 0)		{ print _("No"); }
    				elseif($address[$key] == 1)	{ print _("Yes"); }
    			}
    			else {
    				print $Tools->create_links($address[$key]);
    			}
    			print "	</td>";
    			print "</tr>";
    			}
    		}
    	}

    	# check for temporary shares!
    	if($User->settings->tempShare==1) {
    		if (strlen($User->settings->tempAccess)>0) {
    			foreach(json_decode($User->settings->tempAccess) as $s) {
    				if($s->type=="ipaddresses" && $s->id==$address['id']) {
    					if(time()<$s->validity) {
    						$active_shares[] = $s;
    					}
    					else {
    						$expired_shares[] = $s;
    					}
    				}
    			}
    		}
    		if(isset($active_shares)) {
    			# divider
                print "<tr><td colspan='2'><h4 style='padding-top:20px;'>"._('Temporary shares')."</h4></tr>";

    			# print
    			print "<tr>";
    			print "<th>"._("Active shares").":</th>";
    			print "<td>";
    			$m=1;
    			foreach($active_shares as $s) {
    				print "<button class='btn btn-xs btn-default removeSharedTemp' data-code='$s->code' ><i class='fa fa-times'></i></button> <a href='".create_link("temp_share",$s->code)."'>Share $m</a> ("._("Expires")." ".date("Y-m-d H:i:s", $s->validity).")<br>";
    				$m++;
    			}
    			print "<td>";
    			print "</tr>";
    		}
    		if(isset($expired_shares)) {
    			# divider
    			print "<tr>";
    			print "	<th><hr></th>";
    			print "	<td></td>";
    			print "</tr>";
    			# print
    			print "<tr>";
    			print "<th>"._("Expired shares").":</th>";
    			print "<td>";
    			$m=1;
    			foreach($expired_shares as $s) {
    				print "<button class='btn btn-xs btn-danger removeSharedTemp' data-code='$s->code' ><i class='fa fa-times'></i></button> <a href='".create_link("temp_share",$s->code)."'>Share $m</a> ("._("Expired")." ".date("Y-m-d H:i:s", $s->validity).")<br>";
    				$m++;
    			}
    			print "<td>";
    			print "</tr>";
    		}
    	}


    	# actions
        print "<tr><td colspan='2'><h4 style='padding-top:20px;'>"._('Actions')."</h4></tr>";

    	print "<tr>";
    	print "	<th></th>";

    	print "<td class='btn-actions'>";
    	print "	<div class='btn-toolbar'>";
    	print "	<div class='btn-group'>";
    	# write permitted
    	if( $subnet_permission > 1) {
    		if(@$address['class']=="range-dhcp")
    		{
    			print "		<a class='edit_ipaddress   btn btn-default btn-xs modIPaddr' data-action='edit'   data-subnetId='".$address['subnetId']."' data-id='".$address['id']."' data-stopIP='".$address['stopIP']."' href='#' 		   rel='tooltip' data-container='body' title='"._('Edit IP address details')."'>	<i class='fa fa-gray fa-pencil'>  </i></a>";
    			print "		<a class='				   btn btn-default btn-xs disabled' href='#'>																																													<i class='fa fa-gray fa-cogs'> </i></a>";
    			print "		<a class='				   btn btn-default btn-xs disabled' href='#'>																																													<i class='fa fa-gray fa-search'></i></a>";
    			print "		<a class='				   btn btn-default btn-xs disabled' href='#'>																																													<i class='fa fa-gray fa-envelope-o'></i></a>";
    			print "		<a class='delete_ipaddress btn btn-default btn-xs modIPaddr' data-action='delete' data-subnetId='".$address['subnetId']."' data-id='".$address['id']."' href='#' id2='$address[ip]' rel='tooltip' data-container='body' title='"._('Delete IP address')."'>		<i class='fa fa-gray fa-times'>  </i></a>";
    		}
    		else
    		{
    			print "		<a class='edit_ipaddress   btn btn-default btn-xs modIPaddr' data-action='edit'   data-subnetId='".$address['subnetId']."' data-id='".$address['id']."' href='#' 											   rel='tooltip' data-container='body' title='"._('Edit IP address details')."'>				<i class='fa fa-gray fa-pencil'></i></a>";
    			print "		<a class='ping_ipaddress   btn btn-default btn-xs' data-subnetId='".$address['subnetId']."' data-id='".$address['id']."' href='#' 						   													rel='tooltip' data-container='body' title='"._('Check availability')."'>							<i class='fa fa-gray fa-cogs'></i></a>";
    			print "		<a class='search_ipaddress btn btn-default btn-xs         "; if(strlen($resolve['name']) == 0) { print "disabled"; } print "' href='".create_link("tools","search",$resolve['name'])."' "; if(strlen($resolve['name']) != 0)   { print "rel='tooltip' data-container='body' title='"._('Search same hostnames in db')."'"; } print ">	<i class='fa fa-gray fa-search'></i></a>";
    			print "		<a class='mail_ipaddress   btn btn-default btn-xs          ' href='#' data-id='".$address['id']."' rel='tooltip' data-container='body' title='"._('Send mail notification')."'>																																<i class='fa fa-gray fa-envelope-o'></i></a>";
    			if($zone) {
    			print "		<a class='fw_autogen	   btn btn-default btn-xs          ' href='#' data-subnetid='".$subnet['id']."' data-action='adr' data-ipid='".$address['id']."' data-dnsname='".((preg_match('/\//i',$address['hostname'])) ? '':$address['hostname'])."' rel='tooltip' data-container='body' title='"._('Regenerate firewall address object.')."'><i class='fa fa-gray fa-fire'></i></a>";
    			}
    			print "		<a class='delete_ipaddress btn btn-default btn-xs modIPaddr' data-action='delete' data-subnetId='".$address['subnetId']."' data-id='".$address['id']."' href='#' id2='$address[ip]' rel='tooltip' data-container='body' title='"._('Delete IP address')."'>													<i class='fa fa-gray fa-times'></i></a>";
    			//share
    			if($User->settings->tempShare==1) {
    			print "		<a class='btn btn-xs btn-default open_popup' data-script='app/tools/temp-shares/edit.php' data-class='700' data-action='edit' data-id='".$address['id']."' data-type='ipaddresses' data-container='body' rel='tooltip' title='' data-original-title='"._('Temporary share address')."'><i class='fa fa-share-alt'></i></a>";
    			}
    		}
    	}
    	# write not permitted
    	else {
    		if(@$address['class']=="range-dhcp")
    		{
    			print "		<a class='edit_ipaddress   btn btn-default btn-xs disabled' rel='tooltip' data-container='body' title='"._('Edit IP address details (disabled)')."'>	<i class='fa fa-gray fa-pencil'>  </i></a>";
    			print "		<a class='				   btn btn-default btn-xs disabled' href='#'>																<i class='fa fa-gray fa-retweet'> </i></a>";
    			print "		<a class='				   btn btn-default btn-xs disabled' href='#'>																<i class='fa fa-gray fa-search'></i></a>";
    			print "		<a class='				   btn btn-default btn-xs disabled' href='#'>																<i class='fa fa-gray fa-envelope'></i></a>";
    			print "		<a class='delete_ipaddress btn btn-default btn-xs disabled' rel='tooltip' data-container='body' title='"._('Delete IP address (disabled)')."'>			<i class='fa fa-gray fa-times'>  </i></a>";
    		}
    		else
    		{
    			print "		<a class='edit_ipaddress   btn btn-default btn-xs disabled' rel='tooltip' data-container='body' title='"._('Edit IP address details (disabled)')."'>							<i class='fa fa-gray fa-pencil'>  </i></a>";
    			print "		<a class='				   btn btn-default btn-xs disabled'  data-id='".$address['id']."' href='#' rel='tooltip' data-container='body' title='"._('Check availability')."'>		<i class='fa fa-gray fa-retweet'>  </i></a>";
    			print "		<a class='search_ipaddress btn btn-default btn-xs         "; if(strlen($resolve['name']) == 0) { print "disabled"; } print "' href='".create_link("tools","search",$resolve['name'])."' "; if(strlen($resolve['name']) != 0) { print "rel='tooltip' data-container='body' title='"._('Search same hostnames in db')."'"; } print ">	<i class='fa fa-gray fa-search'></i></a>";
    			print "		<a class='mail_ipaddress   btn btn-default btn-xs          ' href='#' data-id='".$address['id']."' rel='tooltip' data-container='body' title='"._('Send mail notification')."'>		<i class='fa fa-gray fa-envelope'></i></a>";
    			print "		<a class='delete_ipaddress btn btn-default btn-xs disabled' rel='tooltip' data-container='body' title='"._('Delete IP address (disabled)')."'>				<i class='fa fa-gray fa-times'>  </i></a>";
    		}
    	}

    	print "	</div>";
    	print "	</div>";
    	print "</td>";

    print "</table>";
    print "</td>";

	# rack
	if ($User->settings->enableRACK=="1" && isset($device['rack'])) {
        // validate rack
        $rack = $Tools->fetch_object ("racks", "id", $device['rack']);
        if (is_object($rack)) {
			print " <td style='width:200px;padding-right:20px;vertical-align:top !important;'>";
				# title
				print "<h4>"._('Rack details')."</h4>";
				print "<hr>";
				print "     <img src='".$Tools->create_rack_link ($device['rack'], $device['id'])."' class='pull-right' style='width:200px;'>";
			print " </td>";
			}
    }

    print "</table>";
}
# not exisitng
else {
	$Result->show("danger", _("IP address not existing in database")."!", true);
}
