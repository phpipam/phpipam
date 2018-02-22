<?php

/**
 * Print all vlans
 */

# verify that user is logged in
$User->check_user_session();

# fetch l2 domain
$vlan_domain = $Tools->fetch_object("vlanDomains", "id", $_GET['subnetId']);
if($vlan_domain===false)			{ $Result->show("danger", _("Invalid ID"), true); }

# get all VLANs and subnet descriptions
$vlans = $Tools->fetch_vlans_and_subnets ($vlan_domain->id);

# get custom VLAN fields
$custom_fields = (array) $Tools->fetch_custom_fields('vlans');

# set hidden fields
$hidden_fields = json_decode($User->settings->hiddenCustomFields, true);
$hidden_fields = is_array(@$hidden_fields['vlans']) ? $hidden_fields['vlans'] : array();

# size of custom fields
$csize = sizeof($custom_fields) - sizeof($hidden_fields);
if($_GET['page']=="administration") { $csize++; }


# title
print "<h4>"._('Available VLANs in domain')." $vlan_domain->name</h4>";
print "<hr>";
print "<div class='text-muted' style='padding-left:10px;'>".$vlan_domain->description."</div><hr>";
?>
<br>
<div class="btn-group" style="margin-bottom:10px;">
    <?php
    // back
    if(sizeof($vlan_domains)>1) {
    print "<a class='btn btn-sm btn-default' href='".create_link($_GET['page'], $_GET['section'])."'><i class='fa fa-angle-left'></i> "._('L2 Domains')."</a>";
    }
    ?>
    <?php
    // l2 domains
    if($User->is_admin(false)===true && sizeof($vlan_domains)==1) { ?>
	<button class='btn btn-sm btn-default open_popup' data-script='app/admin/vlans/edit-domain.php' data-class='700' data-action='add'><i class='fa fa-plus'></i> <?php print _('Add L2 Domain'); ?></button>
	<?php } ?>
    <?php
    // snmp
    if($User->is_admin(false)===true && $User->settings->enableSNMP==1) { ?>
	<button class="btn btn-sm btn-default" id="snmp-vlan" data-action="add" data-domainid="<?php print $vlan_domain->id; ?>"><i class="fa fa-cogs"></i> <?php print _('Scan for VLANs'); ?></button>
	<?php } ?>
	<button class="btn btn-sm btn-default editVLAN" data-action="add" data-domain="<?php print $vlan_domain->id; ?>" style="margin-bottom:10px;"><i class="fa fa-plus"></i> <?php print _('Add VLAN'); ?></button>

</div>

<?php
# no VLANS?
if($vlans===false) {
	$Result->show("info", _("No VLANS configured"), false);
}
else {
	# table
	print "<table class='table sorted vlans table-condensed table-top' data-cookie-id-table='tools_vlan_2'>";

	# headers
	print "<thead>";
	print '<tr">' . "\n";
	print ' <th data-field="number" data-sortable="true">'._('Number').'</th>' . "\n";
	print ' <th data-field="name" data-sortable="true">'._('Name').'</th>' . "\n";
	print ' <th data-field="description" data-sortable="true">'._('Description').'</th>' . "\n";
	if(sizeof(@$custom_fields) > 0) {
		foreach($custom_fields as $field) {
			if(!in_array($field['name'], $hidden_fields)) {
				print "	<th class='hidden-xs hidden-sm hidden-md'>".$Tools->print_custom_field_name ($field['name'])."</th>";
			}
		}
	}
	print ' <th>'._('Belonging subnets').'</th>' . "\n";
	print ' <th>'._('Section').'</th>' . "\n";
    print "<th></th>";
	print "</tr>";
	print "</thead>";

	print "<tbody>";
	$m = 0;
	foreach ($vlans as $vlan) {

		// show free vlans - start
		if($User->user->hideFreeRange!=1) {
			if($m==0 && $vlan[0]->number!=1)	{
				print "<tr class='success'>";
				print "<td></td>";
				print "<td colspan='".(5+$csize)."'><btn class='btn btn-xs btn-default editVLAN' data-action='add' data-domain='".$vlan_domain->id."'  data-number='1'><i class='fa fa-plus'></i></btn> "._('VLAN')." 1 - ".($vlan[0]->number)." (".($vlan[0]->number -1)." "._('free').")</td>";
				print "</tr>";
			}
			# show free vlans - before vlan
			if($m>0)	{
				if( (($vlans[$m][0]->number)-($vlans[$m-1][0]->number)-1) > 0 ) {
				print "<tr class='success'>";
				print "<td></td>";
				# only 1?
				if( (($vlans[$m][0]->number)-($vlans[$m-1][0]->number)-1) ==1 ) {
				print "<td colspan='".(5+$csize)."'><btn class='btn btn-xs btn-default editVLAN' data-action='add' data-domain='".$vlan_domain->id."' data-number='".($vlan[0]->number -1)."'><i class='fa fa-plus'></i></btn> "._('VLAN')." ".($vlan[0]->number -1)." (".(($vlans[$m][0]->number)-($vlans[$m-1][0]->number)-1)." "._('free').")</td>";
				} else {
				print "<td colspan='".(5+$csize)."'><btn class='btn btn-xs btn-default editVLAN' data-action='add' data-domain='".$vlan_domain->id."' data-number='".($vlans[$m-1][0]->number+1)."'><i class='fa fa-plus'></i></btn> "._('VLAN')." ".($vlans[$m-1][0]->number+1)." - ".($vlan[0]->number -1)." (".(($vlans[$m][0]->number)-($vlans[$m-1][0]->number)-1)." "._('free').")</td>";
				}
				print "</tr>";
				}
			}
		}

		//save first
		$first = $vlan[0];

		//unset if no permissions
		foreach($vlan as $k=>$v) {
			if ($v->subnetId!=null) {
				$permission = $Subnets->check_permission ($User->user, $v->subnetId);
				if($permission==0) {
					unset($vlan[$k]);
				}
			}
		}

		//if none
		if(sizeof($vlan)==0) {
			$first->subnetId = null;
			$vlan[0] = $first;
		}

		//subnets
		if(sizeof($vlan)>0) {
			foreach($vlan as $k=>$v) {
				//first?
				if($k==0) {
					//set odd / even
					$n = @$n==1 ? 0 : 1;
					$class = $n==0 ? "odd" : "even";
					//start - VLAN details
					print "<tr class='$class change'>";
					print "	<td><a class='btn btn-xs btn-default' href='".create_link($_GET['page'], $_GET['section'], $vlan_domain->id, $vlan[0]->vlanId)."'><i class='fa fa-cloud prefix'></i> ".$vlan[0]->number."</a></td>";
					print "	<td><a href='".create_link($_GET['page'], $_GET['section'], $vlan_domain->id, $vlan[0]->vlanId)."'>".$vlan[0]->name."</a></td>";
					print "	<td>".$vlan[0]->description."</td>";
			        //custom fields - no subnets
			        if(sizeof(@$custom_fields) > 0) {
				   		foreach($custom_fields as $field) {
					   		# hidden
					   		if(!in_array($field['name'], $hidden_fields)) {
								print "<td class='hidden-xs hidden-sm hidden-md'>";
								$Tools->print_custom_field ($field['type'], $v->{$field['name']});
								print "</td>";
							}
				    	}
				    }
				}
				else {
					print "<tr class='$class'>";
					print "<td></td>";
					print "<td></td>";
					print "<td></td>";
			        if(sizeof(@$custom_fields) > 0) {
				   		foreach($custom_fields as $field) {
					   		# hidden
					   		if(!in_array($field['name'], $hidden_fields)) {
    					   		print "<td></td>";
    					    }
                        }
                    }
				}
				//subnet?
				if ($v->subnetId!=null) {
					//section
					$section = $Sections->fetch_section (null, $v->sectionId);
					print " <td><a href='".create_link("subnets",$section->id,$v->subnetId)."'>". $Subnets->transform_to_dotted($v->subnet) ."/$v->mask</a></td>";
					print " <td><a href='".create_link("subnets",$section->id)."'>$section->name</a></td>";

					// actions
					if ($k==0) {
						print "	<td class='actions'>";
						print "	<div class='btn-group'>";
						print "		<button class='btn btn-xs btn-default editVLAN' data-action='edit'   data-vlanid='$v->vlanId'><i class='fa fa-pencil'></i></button>";
						print "		<button class='btn btn-xs btn-default open_popup' data-script='app/admin/vlans/move-vlan.php' data-class='700' data-vlanid='$v->vlanId'><i class='fa fa-external-link'></i></button>";
						print "		<button class='btn btn-xs btn-default editVLAN' data-action='delete' data-vlanid='$v->vlanId'><i class='fa fa-times'></i></button>";
						print "	</div>";
						print "	</td>";
					}
					else {
						print "<td></td>";
					}
				    print "</tr>";
				}
				// no subnets
				else {
					print "	<td>/</td>";
					print "	<td>/</td>";
					// actions
					if ($k==0) {
						print "	<td class='actions'>";
						print "	<div class='btn-group'>";
						print "		<button class='btn btn-xs btn-default editVLAN' data-action='edit'   data-vlanid='$v->vlanId'><i class='fa fa-pencil'></i></button>";
						print "		<button class='btn btn-xs btn-default open_popup' data-script='app/admin/vlans/move-vlan.php' data-class='700' data-vlanid='$v->vlanId'><i class='fa fa-external-link'></i></button>";
						print "		<button class='btn btn-xs btn-default editVLAN' data-action='delete' data-vlanid='$v->vlanId'><i class='fa fa-times'></i></button>";
						print "	</div>";
						print "	</td>";
					}
					else {
    					print "	<td>/</td>";

					}

					print "</tr>";
				}
			}
		}

		# show free vlans - last
		if($User->user->hideFreeRange!=1) {
			if($m==(sizeof($vlans)-1)) {
				if($User->settings->vlanMax > $vlan[0]->number) {
					print "<tr class='success'>";
					print "<td></td>";
					print "<td colspan='".(5+$csize)."'><btn class='btn btn-xs btn-default editVLAN' data-action='add' data-domain='".$vlan_domain->id."'  data-number='".($vlan[0]->number+1)."'><i class='fa fa-plus'></i></btn> "._('VLAN')." ".($vlan[0]->number+1)." - ".$User->settings->vlanMax." (".(($User->settings->vlanMax)-($vlan[0]->number))." "._('free').")</td>";
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
?>
