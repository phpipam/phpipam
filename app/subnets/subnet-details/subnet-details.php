<?php

/**
 * Main script to display master subnet details if subnet has slaves
 ***********************************************************************/

# set rowspan
$rowSpan = 10 + sizeof($custom_fields);

# detect multicast
if ($User->settings->enableMulticast==1) {
    $multicast_badge = $Subnets->is_multicast ($subnet['subnet']) ? " <span class='badge badge1 badge5'>"._("Multicast")."</span>" : "";
}
else {
    $multicast_badge = "";
}
?>

<table class="ipaddress_subnet table-condensed table-full">
	<tr>
		<th style='padding-top:2px !important;'><?php print _('Subnet details'); ?></th>
		<td><span style="font-size:14px;border:1px solid #ccc;background:white;padding:4px 8px;border-radius:3px;" class='subnet_badge'><?php print "<b>".$Subnets->transform_address($subnet["subnet"],"dotted")."/$subnet[mask]</b> ($subnet_detailed[netmask])"; ?></span> <?php print $multicast_badge; ?></td>
	</tr>
    <?php
        // if subnet is IPv4 search for linked IPv6 subnet, else show linked ipv4
        $type = $Subnets->identify_address($subnet['subnet']);

        if ($type=="IPv4" && $subnet['linked_subnet']!==null && $subnet['linked_subnet']!==0) {
            $linked_subnet = $Subnets->fetch_subnet ("id", $subnet['linked_subnet']);

            if ($linked_subnet !== false) {
                // desc fix
                $linked_subnet->description = !is_blank($linked_subnet->description) ? "($linked_subnet->description)" : "";

                print "<tr>";
                print " <th style='font-weight:normal'>";
                print " <p class='text-muted' style='margin-top:5px;'>"._("Linked IPv6 subnet")."</p>";
                print " </th>";
                print " <td>";
                print " <ul class='submenu-linked'>";
                print "<li style='font-size:13px;'>";
                print "<i class='icon-gray fa fa-gray fa-angle-right'></i> ";
                print "<a href='".create_link("subnets", $linked_subnet->sectionId, $linked_subnet->id)."'>".$Subnets->transform_address($linked_subnet->subnet,"dotted")."/$linked_subnet->mask</a> $linked_subnet->description";
                print "</li>";
                print " </ul>";
                print " </td>";
                print "</tr>";
            }
        }
        # IPv6 - search if any subnet is linked to it
        else {
            // linked search
            $is_linked_subnets = $Subnets->is_linked($subnet['id']);

            if ($is_linked_subnets !==false) {
                print "<tr>";
                print " <th style='font-weight:normal'>";
                print "  <p class='text-muted' style='margin-top:5px;'>"._("Linked IPv4 subnets")."</p>";
                print " </th>";
                print " <td>";
                print " <ul class='submenu-linked'>";
                foreach ($is_linked_subnets as $k=>$linked_subnet) {
                    // desc fix
                    $linked_subnet->description = !is_blank($linked_subnet->description) ? "($linked_subnet->description)" : "";

                    print "<li style='font-size:13px;'>";
                    print "<i class='icon-gray fa fa-gray fa-angle-right'></i> ";
                    print "<a href='".create_link("subnets", $linked_subnet->sectionId, $linked_subnet->id)."'>".$Subnets->transform_address($linked_subnet->subnet,"dotted")."/$linked_subnet->mask</a> $linked_subnet->description";
                    print "</li>";
                }
                print " </ul>";
                print " </td>";
                print "</tr>";
            }
        }
        ?>

	<tr>
		<th><?php print _('Hierarchy'); ?></th>
		<td>
			<?php $Subnets->print_breadcrumbs($Sections, $Subnets, $GET->as_array()); ?>
		</td>
	</tr>
	<tr>
		<th><?php print _('Subnet description'); ?></th>
		<td><?php print $subnet['description']; ?></td>
	</tr>

	<tr>
		<th><?php print _('Permission'); ?></th>
		<td><?php print $Subnets->parse_permissions($subnet_permission); ?></td>
	</tr>
	<tr>
		<th><?php print _('Subnet Usage'); ?></th>
		<td>
			<?php
				  print ''._('Used').':  '. $Subnets->reformat_number ($subnet_usage['used']) .' |
						 '._('Free').':  '. $Subnets->reformat_number ($subnet_usage['freehosts']) .' ('. $subnet_usage['freehosts_percent']  .'%) |
						 '._('Total').': '. $Subnets->reformat_number ($subnet_usage['maxhosts']);
			?>
		</td>
	</tr>
	<?php if(!$slaves) { ?>

	<!-- gateway -->
	<?php
	$gateway = $Subnets->find_gateway($subnet['id']);
	if($gateway !==false) { ?>
	<tr>
		<th><?php print _('Gateway'); ?></th>
		<td><strong><?php print $Subnets->transform_to_dotted($gateway->ip_addr);?></strong></td>
	</tr>
	<?php } ?>

	<?php } ?>

	<?php if(@is_array($all_nats_per_object['subnets'])) { if(@array_key_exists($subnet['id'], $all_nats_per_object['subnets'])) { ?>
	<tr>
		<th><?php print _('NAT'); ?></th>
		<td><?php $Addresses->print_nat_link($all_nats, $all_nats_per_object, $subnet, false, "subnet"); ?> <?php print _("Subnet is natted"); ?></a></td>
	</tr>
	<?php }} ?>

	<?php if($User->get_module_permissions ("vlan")>=User::ACCESS_R) { ?>
	<tr>
		<th><?php print _('VLAN'); ?></th>
		<td>
		<?php
		if(empty($vlan['number']) || $vlan['number'] == 0) { $vlan['number'] = "<span class='text-muted'>/</span>"; }	//Display fix for emprt VLAN
		print $vlan['number'];

		if(!empty($vlan['name'])) 		 { print ' - '.$vlan['name']; }					//Print name if provided
		if(!empty($vlan['description'])) { print ' ['. $vlan['description'] .']'; }		//Print description if provided
		// domain
		if (isset($vlan['domainId'])) {
    		$l2domain = $Tools->fetch_object("vlanDomains", "id", $vlan['domainId']);
    		if($l2domain!==false)       { print " <span class='badge badge1 badge5' rel='tooltip' title='"._('VLAN is in domain')." $l2domain->name'>$l2domain->name "._('Domain')." </span>"; }
        }
		?>
		</td>
	</tr>
	<?php } ?>

	<?php
	# VRF
	if($User->settings->enableVRF==1 && $User->get_module_permissions ("vrf")>=User::ACCESS_R) {
		# get vrf details
		$vrf = $Tools->fetch_object("vrf", "vrfId" ,$subnet['vrfId']);
		# null
		if($vrf===false) {
			$vrfText = "<span class='text-muted'>"._("None")."</span>";
		}
		else {
			# set text
			$vrfText = "<a href='".create_link("tools","vrf",$vrf->vrfId)."' target='_blank'>".$vrf->name."</a>";
			if(!empty($vrf->description)) { $vrfText .= " [$vrf->description]";}
		}

		print "<tr>";
		print "	<th>"._('VRF')."</th>";
		print "	<td>$vrfText</td>";
		print "</tr>";

		$vrf = (array) $vrf;
	}
	?>

	<!-- nameservers -->
	<tr>
		<th><?php print _('Nameservers'); ?></th>
		<td>
		<?php

		// Only show nameservers if defined for subnet
		if(!empty($subnet['nameserverId'])) {
			# fetch recursive nameserver details
			$nameservers = $Tools->fetch_object("nameservers", "id", $subnet['nameserverId']);
			print str_replace(";", ", ", $nameservers->namesrv1);
			//Print name of nameserver group
			print ' ('.$nameservers->name.')';
		}

		else {
			print "<span class='text-muted'>/</span>";
		}
		?>
		</td>
	</tr>

	<!-- Customers -->
	<?php if($User->get_module_permissions ("customers")>=User::ACCESS_R) { ?>
	<tr>
		<th><?php print _('Customer'); ?></th>
		<td>
		<?php

		if(!empty($subnet['customer_id'])) {
			# fetch recursive nameserver details
			$customer = $Tools->fetch_object("customers", "id", $subnet['customer_id']);
			if ($customer!==false) {
				print $customer->title." <a target='_blank' href='".create_link("tools","customers",$customer->title)."'><i class='fa fa-external-link'></i></a>";
			}
			else {
				print "<span class='text-muted'>/</span>";
			}
		}
		else {
			print "<span class='text-muted'>/</span>";
		}
		?>
		</td>
	</tr>
	<?php } ?>

	<?php if($User->get_module_permissions ("devices")>=User::ACCESS_R) { ?>
	<!-- devices -->
	<tr>
		<th><?php print _('Device'); ?></th>
		<td>
		<?php

		// Only show device if defined for subnet
		if(!empty($subnet['device'])) {
			# fetch recursive nameserver details
			$device = $Tools->fetch_object("devices", "id", $subnet['device']);
			if (is_object($device)) {
				# rack
				if ($User->settings->enableRACK=="1" && !is_blank($device->rack) && $User->get_module_permissions ("racks")>=User::ACCESS_RW) {
					if (!isset($Racks)) $Racks = new phpipam_rack($Database);
					$Racks->add_rack_start_print($device);
					$rack = $Tools->fetch_object("racks", "id", $device->rack);
					$rack_text = !is_object($rack) ? "" : "<br><span class='badge badge1 badge5' style='padding-top:4px;'>$rack->name / "._('Position').": $device->rack_start_print "._("Size").": $device->rack_size U <i class='btn btn-default btn-xs fa fa-server showRackPopup' data-rackId='$rack->id' data-deviceId='$device->id'></i></span>";
				}
				print "<a href='".create_link("tools","devices",$device->id)."'>".$device->hostname."</a>";
				if (!is_blank($device->description)) {
					print ' ('.$device->description.')';
				}
				print $rack_text;
			}
			else {
				print "<span class='text-muted'>/</span>";
			}
		}
		else {
			print "<span class='text-muted'>/</span>";
		}
		?>
		</td>
	</tr>
	<?php } ?>

	<!-- Location -->
	<?php if($User->settings->enableLocations=="1" && $User->get_module_permissions ("locations")>=User::ACCESS_R) { ?>
	<tr>
		<th><?php print _('Location'); ?></th>
		<td>
		<?php

		// Only show nameservers if defined for subnet
		if(!empty($subnet['location']) && $subnet['location']!=0) {
			# fetch recursive nameserver details
			$location2 = $Tools->fetch_object("locations", "id", $subnet['location']);
            if($location2!==false) {
                print "<a href='".create_link("subnets", $subnet['sectionId'], $subnet['id'], "location")."'>$location2->name</a>";
            }
		}

		else {
			print "<span class='text-muted'>/</span>";
		}
		?>
		</td>
	</tr>
    <?php } ?>

    <?php if(@$subnet['isFull'] || @$subnet['isPool']) { ?>
    <tr>
        <td colspan="2"><hr></td>
    </tr>
    <tr>
        <th></th>
        <td class="isFull">
        <?php
        if ($subnet['isFull'])
        print $Result->show("info pull-left", "<i class='fa fa-info-circle'></i> "._("Subnet is marked as full"), false, false, true);
        if ($subnet['isPool'])
        print $Result->show("info pull-left", "<i class='fa fa-info-circle'></i> "._("Subnet is marked as pool"), false, false, true);
        ?></td>
    </tr>
    <?php } ?>

	<?php if($subnet_permission==3) { ?>
    <tr>
    	<th><?php print _("Last edited"); ?></th>
    	<td>
    		<span class="text-muted">
    		<?php
    		if(!is_blank($subnet['editDate']))  	{ print $subnet['editDate']; }
    		else 								{ print _("Never"); }
    		?>
    		</span>
    	</td>
    </tr>
    <?php } ?>

    <?php if($User->settings->enableThreshold=="1" && $subnet['threshold']>0) { ?>
    <tr>
        <td colspan="2"><hr></td>
    </tr>
    <tr>
        <?php
        // add alert class if over usage
        $aclass = gmp_strval(gmp_sub(100,(int) round($subnet_usage['freehosts_percent'], 0)))>$subnet['threshold'] ? "alert alert-danger pull-left" : "";
        $subnet['threshold'] = gmp_strval(gmp_sub(100,(int) round($subnet_usage['freehosts_percent'], 0)))>$subnet['threshold'] ? "<i class='fa fa-warning pull-left'></i> ".$subnet['threshold'] : $subnet['threshold'];
        ?>
        <th><?php print _("Alert threshold"); ?></th>
        <td>
            <div class="<?php print $aclass; ?>">
                <?php print _('Threshold')." ".$subnet['threshold']."%, ". _("Current usage").": ".gmp_strval(gmp_sub(100,(int) round($subnet_usage['freehosts_percent'], 0))); ?>%
            </div>
        </td>
    </tr>
    <?php } ?>

	<?php
	# FW zone info
	if($User->settings->enableFirewallZones==1) {
		# class
		$Zones = new FirewallZones ($Database);
		$fwZone = $Zones->get_zone_subnet_info ($subnet['id']);

		if ($fwZone!==false) {
			// alias fix
			$fwZone->alias 		= !is_blank($fwZone->alias) ? "(".$fwZone->alias.")" : "";
			$fwZone->description 	= !is_blank($fwZone->description) ? " - ".$fwZone->description : "";
			$fwZone->interface 	= !is_blank($fwZone->interface) ? "(".$fwZone->interface.")" : "";

			# divider
			print "<tr>";
			print "	<td colspan='2'><hr></td>";
			print "</tr>";
			# zone details
			print "<tr>";
			print "	<th>"._('Firewall zone')."</th>";
			print "	<td>";
			print $fwZone->zone." ".$fwZone->alias." ".$fwZone->description."<br>".$fwZone->deviceName." ".$fwZone->interface;
			print "	</td>";
			print "</tr>";
			# divider
			print "<tr>";
			print "	<td colspan='2'><hr></td>";
			print "</tr>";
			# address object information
			print "<tr>";
			print "	<th>"._('Address object')."</th>";
			print "	<td>";
			if($fwZone->firewallAddressObject) {
				print $fwZone->firewallAddressObject;
			}
			if($subnet_permission > 1) {
				print '<a style="margin-left:10px;" href="" class="fw_autogen btn btn-default btn-xs" data-action="subnet" data-subnetid="'.$subnet['id'].'" rel="tooltip" title="'._('Generate or regenerate the subnets firewall address object name.').'"><i class="fa fa-repeat"></i></a>';
			}
			print "	</td>";
			print "</tr>";
		}
	}

	if(!$slaves) {

		# Are IP requests allowed?
		if ($User->settings->enableIPrequests==1 && $subnet_permission==3) {
			# divider
			print "<tr>";
			print "	<td colspan='2'><hr></td>";
			print "</tr>";

			print "<tr>";
			print "	<th>"._('IP requests')."</th>";
			if(@$subnet['isFull'] == 1) 		    { print "	<td>"._('disabled - marked as full')."</td>"; }		# yes
			elseif($subnet['allowRequests'] == 1) 	{ print "	<td><span class='badge badge1 badge5 alert-success'>"._('enabled')."</span></td>"; }		# yes
			else 									{ print "	<td><span class='badge badge1 badge5'>"._('disabled')."</span></td>";}		# no
			print "</tr>";
		}

		# admin only
		if($subnet_permission==3) {
			# divider
			print "<tr>";
			print "	<td colspan='2'><hr></td>";
			print "</tr>";

			# agent
			if ($subnet['pingSubnet']==1 || $subnet['discoverSubnet']==1) {
			print "<tr>";
			print "	<th>"._('Scan agent')."</th>";
			print "	<td>";
			// fetch
			$agent = $Tools->fetch_object ("scanAgents", "id", $subnet['scanAgent']);
			if ($agent===false)		{ print _("Invalid scan agent"); }
			else					{
				$last_check = is_null($agent->last_access)||$agent->last_access=="0000-00-00 00:00:00"||$agent->last_access=="1970-01-01 00:00:01" ? "Never" : $agent->last_access;
				print "<strong>".$agent->name ."</strong> (".$agent->description.") <br> <span class='text-muted'>"._("Last check")." $last_check</span>";
			}
			print "	</td>";
			print "</tr>";
			}

			# ping-check hosts inside subnet
			$last_check_s = is_null($subnet['lastScan'])||$subnet['lastScan']==""||$subnet['lastScan']=="0000-00-00 00:00:00" ? "" : " <span class='text-muted'>"._("Last scan")." ".$subnet['lastScan']."</div>";
			$last_check_d = is_null($subnet['lastDiscovery'])||$subnet['lastDiscovery']==""||$subnet['lastDiscovery']=="0000-00-00 00:00:00" ? "" : " <span class='text-muted'>"._("Last scan")." ".$subnet['lastDiscovery']."</div>";

			print "<tr>";
			print "	<th>"._('Hosts check')."</th>";
			if($subnet['pingSubnet'] == 1) 				{ print "	<td><span class='badge badge1 badge5 alert-success'>"._('enabled')."</span> $last_check_s</td>"; }		# yes
			else 										{ print "	<td><span class='badge badge1 badge5'>"._('disabled')."</span></td>";}		# no
			print "</tr>";
			# scan subnet for new hosts *
			print "<tr>";
			print "	<th>"._('Discover new hosts')."</th>";
			if($subnet['discoverSubnet'] == 1) 			{ print "	<td><span class='badge badge1 badge5 alert-success'>"._('enabled')."</span> $last_check_d</td>"; }		# yes
			else 										{ print "	<td><span class='badge badge1 badge5'>"._('disabled')."</span></td>";}		# no
			print "</tr>";
			# resolve DNS names
			print "<tr>";
			print "	<th>"._('Resolve DNS names')."</th>";
			if($subnet['resolveDNS'] == 1) 			    { print "	<td><span class='badge badge1 badge5 alert-success'>"._('enabled')."</span></td>"; }		# yes
			else 										{ print "	<td><span class='badge badge1 badge5'>"._('disabled')."</span></td>";}		# no
			print "</tr>";
		}
	}

	# autocreate PTR records
	if($User->settings->enablePowerDNS==1 && $subnet_permission==3 && $User->get_module_permissions ("pdns")>=User::ACCESS_R) {
		// initialize class
		if ($subnet['DNSrecursive'] == 1 || $subnet['DNSrecords']==1) {
			# powerDNS class
			$PowerDNS = new PowerDNS ($Database);
		}
		if ($subnet['DNSrecursive'] == 1) {
		if($PowerDNS->db_check()!==false) {
			// set name
			$zone = $PowerDNS->get_ptr_zone_name ($subnet['ip'], $subnet['mask']);
			// fetch domain
			$domain = $PowerDNS->fetch_domain_by_name ($zone);
			// count PTR records
			if ($domain!==false) {
				if ($User->check_module_permissions ("pdns", User::ACCESS_RWA, false, false)) {
				$btns[] = "<div class='btn-group'>";
            if (preg_match("/^.*ip6.arpa$/", $domain->name)) {
				   $btns[] = " <a class='btn btn-default btn-xs' href='". create_link ("tools", "powerDNS", "reverse_v6", "records", $domain->name)."'><i class='fa fa-eye'></i></a>";
            }
            else {
				   $btns[] = " <a class='btn btn-default btn-xs' href='". create_link ("tools", "powerDNS", "reverse_v4", "records", $domain->name)."'><i class='fa fa-eye'></i></a>";
            }
				$btns[] = "	<a class='btn btn-default btn-xs refreshPTRsubnet' data-subnetid='$subnet[id]'><i class='fa fa-refresh'></i></a>";
				$btns[] = "</div>";
				$btns = implode("\n", $btns);
				}
				else {
				$btns = "";
				}

				$zone = "<span class='text-muted'>(domain $zone)</span> <span class='badge badge1 badge5'>".$PowerDNS->count_domain_records_by_type ($domain->id, "PTR")." records</span>";
			}
			else {
				if ($User->check_module_permissions ("pdns", User::ACCESS_RWA, false, false)) {
				$btns[] = "<div class='btn-group'>";
				$btns[] = "	<a class='btn btn-default btn-xs refreshPTRsubnet' data-subnetid='$subnet[id]'><i class='fa fa-refresh'></i></a>";
				$btns[] = "</div>";
				$btns = implode("\n", $btns);
				}

				$zone = "<span class='badge alert-danger'>Zone $zone missing</span>";
			}
		}
		else {
			$zone = "<span class='badge alert-danger'>Cannot connect to powerDNS database!</span>";
		}
		}
		# divider
		print "<tr>";
		print "	<td colspan='2'><hr></td>";
		print "</tr>";

		print "<tr>";
		print "	<th>"._('Autocreate reverse records')."</th>";
		if($subnet['DNSrecursive'] == 1) 			{ print "	<td><span class='badge badge1 badge5 alert-success'>"._('enabled')."</span> $btns $zone</td>"; }		# yes
		else 										{ print "	<td><span class='badge badge1 badge5'>"._('disabled')."</span></td>";}		# no
		print "</tr>";
		print "<tr>";
		print "	<th>"._('Show DNS records')."</th>";
		if($subnet['DNSrecords'] == 1) 				{ print "	<td><span class='badge badge1 badge5 alert-success'>"._('enabled')."</span></td>"; }		# yes
		else 										{ print "	<td><span class='badge badge1 badge5'>"._('disabled')."</span></td>";}		# no
		print "</tr>";
	}

	?>

	<?php
	# custom subnet fields
	if(sizeof($custom_fields) > 0) {
		foreach($custom_fields as $key=>$field) {
			if(!is_blank($subnet[$key])) {
				$subnet[$key] = str_replace(array("\n", "\r\n"), "<br>",$subnet[$key]);
				$html_custom[] = "<tr>";
				$html_custom[] = "	<th>".$Tools->print_custom_field_name ($key)."</th>";
				$html_custom[] = "	<td>";
				#booleans
				if($field['type']=="tinyint(1)")	{
					if($subnet[$key] == "0")		{ $html_custom[] = _("No"); }
					elseif($subnet[$key] == "1")	{ $html_custom[] = _("Yes"); }
				}
				else {
					$html_custom[] = $Tools->create_links($subnet[$key]);
				}
				$html_custom[] = "	</td>";
				$html_custom[] = "</tr>";
			}
		}

		# any?
		if(isset($html_custom)) {
			# divider
			print "<tr>";
			print "	<th><hr></th>";
			print "	<td><hr></td>";
			print "</tr>";

			print implode("\n", $html_custom);
		}
	}

	# check for temporary shares!
	if($User->settings->tempShare==1) {
		if (is_array(db_json_decode($User->settings->tempAccess, true))) {
			foreach(db_json_decode($User->settings->tempAccess) as $s) {
				if($s->type=="subnets" && $s->id==$subnet['id']) {
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
			print "<tr>";
			print "	<th><hr></th>";
			print "	<td></td>";
			print "</tr>";
			# print
			print "<tr>";
			print "<th>"._("Active subnet shares").":</th>";
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
			print "<th>"._("Expired subnet shares").":</th>";
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

	print "<tr>";
	print "<td colspan=2><hr></td>";
	print "</tr>";

	# action button groups
	print "<tr>";
	print "	<th>"._('Actions')."</th>";
	print "	<td class='actions'>";

	print "	<div class='btn-toolbar'>";

	$sp = [];
	# set values for permissions
	if($subnet_permission == 1) {
		$sp['editsubnet']= false;		//edit subnet
		$sp['editperm']  = false;		//edit permissions

		$sp['addip'] 	 = false;		//add ip address
		$sp['scan']		 = false;		//scan subnet
		$sp['changelog'] = false;		//changelog view
		$sp['import'] 	 = false;		//import
	}
	elseif ($subnet_permission == 2) {
		$sp['editsubnet']= false;		//edit subnet
		$sp['editperm']  = false;		//edit permissions

		$sp['addip'] 	 = true;		//add ip address
		$sp['scan']		 = true;		//scan subnet
		$sp['changelog'] = true;		//changelog view
		$sp['import'] 	 = true;		//import
	}
	elseif ($subnet_permission == 3) {
		$sp['editsubnet']= true;		//edit subnet
		$sp['editperm']  = true;		//edit permissions

		$sp['addip'] 	 = true;		//add ip address
		$sp['scan']		 = true;		//scan subnet
		$sp['changelog'] = true;		//changelog view
		$sp['import'] 	 = true;		//import
	}

	# edit / permissions / nested
	print "<div class='btn-group'>";
		# warning
		if($subnet_permission == 1)
		print "<button class='btn btn-xs btn-default btn-danger' 	data-container='body' rel='tooltip' title='"._('You do not have permissions to edit subnet or IP addresses')."'>													<i class='fa fa-lock'></i></button> ";
		# edit subnet
		if($sp['editsubnet'])
		print "<a class='edit_subnet btn btn-xs btn-default' 	href='' data-container='body' rel='tooltip' title='"._('Edit subnet properties')."'	data-subnetId='$subnet[id]' data-sectionId='$subnet[sectionId]' data-action='edit'>	<i class='fa fa-pencil'></i></a>";
		else
		print "<a class='btn btn-xs btn-default disabled' 		href='' data-container='body' rel='tooltip' title='"._('Edit subnet properties')."'>																					<i class='fa fa-pencil'></i></a>";
		# add nested subnet
		if($section_permission == 3) {
		print "<a class='edit_subnet btn btn-xs btn-default '	href='' data-container='body' rel='tooltip' title='"._('Add new nested subnet')."' 		data-subnetId='$subnet[id]' data-action='add' data-id='' data-sectionId='$subnet[sectionId]'> <i class='fa fa-plus-circle'></i></a> ";
		} else {
		print "<a class='btn btn-xs btn-default disabled' 		href=''> 																																											  <i class='fa fa-plus-circle'></i></a> ";
		}
		# permissions
		if($sp['editperm'])
		print "<a class='showSubnetPerm btn btn-xs btn-default' href='' data-container='body' rel='tooltip' title='"._('Manage subnet permissions')."'	data-subnetId='$subnet[id]' data-sectionId='$subnet[sectionId]' data-action='show'>	<i class='fa fa-tasks'></i></a>";
		else
		print "<a class='btn btn-xs btn-default disabled' 		href='' data-container='body' rel='tooltip' title='"._('Manage subnet permissions')."'>																						<i class='fa fa-tasks'></i></a>";
		# linked
		if($sp['editperm'] && $type=="IPv4")
		print "<a class='editSubnetLink btn btn-xs btn-default' href='' data-container='body' rel='tooltip' title='"._('Manage linked IPv6 subnet')."'	data-subnetId='$subnet[id]' data-action='show'>	<i class='fa fa-link'></i></a>";
		else
		print "<a class='btn btn-xs btn-default disabled' 		href='' data-container='body' rel='tooltip' title='"._('Manage linked IPv6 subnet')."'>																						<i class='fa fa-link'></i></a>";
	print "</div>";

	# favourites / changelog
	print "<div class='btn-group'>";
		# favourite
		if($User->is_subnet_favourite($subnet['id']))
		print "<a class='btn btn-xs btn-default btn-info editFavourite favourite-$subnet[id]' href='' data-container='body' rel='tooltip' title='"._('Click to remove from favourites')."' data-subnetId='$subnet[id]' data-action='remove'>			<i class='fa fa-star'></i></a> ";
		else
		print "<a class='btn btn-xs btn-default editFavourite favourite-$subnet[id]' 		 href='' data-container='body' rel='tooltip' title='"._('Click to add to favourites')."' data-subnetId='$subnet[id]' data-action='add'>						<i class='fa fa-star fa-star-o' ></i></a> ";
		# changelog
		if($User->settings->enableChangelog==1) {
		if($sp['changelog'])
		print "<a class='sChangelog btn btn-xs btn-default'     									 href='".create_link("subnets",$subnet['sectionId'],$subnet['id'],"changelog")."' data-container='body' rel='tooltip' title='"._('Changelog')."'>	<i class='fa fa-clock-o'></i></a>";
		else
		print "<a class='btn btn-xs btn-default disabled'     									 	 href='' 																data-container='body' rel='tooltip' title='"._('Changelog')."'>				<i class='fa fa-clock-o'></i></a>";
		}
	print "</div>";

	# add / requests / scan
	if(!$slaves) {
	print "<div class='btn-group'>";
		// if full prevent new
		if($Subnets->reformat_number($subnet_usage['freehosts'])=="0" || !$sp['addip'])
		print "<a class='btn btn-xs btn-default btn-success disabled' 	href='' data-container='body' rel='tooltip' title='"._('Add new IP address')."'>															<i class='fa fa-plus'></i></a> ";
		else
		print "<a class='modIPaddr btn btn-xs btn-default btn-success' 	href='' data-container='body' rel='tooltip' title='"._('Add new IP address')."' data-subnetId='$subnet[id]' data-action='add' data-id=''>	<i class='fa fa-plus'></i></a> ";
		//requests
		if($subnet['allowRequests'] == 1  && $subnet_permission<3)  {
		print "<a class='request_ipaddress btn btn-xs btn-default btn-success' 	href='' data-container='body' rel='tooltip' title='"._('Request new IP address')."' data-subnetId='$subnet[id]'>					<i class='fa fa-plus-circle'>  </i></a>";
		}
		// subnet scan
		if($sp['scan'])
		print "<a class='scan_subnet btn btn-xs btn-default'			href='' data-container='body' rel='tooltip' title='"._('Scan subnet for new hosts')."' 	data-subnetId='$subnet[id]'> 						<i class='fa fa-cogs'></i></a> ";
		else
		print "<a class='btn btn-xs btn-default disabled'				href='' data-container='body' rel='tooltip' title='"._('Scan subnet for new hosts')."'> 													<i class='fa fa-cogs'></i></a> ";
	print "</div>";

	# export / import / shares / mail
	print "<div class='btn-group'>";
		//import
		if($sp['import'])
		print "<a class='csvImport btn btn-xs btn-default'  href='' data-container='body' rel='tooltip' title='"._('Import IP addresses')."' data-subnetId='$subnet[id]'>		<i class='fa fa-download'></i></a>";
		else
		print "<a class='btn btn-xs btn-default disabled'  	href='' data-container='body' rel='tooltip' title='"._('Import IP addresses')."'>									<i class='fa fa-download'></i></a>";
		//export
		print "<a class='csvExport btn btn-xs btn-default'  href='' data-container='body' rel='tooltip' title='"._('Export IP addresses')."' data-subnetId='$subnet[id]'>		<i class='fa fa-upload'></i></a>";
		//share
		if($subnet_permission>1 && $User->settings->tempShare==1) {
        print "<a class='btn btn-xs btn-default open_popup' data-script='app/tools/temp-shares/edit.php' data-class='700' data-action='edit' data-id='$subnet[id]' data-type='subnets' data-container='body' rel='tooltip' title='"._('Temporary share subnet')."'><i class='fa fa-share-alt'></i></a>";
		}
        print "<a class='mail_subnet btn btn-xs btn-default' href='#' data-id='$subnet[id]' rel='tooltip' data-container='body' title='' data-original-title='"._('Send mail notification')."'>          <i class='fa fa-gray fa-envelope-o'></i></a>";
	print "</div>";

		# firewall address object actions
		$firewallZoneSettings = db_json_decode($User->settings->firewallZoneSettings,true);
		if ( $User->settings->enableFirewallZones == 1 && $subnet_permission > 1) {
			print "<div class='btn-group'>";
			print "<a class='subnet_to_zone btn btn-xs btn-default".(($fwZone == false) ? '':' disabled')."' href='' data-container='body' rel='tooltip' title='"._('Map subnet to firewall zone')."' data-subnetId='$subnet[id]' data-operation='subnet2zone'><i class='fa fa-fire'></i></a>";
			print "<a class='fw_autogen btn btn-xs btn-default ".(($fwZone == false) ? 'disabled':'')."'  href='' data-container='body' rel='tooltip' title='"._('Generate or regenerate firewall address objects for all ip addresses within this subnet.')."' data-subnetid='$subnet[id]' data-action='net'>		<i class='fa fa-repeat'></i></a>";
			print "</div>";
		}
	}

	print "	</div>";
	print "	</td>";
	print "</tr>";
	?>

</table>
