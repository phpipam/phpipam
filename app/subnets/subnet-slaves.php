<script type="text/javascript">
/* fix for ajax-loading tooltips */
$('body').tooltip({ selector: '[rel=tooltip]' });
</script>
<?php

# set which custom fields to display
$hidden_fields = json_decode($User->settings->hiddenCustomFields, true);
# set visible fields
foreach ($custom_fields as $k=>$f) {
    if (isset($hidden_fields['subnets'])) {
        if (!in_array($k, $hidden_fields['subnets'])) {
            $visible_fields[$k] = $f;
        }
    }
}
# set colspan
$colspan_subnets = 5 + sizeof($visible_fields);


/**
 * Script to display all slave IP addresses and subnets in content div of subnets table!
 ***************************************************************************************/

# print title
print "<h4 style='margin-top:25px;'>$subnet[description] ($subnet[ip]/$subnet[mask]) "._('has')." ".sizeof($slave_subnets)." "._('directly nested subnets').":</h4><hr><br>";

# print HTML table
print '<table class="slaves table table-striped table-condensed table-hover table-full table-top">'. "\n";

# headers
print "<tr>";
print "	<th class='small'>"._('VLAN')."</th>";
print "	<th class='small description'>"._('Subnet description')."</th>";
print "	<th>"._('Subnet')."</th>";
# custom
if(isset($visible_fields)) {
foreach ($visible_fields as $f) {
print "	<th class='hidden-xs hidden-sm hidden-md'>$f[name]</th>";
}
}
print "	<th class='small hidden-xs hidden-sm hidden-md'>"._('Used')."</th>";
print "	<th class='small hidden-xs hidden-sm hidden-md'>% "._('Free')."</th>";
print "	<th class='small hidden-xs hidden-sm hidden-md'>"._('Requests')."</th>";
print " <th style='width:80px;'></th>";
print "</tr>";

$m = 0;				//slave index

# loop
foreach ($slave_subnets as $slave_subnet) {
	# cast to array
	$slave_subnet = (array) $slave_subnet;

	# if first check for free space if permitted
	if($User->user->hideFreeRange!=1) {
	if($m == 0) {
		# if master start != first slave start print free space
		if($subnet['subnet'] != $slave_subnet['subnet']) {
			# calculate diff between subnet and slave
			$diff = (int) gmp_strval(gmp_sub($slave_subnet['subnet'], $subnet['subnet']));

			print "<tr class='success'>";
			print "	<td></td>";
			print "	<td class='small description'><a href='#' data-sectionId='$section[id]' data-masterSubnetId='$subnet[id]' class='btn btn-sm btn-default createfromfree' data-cidr='".$Subnets->get_first_possible_subnet($subnet['ip'] , $diff, false)."'><i class='fa fa-plus'></i></a> "._('Free space')."</td>";
			print "	<td colspan='$colspan_subnets'>$subnet[ip] - ". $Subnets->transform_to_dotted(gmp_strval(gmp_add($subnet['subnet'], gmp_sub($diff,1)))) ." ( ".$diff." )</td>";
			print "</tr>";
	}	}	}


	# get VLAN details
	$slave_vlan = (array) $Tools->fetch_object("vlans", "vlanId", $slave_subnet['vlanId']);
	if(!$slave_vlan) 	{ $slave_vlan['number'] = "/"; }				//reformat empty vlan


	# calculate free / used / percentage
	if(!$has_slaves) {
		$slave_addresses = (int) $Addresses->count_subnet_addresses ($slave_subnet['id']);
		$calculate = $Subnets->calculate_subnet_usage( $slave_addresses, $slave_subnet['mask'], $slave_subnet['subnet'], $slave_subnet['isFull']);
	} else {
		$calculate = $Subnets->calculate_subnet_usage_recursive( $slave_subnet['id'], $slave_subnet['subnet'], $slave_subnet['mask'], $Addresses, $slave_subnet['isFull']);
	}

	# add full information
	$fullinfo = $slave_subnet['isFull']==1 ? " <span class='badge badge1 badge2 badge4'>"._("Full")."</span>" : "";
    if ($slave_subnet['isFull']!==1) {
        # if usage is 100%, fake usFull to true!
        if ($calculate['freehosts']==0)  { $fullinfo = "<span class='badge badge1 badge2 badge4'>"._("Full")."</span>"; }
    }

	# slaves info
	$has_slaves = $Subnets->has_slaves ($slave_subnet['id']) ? true : false;

	# description
	$has_slaves_ind = $has_slaves ? " <i class='fa fa-folder'></i> ":"";
	$slave_subnet['description'] = strlen($slave_subnet['description'])>0 ? $slave_subnet['description'] : " / ";
	$slave_subnet['description'] = $has_slaves_ind . $slave_subnet['description'];

	print "<tr>";
    print "	<td class='small'>".@$slave_vlan['number']."</td>";
    print "	<td class='small description'><a href='".create_link("subnets",$section['id'],$slave_subnet['id'])."'>$slave_subnet[description]</a></td>";
    print "	<td><a href='".create_link("subnets",$section['id'],$slave_subnet['id'])."'>".$Subnets->transform_address($slave_subnet['subnet'],"dotted")."/$slave_subnet[mask]</a> $fullinfo</td>";

    # custom
    if(isset($visible_fields)) {
    foreach ($visible_fields as $key=>$field) {
		#booleans
		if($field['type']=="tinyint(1)")	{
			if($slave_subnet[$key] == "0")		{ $html_custom = _("No"); }
			elseif($slave_subnet[$key] == "1")	{ $html_custom = _("Yes"); }
		}
		else {
			$html_custom = $Result->create_links($slave_subnet[$key]);
		}

        print "<td>".$html_custom."</td>";
    }
    }

    print ' <td class="small hidden-xs hidden-sm hidden-md">'. $calculate['used'] .'/'. $calculate['maxhosts'] .'</td>'. "\n";
    print '	<td class="small hidden-xs hidden-sm hidden-md">'. $calculate['freehosts_percent'] .'</td>';

	# allow requests
	if($slave_subnet['allowRequests'] == 1) 	{ print '<td class="allowRequests small hidden-xs hidden-sm hidden-md"><i class="fa fa-gray fa-check"></td>'; }
	else 										{ print '<td class="allowRequests small hidden-xs hidden-sm hidden-md"></td>'; }

	# edit
	$slave_subnet_permission = $Subnets->check_permission($User->user, $subnet['id']);
	if($slave_subnet_permission == 3) {
		print "	<td class='actions'>";
		print "	<div class='btn-group'>";
		print "		<button class='btn btn btn-xs btn-default editSubnet'     data-action='edit'   data-subnetid='".$slave_subnet['id']."'  data-sectionid='".$slave_subnet['sectionId']."'><i class='fa fa-gray fa-pencil'></i></button>";
		print "		<button class='btn btn btn-xs btn-default showSubnetPerm' data-action='show'   data-subnetid='".$slave_subnet['id']."'  data-sectionid='".$slave_subnet['sectionId']."'><i class='fa fa-gray fa-tasks'></i></button>";
		print "		<button class='btn btn btn-xs btn-default editSubnet'     data-action='delete' data-subnetid='".$slave_subnet['id']."'  data-sectionid='".$slave_subnet['sectionId']."'><i class='fa fa-gray fa-times'></i></button>";
		print "	</div>";
		print " </td>";
	}
	else {
		print "	<td class='actions'>";
		print "	<div class='btn-group'>";
		print "		<button class='btn btn btn-xs btn-default disabled'><i class='fa fa-gray fa-pencil'></i></button>";
		print "		<button class='btn btn btn-xs btn-default disabled'><i class='fa fa-gray fa-tasks'></i></button>";
		print "		<button class='btn btn btn-xs btn-default disabled'><i class='fa fa-gray fa-remove'></i></button>";
		print "	</div>";
		print " </td>";
	}

	print '</tr>' . "\n";


	# check if some free space between this and next subnet
	if($User->user->hideFreeRange!=1) {
	if(isset($slave_subnets[$m+1])) {

		# set max host > (bcast) from current slave
		$current_slave_details = $Subnets->get_network_boundaries ($slave_subnet['subnet'], $slave_subnet['mask']);
		$current_slave_bcast  =  $Subnets->transform_to_decimal($current_slave_details['broadcast']);
		# calculate next slave
		$next_slave_subnet  = $slave_subnets[$m+1]->subnet;
		# calculate diff
		$diff = (int) gmp_strval(gmp_sub($next_slave_subnet, $current_slave_bcast));
		# if diff print free space
		if($diff>1) {

			print "<tr class='success'>";
			print "	<td></td>";
			print "	<td class='small description'><a href='#' data-sectionId='$section[id]' data-masterSubnetId='$subnet[id]' class='btn btn-sm btn-default createfromfree' data-cidr='".$Subnets->get_first_possible_subnet($Subnets->transform_to_dotted(gmp_strval(gmp_add($current_slave_bcast, 1))), $diff, false)."'><i class='fa fa-plus'></i></a> "._('Free space')."</td>";
			print "	<td colspan='$colspan_subnets'>".$Subnets->transform_to_dotted(gmp_strval(gmp_add($current_slave_bcast, 1))) ." - ".$Subnets->transform_to_dotted(gmp_strval(gmp_sub($next_slave_subnet, 1))) ." ( ".gmp_strval(gmp_sub($diff, 1))." )</td>";
			print "</tr>";
	}	}	}

	# next - for free space check
	$m++;

	# if last check for free space
	if($User->user->hideFreeRange!=1) {
	if($m == sizeof($slave_subnets)) {

		# top subnet limit
		#
		# $subnet -> master subnet
		# $subnet_detailed -> master subnet detailed (bcast, ...)
		# $slave_subnet -> last slave subnet
		# $calculate -> last slave subnet calculations

		# set max host > (bcast) from master
		$max_host = $Subnets->transform_to_decimal($subnet_detailed['broadcast']);

		# set max host of subnet
		$slave_details  = $Subnets->get_network_boundaries ($slave_subnets[$m-1]->subnet, $slave_subnets[$m-1]->mask);
		$max_last_slave = $Subnets->transform_to_decimal($slave_details['broadcast']);

		# if slave stop < master stop print free space
		if($max_host > $max_last_slave) {
			print "<tr class='success'>";
			print "	<td></td>";
			print "	<td class='small description'><a href='#' data-sectionId='$section[id]' data-masterSubnetId='$subnet[id]' class='btn btn-sm btn-default createfromfree' data-cidr='".$Subnets->get_first_possible_subnet($Subnets->transform_to_dotted(gmp_strval(gmp_add($max_last_slave,1))), gmp_strval(gmp_sub($max_host,$max_last_slave)), false)."'><i class='fa fa-plus'></i></a> "._('Free space')."</td>";
			print "	<td colspan='$colspan_subnets'>". $Subnets->transform_to_dotted(gmp_strval(gmp_add($max_last_slave,1))) ." - ". $Subnets->transform_to_dotted(gmp_strval($max_host)) ." ( ".gmp_strval(gmp_sub($max_host,$max_last_slave))." )</td>";
			print "</tr>";
	}	}	}

}
print '</table>'. "\n";

?>