<script type="text/javascript">
/* fix for ajax-loading tooltips */
$('body').tooltip({ selector: '[rel=tooltip]' });
</script>

<?php
/**
 * Print sorted IP addresses
 ***********************************************************************/

# We need DNS object
$DNS = new DNS ($Database, $User->settings);

# reset custom fields to ip addresses
$custom_fields = $Tools->fetch_custom_fields ('ipaddresses');
# set hidden custom fields
$hidden_cfields = json_decode($settings->hiddenCustomFields, true);
$hidden_cfields = is_array($hidden_cfields['ipaddresses']) ? $hidden_cfields['ipaddresses'] : array();

# set selected address fields array
$selected_ip_fields = $settings->IPfilter;
$selected_ip_fields = explode(";", $selected_ip_fields);																			//format to array
$selected_ip_fields_size = in_array('state', $selected_ip_fields) ? (sizeof($selected_ip_fields)-1) : sizeof($selected_ip_fields);	//set size of selected fields
if($selected_ip_fields_size==1 && strlen($selected_ip_fields[0])==0) { $selected_ip_fields_size = 0; }								//fix for 0


/* Addresses and fields manupulations */

# save for visual display !
$addresses_visual = $addresses;

# set colspan for output
$colspan['empty']  = $selected_ip_fields_size + sizeof($custom_fields) +4;		//empty colspan
$colspan['unused'] = $selected_ip_fields_size + sizeof($custom_fields) +3;		//unused colspan
$colspan['dhcp']   = $selected_ip_fields_size + sizeof($custom_fields);			//dhcp colspan

# remove custom fields if all are empty!
foreach($custom_fields as $field) {
	$sizeMyFields[$field['name']] = 0;				// default value
	# check against each IP address
	if($addresses!==false) {
		foreach($addresses as $ip) {
			$ip = (array) $ip;
			if(strlen($ip[$field['name']]) > 0) {
				$sizeMyFields[$field['name']]++;		// +1
			}
		}
		# unset if value == 0
		if($sizeMyFields[$field['name']] == 0) {
			unset($custom_fields[$field['name']]);

			$colspan['empty']--;
			$colspan['unused']--;						//unused  span -1
			$colspan['dhcp']--;							//dhcp span -1
		}
	}
}

/* output variables */

# set page limit for pagination
$page_limit = 100000000;
# set ping statuses for warning and offline
$statuses = explode(";", $settings->pingStatus);
?>

<!-- print title and pagenum -->
<h4 style="margin-top:40px;">
<?php
if(!$slaves)		{ print _("IP addresses in subnet "); }
else 				{ print _("IP addresses belonging to ALL nested subnets"); }
?>
</h4>


<!-- table -->
<table class="ipaddresses normalTable table table-striped table-condensed table-hover table-full table-top">

<!-- headers -->
<tbody>
<tr class="th">

	<?php

	# IP address - mandatory
												  print "<th class='s_ipaddr'>"._('IP address')."</th>";
	# hostname - mandatory
												  print "<th>"._('Hostname')."</th>";
	# Description - mandatory
												  print "<th>"._('Description')."</th>";
	# MAC address
	if(in_array('mac', $selected_ip_fields)) 	{ print "<th></th>"; }
	# note
	if(in_array('note', $selected_ip_fields)) 	{ print "<th></th>"; }
	# switch
	if(in_array('switch', $selected_ip_fields)) { print "<th class='hidden-xs hidden-sm hidden-md'>"._('Device')."</th>"; }
	# port
	if(in_array('port', $selected_ip_fields)) 	{ print "<th class='hidden-xs hidden-sm hidden-md'>"._('Port')."</th>"; }
	# owner
	if(in_array('owner', $selected_ip_fields)) 	{ print "<th class='hidden-xs hidden-sm hidden-md'>"._('Owner')."</th>"; }

	# custom fields
	if(sizeof($custom_fields) > 0) {
		foreach($custom_fields as $myField) 	{
			if(!in_array($myField['name'], $hidden_cfields)) {
				print "<th class='hidden-xs hidden-sm hidden-md'>$myField[name]</th>";
			}
		}
	}
	?>
</tr>
</tbody>


<?php
/* Addresses content print */
$n = 0;							//addresses index
$m = sizeof($addresses) -1;		//last address index

# if no IP is configured only display free subnet!
if ($addresses===false || sizeof($addresses)==0) {
   	$unused = $Addresses->find_unused_addresses($Subnets->transform_to_decimal($subnet_detailed['network']), $Subnets->transform_to_decimal($subnet_detailed['broadcast']), $subnet['mask'], $empty=true );
	print '<tr class="th"><td colspan="'.$colspan['empty'].'" class="unused">'.$unused['ip'].' (' .$Subnets->reformat_number($unused['hosts']).')</td></tr>'. "\n";
}
# print IP address
else {
	foreach($addresses as $dummy) {
       	#
       	# first check for gaps from network to first host
       	#

       	# check gap between network address and first IP address
       	if ( $n == 0 ) 																	{ $unused = $Addresses->find_unused_addresses ( $Subnets->transform_to_decimal($subnet_detailed['network']), $addresses[$n]->ip_addr, $subnet['mask']); }
       	# check unused space between IP addresses
       	else 																			{ $unused = $Addresses->find_unused_addresses ( $addresses[$n-1]->ip_addr, $addresses[$n]->ip_addr, $subnet['mask'] );  }

       	# if there is some result for unused print it - if sort == ip_addr
	    if ( $unused ) {
    		print "<tr class='th'>";
    		print "	<td></td>";
    		print "	<td colspan='$colspan[unused]' class='unused'>$unused[ip] ($unused[hosts])</td>";
    		print "</tr>";
    	}

		#
	    # print IP address
	    #

	    # ip - range
	    if($addresses[$n]->class=="range-dhcp")
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
			print "	<td>"._("DHCP range")."</td>";
    		print "	<td>".$addresses[$n]->description."</td>";
    		if($colspan['dhcp']!=0)
    		print "	<td colspan='$colspan[dhcp]' class='unused'></td>";
		    // tr ends after!

	    }
	    # ip - normal
	    else
	    {
	        /*	set class for reserved and offline - if set! */
		    $stateClass = "";
	        if(in_array('state', $selected_ip_fields)) {
		        if ($addresses[$n]->state == 0) 	 	{ $stateClass = _("Offline"); }
		        else if ($addresses[$n]->state == 2) 	{ $stateClass = _("Reserved"); }
		        else if ($addresses[$n]->state == 3) 	{ $stateClass = _("DHCP"); }
		    }

	 		print "<tr class='$stateClass'>";

		    // gateway
		    $gw = $addresses[$n]->is_gateway==1 ? "gateway" : "";

		    print "	<td class='ipaddress $gw'><a href='".create_link("temp_share",$_GET['section'],$addresses[$n]->id)."'>".$Subnets->transform_to_dotted( $addresses[$n]->ip_addr)."</a>";
		    if($addresses[$n]->is_gateway==1)						{ print " <i class='fa fa-info-circle fa-gateway' rel='tooltip' title='"._('Address is marked as gateway')."'></i>"; }
		    if(in_array('state', $selected_ip_fields)) 				{ print $Addresses->address_type_format_tag($addresses[$n]->state); }
		    print "</td>";

		    # resolve dns name
		    $resolve = $DNS->resolve_address($addresses[$n]->ip_addr, $addresses[$n]->dns_name, false, $subnet['nameserverId']);
																	{ print "<td class='$resolve[class] hostname'>$resolve[name]</td>"; }

			# print description - mandatory
        													  		  print "<td class='description'>".$addresses[$n]->description."</td>";
			# Print mac address icon!
			if(in_array('mac', $selected_ip_fields)) {
				if(!empty($addresses[$n]->mac)) 					{ print "<td class='narrow'><i class='info fa fa-gray fa-sitemap' rel='tooltip' data-container='body' title='"._('MAC').": ".$addresses[$n]->mac."'></i></td>"; }
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
	        	$device = (array) $Tools->fetch_object("devices", "id", $addresses[$n]->switch);
																	  print "<td class='hidden-xs hidden-sm hidden-md'>".@$device['hostname']."</td>";
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

						//booleans
						if($myField['type']=="tinyint(1)")	{
							if($addresses[$n]->{$myField['name']} == "0")		{ print _("No"); }
							elseif($addresses[$n]->{$myField['name']} == "1")	{ print _("Yes"); }
						}
						//text
						elseif($myField['type']=="text") {
							if(strlen($addresses[$n]->{$myField['name']})>0)	{ print "<i class='fa fa-gray fa-comment' rel='tooltip' data-container='body' data-html='true' title='".str_replace("\n", "<br>", $addresses[$n][$myField['name']])."'>"; }
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
		print '</tr>'. "\n";

		/*	if last one return ip address and broadcast IP
		****************************************************/
		if ( $n == $m )
		{
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
        /* next IP address for free check */
        $n++;
	}
}
?>

</table>	<!-- end IP address table -->