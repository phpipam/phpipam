<!-- subnet details upper table -->
<h4><?php print _('Subnet details'); ?></h4>
<hr>

<table class="ipaddress_subnet table-condensed table-full">
	<tr>
		<th><?php print _('Subnet details'); ?></th>
		<td><?php print "<b>$subnet[ip]/$subnet[mask]</b> ($subnet_detailed[netmask])"; ?></td>
	</tr>
	<tr>
		<th><?php print _('Hierarchy'); ?></th>
		<td>
			<?php $Subnets->print_breadcrumbs($Sections, $Subnets, array("page"=>"subnets", "section"=>$subnet['sectionId'], "subnetId"=>$subnet['id'])); ?>
		</td>
	</tr>
	<tr>
		<th><?php print _('Subnet description'); ?></th>
		<td><?php print escape_input($subnet['description']); ?></td>
	</tr>
	<tr>
		<th><?php print _('Permission'); ?></th>
		<td><?php print $Subnets->parse_permissions($subnet_permission); ?></td>
	</tr>
	<?php if(!$slaves) { ?>
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
	<tr>
		<th><?php print _('VLAN'); ?></th>
		<td>
		<?php
		if(empty($vlan['number']) || $vlan['number'] == 0) { $vlan['number'] = "<span class='text-muted'>/</span>"; }	//Display fix for emprt VLAN
		print $vlan['number'];

		if(!empty($vlan['name'])) 		 { print ' - '.$vlan['name']; }					//Print name if provided
		if(!empty($vlan['description'])) { print ' ['. $vlan['description'] .']'; }		//Print description if provided
		?>
		</td>
	</tr>

	<?php
	# VRF
	if(!empty($subnet['vrfId']) && $settings->enableVRF==1) {
		# get vrf details
		$vrf = (array) $Tools->fetch_object ("vrf", "vrfId" ,$subnet['vrfId']);
		# set text
		$vrfText = $vrf['name'];
		if(!empty($vrf['description'])) { $vrfText .= " [$vrf[description]]";}

		print "<tr>";
		print "	<th>"._('VRF')."</th>";
		print "	<td>$vrfText</td>";
		print "</tr>";
	}

	if(!$slaves) {
		# divider
		print "<tr>";
		print "	<th><hr></th>";
		print "	<td></td>";
		print "</tr>";

		# Are IP requests allowed?
		if ($settings->enableIPrequests==1) {
			print "<tr>";
			print "	<th>"._('IP requests')."</th>";
			if($subnet['allowRequests'] == 1) 		{ print "	<td>"._('enabled')."</td>"; }		# yes
			else 									{ print "	<td class='info2'>"._('disabled')."</td>";}		# no
			print "</tr>";
		}
		# ping-check hosts inside subnet
		print "<tr>";
		print "	<th>"._('Hosts check')."</th>";
		if($subnet['pingSubnet'] == 1) 				{ print "	<td>"._('enabled')."</td>"; }		# yes
		else 										{ print "	<td class='info2'>"._('disabled')."</td>";}		# no
		print "</tr>";
		# scan subnet for new hosts *
		print "<tr>";
		print "	<th>"._('Discover new hosts')."</th>";
		if($subnet['discoverSubnet'] == 1) 			{ print "	<td>"._('enabled')."</td>"; }		# yes
		else 										{ print "	<td class='info2'>"._('disabled')."</td>";}		# no
		print "</tr>";
	}
	?>
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
					$html_custom[] = $subnet[$key];
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
			print "	<td></td>";
			print "</tr>";

			print implode("\n", $html_custom);
		}
	}
	print "<tr>";
	print "<td colspan=2><hr></td>";
	print "</tr>";

	?>

</table>