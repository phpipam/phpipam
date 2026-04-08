<?php

/**
 * Print all vlans
 */

# verify that user is logged in
$User->check_user_session();
# perm check
$User->check_module_permissions ("vlan", User::ACCESS_R, true, false);

# fetch l2 domain
$vlan_domain = new StdClass();

# get all VLANs and subnet descriptions
$vlans = $Tools->fetch_all_domains_and_vlans ();

# get custom VLAN fields
$custom_fields = (array) $Tools->fetch_custom_fields('vlans');

# set hidden fields
$hidden_fields = db_json_decode($User->settings->hiddenCustomFields, true);
$hidden_fields = is_array(@$hidden_fields['vlans']) ? $hidden_fields['vlans'] : array();

# size of custom fields
$csize = sizeof($custom_fields) - sizeof($hidden_fields);

# set disabled for non-admins
$disabled = $User->get_module_permissions ("vlan")>=User::ACCESS_RW ? "" : "hidden";


# title
print "<h4>"._('VLANs in all domains')."</h4>";
print "<hr>";
print "<div class='text-muted' style='padding-left:10px;'>"._('List of VLANS in all domains')."</div>";

print "<div class='btn-group' style='margin-bottom:10px;'>";
print "<a class='btn btn-sm btn-default' href='".create_link($GET->page, $GET->section)."'><i class='fa fa-angle-left'></i> "._('L2 Domains')."</a>";
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
	if($User->settings->enableCustomers=="1" && $User->get_module_permissions ("customers")>=User::ACCESS_R) {
	print ' <th data-field="customer" data-sortable="true">'._('Customer').'</th>' . "\n";
	$csize++;
	}
	if(sizeof(@$custom_fields) > 0) {
		foreach($custom_fields as $field) {
			if(!in_array($field['name'], $hidden_fields)) {
				print "	<th class='hidden-xs hidden-sm hidden-md'>".$Tools->print_custom_field_name ($field['name'])."</th>";
			}
		}
	}
	if($User->get_module_permissions ("vlan")>=User::ACCESS_RW)
    print "<th></th>";
	print "</tr>";
	print "</thead>";

	print "<tbody>";
	$m = 0;
	foreach ($vlans as $vlan) {

		// Check user has read level permission to vlan l2domain
		$vlan_domain = $Tools->fetch_object("vlanDomains", "id", $vlan->domainId);
		if (!$User->check_l2domain_permissions($vlan_domain, 1, false)) continue;

		// show free vlans - start
		if($User->user->hideFreeRange!=1 && !isset($GET->sPage)) {
			if($m==0 && $vlan->number!=1)	{
				print "<tr class='success'>";
				print "<td></td>";
				print "<td colspan='".(3+$csize)."'><btn class='btn btn-xs btn-default open_popup $disabled' data-script='app/admin/vlans/edit.php' data-action='add' data-domain='all' data-number='1'><i class='fa fa-plus'></i></btn> "._('VLAN')." 1 - ".($vlan->number -1)." (".($vlan->number -1)." "._('free').")</td>";
				print "</tr>";
			}
			# show free vlans - before vlan
			if($m>0)	{
				if( (($vlan->number)-($vlans[$m-1]->number)-1) > 0 ) {
				print "<tr class='success'>";
				print "<td></td>";
				# only 1?
				if( (($vlan->number)-($vlans[$m-1]->number)-1) ==1 ) {
					print "<td colspan='".(4+$csize)."'><btn class='btn btn-xs btn-default open_popup $disabled' data-script='app/admin/vlans/edit.php' data-action='add' data-domain='all' data-number='".($vlan->number -1)."'><i class='fa fa-plus'></i></btn> "._('VLAN')." ".($vlan->number -1)." (".(($vlan->number)-($vlans[$m-1]->number)-1)." "._('free').")</td>";

				} else {
					print "<td colspan='".(3+$csize)."'><btn class='btn btn-xs btn-default open_popup $disabled' data-script='app/admin/vlans/edit.php' data-action='add' data-domain='all' data-number='".($vlans[$m-1]->number+1)."'><i class='fa fa-plus'></i></btn> "._('VLAN')." ".($vlans[$m-1]->number+1)." - ".($vlan->number -1)." (".(($vlan->number)-($vlans[$m-1]->number)-1)." "._('free').")</td>";
				}
				print "</tr>";
				}
			}
		}

		// fixes
		$vlan->description = !is_blank($vlan->description) ? " <span class='text-muted'>( ".$vlan->description." )</span>" : "";
		$vlan->domainDescription = !is_blank($vlan->domainDescription) ? " <span class='text-muted'>( ".$vlan->domainDescription." )</span>" : "";

		// set odd / even
		$n = @$n==1 ? 0 : 1;
		$class = $n==0 ? "odd" : "even";
		// start - VLAN details
		print "<tr class='$class change'>";
		print "	<td><a class='btn btn-xs btn-default' href='".create_link($GET->page, $GET->section, $vlan->domainId, $vlan->id)."'><i class='fa fa-cloud prefix'></i> ".$vlan->number."</a></td>";
		print "	<td><a href='".create_link($GET->page, $GET->section, $vlan->domainId, $vlan->id)."'>".$vlan->name."</a>".$vlan->description."</td>";
		print "	<td>".$vlan->domainName.$vlan->domainDescription."</td>";
		if($User->settings->enableCustomers=="1" && $User->get_module_permissions ("customers")>=User::ACCESS_R) {
			 $customer = $Tools->fetch_object ("customers", "id", $vlan->customer_id);
			 print $customer===false ? "<td></td>" : "<td>{$customer->title} <a target='_blank' href='".create_link("tools","customers",$customer->title)."'><i class='fa fa-external-link'></i></a></td>";
		}
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
        if ($User->get_module_permissions ("vlan")>=User::ACCESS_RW) {
        print "<td class='actions'>";
        $links = [];
        if($User->get_module_permissions ("vlan")>=User::ACCESS_RW) {
            $links[] = ["type"=>"header", "text"=>_("Manage")];
            $links[] = ["type"=>"link", "text"=>_("Edit VLAN"), "href"=>"", "class"=>"open_popup", "dataparams"=>"data-script='app/admin/vlans/edit.php' data-action='edit' data-vlanid='$vlan->id'", "icon"=>"pencil"];
        }
        if($User->get_module_permissions ("vlan")>=User::ACCESS_RWA) {
            $links[] = ["type"=>"divider"];
            $links[] = ["type"=>"link", "text"=>_("Move VLAN"), "href"=>"", "class"=>"open_popup", "dataparams"=>"data-script='app/admin/vlans/move-vlan.php' data-action='delete' data-vlanid='$vlan->id'", "icon"=>"external-link"];
            $links[] = ["type"=>"link", "text"=>_("Delete VLAN"), "href"=>"", "class"=>"open_popup", "dataparams"=>"data-script='app/admin/vlans/edit.php' data-action='delete' data-vlanid='$vlan->id'", "icon"=>"times"];
        }
        // print links
        print $User->print_actions($User->user->compress_actions, $links);
        print "</td>";		}

        print "</tr>";

		# show free vlans - last
		if($User->user->hideFreeRange!=1 && !isset($GET->sPage)) {
			if($m==(sizeof($vlans)-1)) {
				if($User->settings->vlanMax > $vlans[0]->number) {
					print "<tr class='success'>";
					print "<td></td>";
					print "<td colspan='".(3+$csize)."'><btn class='btn btn-xs btn-default open_popup $disabled' data-script='app/admin/vlans/edit.php' data-action='add' data-domain='all' data-number='".($vlan->number+1)."'><i class='fa fa-plus'></i></btn> "._('VLAN')." ".($vlan->number+1)." - ".$User->settings->vlanMax." (".(($User->settings->vlanMax)-($vlan->number))." "._('free').")</td>";
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