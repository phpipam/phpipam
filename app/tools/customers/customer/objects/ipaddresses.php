<h4><?php print _("IP addresses"); ?></h4>
<hr>
<span class="text-muted"><?php print _("All IP addresses belonging to customer"); ?>.</span>

<script type="text/javascript">
/* fix for ajax-loading tooltips */
$('body').tooltip({ selector: '[rel=tooltip]' });
</script>




<?php
/**
 * Print IP addresses
 *******************/

# set addresses
$addresses = $objects['ipaddresses'];

# only if set
if (isset($objects["subnets"])) {

# reset custom fields to ip addresses
$custom_fields = $Tools->fetch_custom_fields ('ipaddresses');
# set hidden custom fields
$hidden_cfields = json_decode($User->settings->hiddenCustomFields, true);
$hidden_cfields = is_array($hidden_cfields['ipaddresses']) ? $hidden_cfields['ipaddresses'] : array();

# set selected address fields array
$selected_ip_fields = explode(";", $User->settings->IPfilter);  																	//format to array

// set size
$selected_ip_fields_size = in_array('state', $selected_ip_fields) ? sizeof($selected_ip_fields)-1 : sizeof($selected_ip_fields);	//set size of selected fields
if($selected_ip_fields_size==1 && strlen($selected_ip_fields[0])==0) { $selected_ip_fields_size = 0; }								//fix for 0

# set ping statuses for warning and offline
$statuses = explode(";", $User->settings->pingStatus);
?>

<!-- table -->
<table class="ipaddresses sortable sorted normalTable table table-condensed table-full table-top" data-cookie-id-table="ipaddresses_customers">

<!-- headers -->
<thead>
<tr class="th">
	<?php
	print "<th class='s_ipaddr'>"._('IP address')."</th>";
	print "<th class='s_ipaddr'>"._('Subnet')."</th>";
	print "<th>"._('Hostname')."</th>";
	print "<th>"._('Description')."</th>";
	if(in_array('mac', $selected_ip_fields))
	print "<th>"._('MAC')."</th>";
	if(in_array('note', $selected_ip_fields)) 	{ print "<th></th>"; }
	if(in_array('switch', $selected_ip_fields)) { print "<th class='hidden-xs hidden-sm hidden-md'>"._('Device')."</th>"; }
	if(in_array('port', $selected_ip_fields)) 	{ print "<th class='hidden-xs hidden-sm hidden-md'>"._('Port')."</th>"; }
	if(in_array('owner', $selected_ip_fields)) 	{ print "<th class='hidden-xs hidden-sm'>"._('Owner')."</th>"; }
	?>
	<!-- actions -->
	<th class="actions"></th>
</tr>
</thead>

<tbody>
<?php

/* Addresses content print */
$n = 0;		//count for IP addresses - $n++ per IP address

// print
foreach($addresses as $dummy) {

	// get permission and subnet
	$subnet = (array) $Tools->fetch_object ("subnets", "id", $dummy->subnetId);
	$subnet_permission  = $Subnets->check_permission($User->user, $dummy->subnetId);						//subnet permission


	print "<tr>";

    # status icon
    if($subnet['pingSubnet']=="1") {
	    //calculate
	    $tDiff = time() - strtotime($addresses[$n]->lastSeen);
	    if($addresses[$n]->excludePing=="1" ) { $hStatus = "padded"; $hTooltip = ""; }
	    if(is_null($addresses[$n]->lastSeen))   { $hStatus = "neutral"; $hTooltip = "rel='tooltip' data-container='body' data-html='true' data-placement='left' title='"._("Address was never online")."'"; }
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

    // gateway
    $gw = $addresses[$n]->is_gateway==1 ? "gateway" : "";

    // ip address
    print "	<td class='ipaddress $gw'><span class='status status-$hStatus' $hTooltip></span><a href='".create_link("subnets",$subnet['sectionId'],$dummy->subnetId,"address-details",$dummy->id)."'>".$Subnets->transform_to_dotted( $addresses[$n]->ip_addr)."</a>";
    if($addresses[$n]->is_gateway==1)						{ print " <i class='fa fa-info-circle fa-gateway' rel='tooltip' title='"._('Address is marked as gateway')."'></i>"; }
    print $Addresses->address_type_format_tag($addresses[$n]->state);
    print "	</td>";

    // subnet
    print "	<td><a href='".create_link("subnets", $subnet['sectionId'], $subnet['id'])."' rel='tooltip' title='".$subnet['description']."'>".$Tools->transform_to_dotted($subnet['subnet'])."/".$subnet['mask']."</a></td>";

    // hostname
	print "<td class='hostname'>{$addresses[$n]->dns_name}</td>";

	// print description - mandatory
	print "<td class='description'>".$addresses[$n]->description."</td>";

	// Print mac address icon
	if(in_array('mac', $selected_ip_fields)) {
        # normalize MAC address
    	if(strlen(@$addresses[$n]->mac)>0) {
        	if($User->validate_mac ($addresses[$n]->mac)!==false) {
            	$addresses[$n]->mac = $User->reformat_mac_address ($addresses[$n]->mac, 1);
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
		if(!empty($addresses[$n]->mac)) 				{ print "<td class='narrow'><i class='info fa fa-gray fa-sitemap' rel='tooltip' data-container='body' data-html='true' title='"._('MAC').": ".$addresses[$n]->mac.$mac_vendor."'></i></td>"; }
		else 											{ print "<td class='narrow'></td>"; }
	}

	// print info button for hover
	if(in_array('note', $selected_ip_fields)) {
		if(!empty($addresses[$n]->note)) 					{ print "<td class='narrow'><i class='fa fa-gray fa-comment-o' rel='tooltip' data-container='body' data-html='true' title='".str_replace("\n", "<br>", addslashes(str_replace("'", "&#39;", $addresses[$n]->note)))."'></td>"; }
		else 												{ print "<td class='narrow'></td>"; }
	}

	// print device
	if(in_array('switch', $selected_ip_fields)) {
    	# get device details
    	$device = (array) $Tools->fetch_object("devices", "id", $addresses[$n]->switch);
    	# set rack
    	if ($User->settings->enableRACK=="1")
    	$rack = $device['rack']>0 ? "<i class='btn btn-default btn-xs fa fa-server showRackPopup' data-rackid='$device[rack]' data-deviceid='$device[id]'></i>" : "";

		print "<td class='hidden-xs hidden-sm hidden-md'>$rack <a href='".create_link("tools","devices",@$device['id'])."'>". @$device['hostname'] ."</a></td>";
	}

	// print port
	if(in_array('port', $selected_ip_fields)) {
		print "<td class='hidden-xs hidden-sm hidden-md'>".$addresses[$n]->port."</td>";
	}

	# print owner
	if(in_array('owner', $selected_ip_fields)) {
		print "<td class='hidden-xs hidden-sm'>".$addresses[$n]->owner."</td>";
	}

	# print action links if user can edit
	print "<td class='btn-actions'>";
	print "	<div class='btn-group'>";
	# write permitted
	if( $subnet_permission > 1) {
		print "<a class='edit_ipaddress   btn btn-xs btn-default modIPaddr' rel='tooltip' data-container='body' title='"._('Edit IP address details')."' data-action='edit'  data-subnetId='".$addresses[$n]->subnetId."' data-id='".$addresses[$n]->id."' href='#' >															<i class='fa fa-gray fa-pencil'></i></a>";
		print "<a class='ping_ipaddress   btn btn-xs btn-default' data-subnetId='".$addresses[$n]->subnetId."' data-id='".$addresses[$n]->id."' href='#' rel='tooltip' data-container='body' title='"._('Check availability')."'>					<i class='fa fa-gray fa-cogs'></i></a>";
		print "<a class='delete_ipaddress btn btn-xs btn-default modIPaddr' rel='tooltip' data-container='body' title='"._('Delete')."' data-action='delete' data-subnetId='".$addresses[$n]->subnetId."' data-id='".$addresses[$n]->id."' href='#' id2='".$Subnets->transform_to_dotted($addresses[$n]->ip_addr)."'>		<i class='fa fa-gray fa-times'>  </i></a>";
	}
	# write not permitted
	else {
		print "<a class='edit_ipaddress   btn btn-xs btn-default disabled' rel='tooltip' data-container='body' title='"._('Edit IP address details (disabled)')."'>													<i class='fa fa-gray fa-pencil'></i></a>";
		print "<a class='				   btn btn-xs btn-default disabled'  data-id='".$addresses[$n]->id."' href='#' rel='tooltip' data-container='body' title='"._('Check availability')."'>					<i class='fa fa-gray fa-cogs'></i></a>";
		print "<a class='delete_ipaddress btn btn-xs btn-default disabled' rel='tooltip' data-container='body' title='"._('Delete IP address (disabled)')."'>														<i class='fa fa-gray fa-times'></i></a>";
	}
	if($User->get_module_permissions ("customers")>1)
	print "	<button class='btn btn-xs btn-default open_popup' rel='tooltip' title='Unlink object' data-script='app/admin/customers/unlink.php' data-class='700' data-object='ipaddresses' data-id='{$addresses[$n]->id}'><i class='fa fa-unlink'></i></button>";

	print "	</div>";
	print "</td>";

	print '</tr>'. "\n";

	// next
	$n++;

}
?>

</tbody>
</table>	<!-- end IP address table -->

<?php
}
else {
	$Result->show("info", _("No objects"));
}
?>