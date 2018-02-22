<?php

/**
 * Print all vlans
 */

# verify that user is logged in
$User->check_user_session();

# fetch l2 domain
$vlan_domain = new StdClass();

# get all VLANs and subnet descriptions
$vlans = $Tools->fetch_all_domains_and_vlans ();

# get custom VLAN fields
$custom_fields = (array) $Tools->fetch_custom_fields('vlans');

# set hidden fields
$hidden_fields = json_decode($User->settings->hiddenCustomFields, true);
$hidden_fields = is_array(@$hidden_fields['vlans']) ? $hidden_fields['vlans'] : array();

# size of custom fields
$csize = sizeof($custom_fields) - sizeof($hidden_fields);


# set disabled for non-admins
$disabled = $User->is_admin(false)==true ? "" : "hidden";


# title
print "<h4>"._('VLANs in all domains')."</h4>";
print "<hr>";
print "<div class='text-muted' style='padding-left:10px;'>"._('List of VLANS in all domains')."</div><hr>";

print "<div class='btn-group' style='margin-bottom:10px;'>";
print "<a class='btn btn-sm btn-default' href='".create_link($_GET['page'], $_GET['section'])."'><i class='fa fa-angle-left'></i> "._('L2 Domains')."</a>";
print "</div>";


# no VLANS?
if($vlans===false) {
	print "<hr>";
	$Result->show("info", _("No VLANS configured"), false);
}
else {
	# table
	print "<table class='table sorted vlans table-condensed table-top' data-cookie-id-table='tools_vl_all_domains'>";

	# headers
	print "<thead>";
	print '<tr">' . "\n";
	print ' <th data-field="number" data-sortable="true">'._('Number').'</th>' . "\n";
	print ' <th data-field="vlname" data-sortable="true">'._('Name').'</th>' . "\n";
	print ' <th data-field="name" data-sortable="true">'._('L2domain').'</th>' . "\n";
	if(sizeof(@$custom_fields) > 0) {
		foreach($custom_fields as $field) {
			if(!in_array($field['name'], $hidden_fields)) {
				print "	<th class='hidden-xs hidden-sm hidden-md'>".$Tools->print_custom_field_name ($field['name'])."</th>";
			}
		}
	}
    print "<th></th>";
	print "</tr>";
	print "</thead>";

	print "<tbody>";
	$m = 0;
	foreach ($vlans as $vlan) {

		// show free vlans - start
		if($User->user->hideFreeRange!=1 && !isset($_GET['sPage'])) {
			if($m==0 && $vlan->number!=1)	{
				print "<tr class='success'>";
				print "<td></td>";
				print "<td colspan='".(3+$csize)."'><btn class='btn btn-xs btn-default editVLAN $disabled' data-action='add' data-domain='all' data-number='1'><i class='fa fa-plus'></i></btn> "._('VLAN')." 1 - ".($vlan->number -1)." (".($vlan->number -1)." "._('free').")</td>";
				print "</tr>";
			}
			# show free vlans - before vlan
			if($m>0)	{
				if( (($vlan->number)-($vlans[$m-1]->number)-1) > 0 ) {
				print "<tr class='success'>";
				print "<td></td>";
				# only 1?
				if( (($vlan->number)-($vlans[$m-1]->number)-1) ==1 ) {
				print "<td colspan='".(4+$csize)."'><btn class='btn btn-xs btn-default editVLAN $disabled' data-action='add' data-domain='all' data-number='".($vlan->number -1)."'><i class='fa fa-plus'></i></btn> "._('VLAN')." ".($vlan->number -1)." (".(($vlan->number)-($vlans[$m-1]->number)-1)." "._('free').")</td>";
				} else {
				print "<td colspan='".(3+$csize)."'><btn class='btn btn-xs btn-default editVLAN $disabled' data-action='add' data-domain='all' data-number='".($vlans[$m-1]->number+1)."'><i class='fa fa-plus'></i></btn> "._('VLAN')." ".($vlans[$m-1]->number+1)." - ".($vlan->number -1)." (".(($vlan->number)-($vlans[$m-1]->number)-1)." "._('free').")</td>";
				}
				print "</tr>";
				}
			}
		}

		// fixes
		$vlan->description = strlen($vlan->description)>0 ? " <span class='text-muted'>( ".$vlan->description." )</span>" : "";
		$vlan->domainDescription = strlen($vlan->domainDescription)>0 ? " <span class='text-muted'>( ".$vlan->domainDescription." )</span>" : "";

		// set odd / even
		$n = @$n==1 ? 0 : 1;
		$class = $n==0 ? "odd" : "even";
		// start - VLAN details
		print "<tr class='$class change'>";
		print "	<td><a class='btn btn-xs btn-default' href='".create_link($_GET['page'], $_GET['section'], $vlan->domainId, $vlan->id)."'><i class='fa fa-cloud prefix'></i> ".$vlan->number."</a></td>";
		print "	<td><a href='".create_link($_GET['page'], $_GET['section'], $vlan->domainId, $vlan->id)."'>".$vlan->name."</a>".$vlan->description."</td>";
		print "	<td>".$vlan->domainName.$vlan->domainDescription."</td>";
        // custom fields - no subnets
        if(sizeof(@$custom_fields) > 0) {
	   		foreach($custom_fields as $field) {
		   		# hidden
		   		if(!in_array($field['name'], $hidden_fields)) {
					print "<td class='hidden-xs hidden-sm hidden-md'>";
					$Tools->print_custom_field ($field['type'], $vlan->{$field['name']});
					print "</td>";
				}
	    	}
	    }

        // actions
		print "	<td class='actions'>";
		print "	<div class='btn-group'>";
		print "		<button class='btn btn-xs btn-default editVLAN' data-action='edit'   data-vlanid='$vlan->id'><i class='fa fa-pencil'></i></button>";
		print "		<button class='btn btn-xs btn-default open_popup' data-script='app/admin/vlans/move-vlan.php' data-class='700' data-vlanid='$vlan->id'><i class='fa fa-external-link'></i></button>";
		print "		<button class='btn btn-xs btn-default editVLAN' data-action='delete' data-vlanid='$vlan->id'><i class='fa fa-times'></i></button>";
		print "	</div>";
		print "	</td>";

        print "</tr>";

		# show free vlans - last
		if($User->user->hideFreeRange!=1 && !isset($_GET['sPage'])) {
			if($m==(sizeof($vlans)-1)) {
				if($User->settings->vlanMax > $vlans[0]->number) {
					print "<tr class='success'>";
					print "<td></td>";
					print "<td colspan='".(3+$csize)."'><btn class='btn btn-xs btn-default editVLAN $disabled' data-action='add' data-domain='all' data-number='".($vlan->number+1)."'><i class='fa fa-plus'></i></btn> "._('VLAN')." ".($vlan->number+1)." - ".$User->settings->vlanMax." (".(($User->settings->vlanMax)-($vlan->number))." "._('free').")</td>";
					print "</tr>";
				}
			}
		}
		# next index
		$m++;
	}
	print "</tbody>";

	print '</table>';
}