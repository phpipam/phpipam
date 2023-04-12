<?php

/**
 * print subnets
 */

# verify that user is logged in
$User->check_user_session();

# get all multicast subnets
$subnets = $Subnets->fetch_multicast_subnets();

# get custom fields
$custom_fields = $Tools->fetch_custom_fields('subnets');

# set hidden fields
$hidden_cfields = json_decode($User->settings->hiddenCustomFields, true) ? : ['subnets'=>null];
$hidden_cfields = is_array($hidden_cfields['subnets']) ? $hidden_cfields['subnets'] : array();

# set selected address fields array
$selected_ip_fields = $Tools->explode_filtered(";", $User->settings->IPfilter);  																	//format to array
// if fw not set remove!
unset($selected_ip_fields['firewallAddressObject']);

// set size
$selected_ip_fields_size = in_array('state', $selected_ip_fields) ? sizeof($selected_ip_fields)-1 : sizeof($selected_ip_fields);	//set size of selected fields
if($selected_ip_fields_size==1 && strlen($selected_ip_fields[0])==0) { $selected_ip_fields_size = 0; }								//fix for 0

// colspan
$colSpan  = $selected_ip_fields_size + sizeof($custom_fields) + 3;		//empty colspan


# title
print "<h4>"._('Multicast networks')."</h4>";
print "<hr>";

# table
print "<table class='ipaddresses multicast sorted table table-condensed table-full table-top'>";

$subnet_count=0;
# print multicast subnets
if ($subnets!==false) {

	# headers
    print "<thead>";
    print " <tr class='th1'>";

    	# IP address - mandatory
    												  print "<th>"._('IP address')."</th>";
    	# hostname - mandatory
    												  print "<th>"._('Hostname')."</th>";
    	# Description - mandatory
    												  print "<th>"._('Description')."</th>";
    	# MAC address
    	if(in_array('mac', $selected_ip_fields)) 	{ print "<th>"._('MAC address')."</th>"; }
    	# note
    	if(in_array('note', $selected_ip_fields)) 	{ print "<th></th>"; }
    	# switch
    	if(in_array('switch', $selected_ip_fields)) { print "<th class='hidden-xs hidden-sm hidden-md'>"._('Device')."</th>"; }
    	# port
    	if(in_array('port', $selected_ip_fields)) 	{ print "<th class='hidden-xs hidden-sm hidden-md'>"._('Port')."</th>"; }
    	# owner
    	if(in_array('owner', $selected_ip_fields)) 	{ print "<th class='hidden-xs hidden-sm'>"._('Owner')."</th>"; }

    	# custom fields
    	if(sizeof($custom_fields) > 0) {
    		foreach($custom_fields as $myField) 	{
    			print "<th class='hidden-xs hidden-sm hidden-md'>".$Tools->print_custom_field_name ($myField['name'])."</th>";
    		}
    	}
    	# actions
    	print '<th class="actions"></th>';
        print '</tr>';
    print '</thead>';


    print "<tbody>";
    //loop
	foreach ($subnets as $subnet) {
		# check permission
		$permission = $Subnets->check_permission ($User->user, $subnet->id);
		//if it has slaves dont print it, slaves will be printed automatically
		if($permission > 0 && ($Subnets->has_slaves($subnet->id)===false || $subnet->isFolder=="1")) {
    		// add to count
    		$subnet_count++;

        	# set values for permissions
        	if($permission == 1) {
        		$sp['addip'] 	 = false;		//add ip address
        	}
        	else if ($permission == 2) {
        		$sp['addip'] 	 = true;		//add ip address
        	}
        	else if ($permission == 3) {
        		$sp['addip'] 	 = true;		//add ip address
        	}

            # calculate usage
        	$addresses = $Addresses->fetch_subnet_addresses ($subnet->id);
        	// save count
        	$addresses_cnt = gmp_strval(sizeof($addresses));
        	$subnet_usage  = $Subnets->calculate_subnet_usage ($subnet);		//Calculate free/used etc

            // description
            if($subnet->isFolder=="1")
            $subnet->description = strlen($subnet->description)>0 ? $subnet->description : "";
            else
            $subnet->description = strlen($subnet->description)>0 ? "[".$subnet->description."]" : "";

			# section names
			print "	<tr class='subnets-title'>";
			print "		<td colspan='$colSpan' style='padding-top:20px;'>";
			if($subnet->isFolder=="1")
			print "     <h4><i class='fa fa-folder-o fa-gray'></i> $subnet->description</h4>";
			else
			print "     <h4>".$Subnets->transform_address($subnet->subnet, "dotted")."/$subnet->mask $subnet->description</h4>";

			print " </td>";
			print "</tr>";

            # print addresses
			# no subnets
			if(sizeof($addresses) == 0) {
				print "<tr><td colspan='$colSpan'>";
				$Result->show("info", _('Subnets has no hosts')."!", false);
				print "</td></tr>";
			}
			else {
    			foreach ($addresses as $address) {
			 		print "<tr>";

				    # status icon
				    if($subnet->pingSubnet=="1") {
					    //calculate
					    $tDiff = time() - strtotime($address->lastSeen);
					    if($address->excludePing=="1" ) { $hStatus = "padded"; $hTooltip = ""; }
					    elseif($tDiff < $statuses[0])	{ $hStatus = "success";	$hTooltip = "rel='tooltip' data-container='body' data-html='true' data-placement='left' title='"._("Device is alive")."<hr>"._("Last seen").": ".$address->lastSeen."'"; }
					    elseif($tDiff < $statuses[1])	{ $hStatus = "warning"; $hTooltip = "rel='tooltip' data-container='body' data-html='true' data-placement='left' title='"._("Device warning")."<hr>"._("Last seen").": ".$address->lastSeen."'"; }
					    elseif($tDiff < 2592000)		{ $hStatus = "error"; 	$hTooltip = "rel='tooltip' data-container='body' data-html='true' data-placement='left' title='"._("Device is offline")."<hr>"._("Last seen").": ".$address->lastSeen."'";}
					    elseif($address->lastSeen == "0000-00-00 00:00:00") { $hStatus = "neutral"; 	$hTooltip = "rel='tooltip' data-container='body' data-html='true' data-placement='left' title='"._("Device is offline")."<hr>"._("Last seen").": "._("Never")."'";}
					    elseif($address->lastSeen == "1970-01-01 00:00:01") { $hStatus = "neutral"; 	$hTooltip = "rel='tooltip' data-container='body' data-html='true' data-placement='left' title='"._("Device is offline")."<hr>"._("Last seen").": "._("Never")."'";}
					    else							{ $hStatus = "neutral"; $hTooltip = "rel='tooltip' data-container='body' data-html='true' data-placement='left' title='"._("Device status unknown")."'";}
				    }
				    else {
					    $hStatus = "hidden";
					    $hTooltip = "";
				    }

				    print "	<td class='ipaddress'><span class='status status-$hStatus' $hTooltip></span><a href='".create_link("subnets",$subnet->sectionId,$subnet->id,"address-details",$address->id)."'>".$Subnets->transform_to_dotted( $address->ip_addr)."</a>";
				    print $Addresses->address_type_format_tag($address->state);
				    print "</td>";

				    # resolve dns name
																	{ print "<td class='hostname'>$address->hostname</td>"; }

					# print description - mandatory
		        													{ print "<td class='description'>".$address->description."</td>"; }
					# Print mac address icon!
					if(in_array('mac', $selected_ip_fields)) {
                	    # multicast check
                	    if ($User->settings->enableMulticast==1 && $Subnets->is_multicast ($address->ip_addr)) {
                    	    $mtest = $Subnets->validate_multicast_mac ($address->mac, $subnet->sectionId, $subnet->vlanId, MCUNIQUE, $address->id);
                            // if duplicate
                    	    if ($mtest !== true) {
                                // find duplicate
                                $duplicates = $Subnets->find_duplicate_multicast_mac ($address->id, $address->mac);

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
    					// multicast ?
    					if ($User->settings->enableMulticast=="1" && $Subnets->is_multicast ($address->ip_addr))          { print "<td class='$mclass' style='white-space:nowrap;'>".$address->mac." $minfo $mobjects</td>"; }
						elseif(!empty($address->mac)) 				{ print "<td class='narrow'><i class='info fa fa-gray fa-sitemap' rel='tooltip' data-container='body' title='"._('MAC').": ".$address->mac."'></i></td>"; }
						else 												{ print "<td class='narrow'></td>"; }
					}


		       		# print info button for hover
		       		if(in_array('note', $selected_ip_fields)) {
		        		if(!empty($address->note)) 					{ print "<td class='narrow'><i class='fa fa-gray fa-comment-o' rel='tooltip' data-container='body' data-html='true' title='".str_replace("\n", "<br>",$address->note)."'></i></td>"; }
		        		else 										{ print "<td class='narrow'></td>"; }
		        	}

		        	# print device
		        	if(in_array('switch', $selected_ip_fields)) {
			        	# get device details
			        	$device = (array) $Tools->fetch_object("devices", "id", $address->switch);
																	 { print "<td class='hidden-xs hidden-sm hidden-md'><a href='".create_link("tools","devices",@$device['id'])."'>". @$device['hostname'] ."</a></td>"; }
					}

					# print port
					if(in_array('port', $selected_ip_fields)) 		 { print "<td class='hidden-xs hidden-sm hidden-md'>".$address->port."</td>"; }

					# print owner
					if(in_array('owner', $selected_ip_fields)) 		{ print "<td class='hidden-xs hidden-sm'>".$address->owner."</td>"; }

					# print custom fields
					if(sizeof($custom_fields) > 0) {
						foreach($custom_fields as $myField) 					{
							if(!in_array($myField['name'], $hidden_cfields)) 	{
								print "<td class='customField hidden-xs hidden-sm hidden-md'>";

								// create html links
								$address->{$myField['name']} = $Tools->create_links($address->{$myField['name']}, $myField['type']);

								//booleans
								if($myField['type']=="tinyint(1)")	{
									if($address->{$myField['name']} == "0")		{ print _("No"); }
									elseif($address->{$myField['name']} == "1")	{ print _("Yes"); }
								}
								//text
								elseif($myField['type']=="text") {
									if(strlen($address->{$myField['name']})>0)	{ print "<i class='fa fa-gray fa-comment' rel='tooltip' data-container='body' data-html='true' title='".str_replace("\n", "<br>", $address->{$myField['name']})."'>"; }
									else											{ print ""; }
								}
								else {
									print $address->{$myField['name']};

								}
								print "</td>";
							}
						}
					}

    				# print action links if user can edit
    				print "<td class='btn-actions'>";
    				print "	<div class='btn-group'>";
    				# write permitted
    				if( $permission > 1) {
						print "<a class='edit_ipaddress   btn btn-xs btn-default modIPaddr' data-action='edit'   data-subnetId='".$address->subnetId."' data-id='".$address->id."' href='#' >															<i class='fa fa-gray fa-pencil'></i></a>";
						print "<a class='ping_ipaddress   btn btn-xs btn-default' data-subnetId='".$address->subnetId."' data-id='".$address->id."' href='#' rel='tooltip' data-container='body' title='"._('Check availability')."'>					<i class='fa fa-gray fa-cogs'></i></a>";
						print "<a class='search_ipaddress btn btn-xs btn-default         "; if(strlen($resolve['name']) == 0) { print "disabled"; } print "' href='".create_link("tools","search",$resolve['name'])."' "; if(strlen($resolve['name']) != 0)   { print "rel='tooltip' data-container='body' title='"._('Search same hostnames in db')."'"; } print ">	<i class='fa fa-gray fa-search'></i></a>";
						print "<a class='mail_ipaddress   btn btn-xs btn-default          ' href='#' data-id='".$address->id."' rel='tooltip' data-container='body' title='"._('Send mail notification')."'>																																		<i class='fa fa-gray fa-envelope-o'></i></a>";
						if(in_array('firewallAddressObject', $selected_ip_fields)) { if($zone) { print "<a class='fw_autogen	   	  btn btn-default btn-xs          ' href='#' data-subnetid='".$address->subnetId."' data-action='adr' data-ipid='".$address->id."' data-dnsname='".$address->hostname."' rel='tooltip' data-container='body' title='"._('Generate or regenerate a firewall address object of this ip address.')."'><i class='fa fa-gray fa-repeat'></i></a>"; }}
						print "<a class='delete_ipaddress btn btn-xs btn-default modIPaddr' data-action='delete' data-subnetId='".$address->subnetId."' data-id='".$address->id."' href='#' id2='".$Subnets->transform_to_dotted($address->ip_addr)."'>		<i class='fa fa-gray fa-times'>  </i></a>";
    				}
    				# write not permitted
    				else {
						print "<a class='edit_ipaddress   btn btn-xs btn-default disabled' rel='tooltip' data-container='body' title='"._('Edit IP address details (disabled)')."'>													<i class='fa fa-gray fa-pencil'></i></a>";
						print "<a class='				   btn btn-xs btn-default disabled'  data-id='".$address->id."' href='#' rel='tooltip' data-container='body' title='"._('Check availability')."'>					<i class='fa fa-gray fa-cogs'></i></a>";
						print "<a class='search_ipaddress btn btn-xs btn-default         "; if(strlen($resolve['name']) == 0) { print "disabled"; } print "' href='".create_link("tools","search",$resolve['name'])."' "; if(strlen($resolve['name']) != 0) { print "rel='tooltip' data-container='body' title='"._('Search same hostnames in db')."'"; } print ">	<i class='fa fa-gray fa-search'></i></a>";
						print "<a class='mail_ipaddress   btn btn-xs btn-default          ' href='#' data-id='".$address->id."' rel='tooltip' data-container='body' title='"._('Send mail notification')."'>				<i class='fa fa-gray fa-envelope-o'></i></a>";
						print "<a class='delete_ipaddress btn btn-xs btn-default disabled' rel='tooltip' data-container='body' title='"._('Delete IP address (disabled)')."'>														<i class='fa fa-gray fa-times'></i></a>";
    				}

    				print "	</div>";
    				print "</td>";

    				print '</tr>'. "\n";
                }
            }
            print "<tr>";
            print " <td colspan='$colSpan'>";
            // if full prevent new
    		if($subnet_usage['freehosts']>0 && $sp['addip'])
    		print "     <a class='modIPaddr btn btn-sm btn-default pull-right' 	href='' data-subnetId='$subnet->id' data-action='add' data-id=''>	<i class='fa fa-plus'> "._("Add new address")."</i></a> ";
            print " <span class='clearfix'></span><hr>";
            print " </td>";
            print "</tr>";
        }
    }
    print "</tbody>";
}

# none
if ($subnet_count===0) {
	print "<tr><td colspan='$colSpan'>".$Result->show("info", _("No multicast subnets available"), false)."</td></tr>";
}

?>
</table>