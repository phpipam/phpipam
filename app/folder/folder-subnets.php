<script type="text/javascript">
/* fix for ajax-loading tooltips */
$('body').tooltip({ selector: '[rel=tooltip]' });
</script>
<?php

/* Script to display all slave subnets in content div of subnets table! */

# verify that user is logged in
$User->check_user_session();

# must be numeric
if(!is_numeric($_GET['subnetId']))	{ $Result->show("danger", _("Invalid ID"), true); }
if(!is_numeric($_GET['section']))	{ $Result->show("danger", _("Invalid ID"), true); }

# set master folder ID to check for slaves
$folderId = $_GET['subnetId'];

# get section details
$section = $Sections->fetch_section ("id", $folder['sectionId']);

// init subnets
$subnets = array();

if($slaves) {
	# sort slaves by folder / subnet
	foreach($slaves as $s) {
		if($s->isFolder==1)		{ $folders[] = $s; }
		else 					{ $subnets[] = $s; }
	}

	# first print belonging folders
	if(isset($folders)) {
		# print title
		print "<h4>"._("Folder")." $folder[description] "._('has')." ". sizeof($folders)." "._('directly nested folders').":</h4><hr>";

		# table
		print '<table class="slaves table sorted table-striped table-condensed table-hover table-full table-top" style="margin-bottom:50px;" data-cookie-id-table="folder_subnets">'. "\n";
		# headers
		print "<thead>";
		print "<tr>";
		print "	<th class='small' style='width:55px;'></th>";
		print "	<th class='description'>"._('Folder')."</th>";
		print "</tr>";
		print "</thead>";

		# folders
		$m=0;
		print "<tbody>";
		foreach($folders as $f) {
			$f = (array) $f;
			# check permission
			$permission = $Subnets->check_permission ($User->user, $f['id']);
			if($permission > 0) {
				print "<tr>";
				print "	<td class='small'><i class='fa fa-folder fa-sfolder'></i></td>";
				print "	<td class='description'><a href='".create_link("folder",$section->id,$f['id'])."'> $f[description]</a></td>";
				print "</tr>";
				$m++;
			}
		}
		# no because of permissions
		if($m==0) {
			print "<tr>";
			print "<td colspan='2'>";
			$Result->show("info", _("Folder has no subfolders")."!", false);
			print "</td>";
			print "</tr>";
		}
		print "</tbody>";
		print "</table>";
	}
	# print subnets
	if(sizeof($subnets)>0) {
		# title
		print "<h4>"._("Folder")." $folder[description] "._('has')." ".sizeof($subnets)." "._('directly nested subnets').":</h4><hr><br>";

		# print table
		print '<table class="slaves table sorted table-striped table-condensed table-hover table-full table-top" data-cookie-id-table="folder_subnets_sorted">'. "\n";

		# headers
		print "<thead>";
		print "<tr>";
		print "	<th class='small'>"._('VLAN')."</th>";
		if($User->settings->enableVRF==1)
		print "	<th class='small'>"._('VRF')."</th>";
		print "	<th class='small description'>"._('Subnet description')."</th>";
		print "	<th>"._('Subnet')."</th>";
		print "	<th class='small hidden-xs hidden-sm'>"._('Used')."</th>";
		print "	<th class='small hidden-xs hidden-sm'>% "._('Free')."</th>";
		print "	<th class='small hidden-xs hidden-sm'>"._('Requests')."</th>";
		print " <th class='actions'></th>";
		print "</tr>";
		print "</thead>";

		# print slave subnets
		$m=0;
		print "<tbody>";
		foreach ($subnets as $slave) {
			# cast
			$slave = (array) $slave;
			# check permission
			$permission = $Subnets->check_permission ($User->user, $slave['id']);
			# allowed
			if($permission > 0) {
				# get VLAN details
				$vlan = $Tools->fetch_object("vlans", "vlanId",$slave['vlanId']);
				$vlan = (array) $vlan;
				# reformat empty VLAN
				if(sizeof($vlan)==1) { $vlan['number'] = "/"; }

				# get VRF details
				if($User->settings->enableVRF==1) {
					$vrf = $Tools->fetch_object("vrf", "vrfId", $slave['vrfId']);
					$vrf = (array) $vrf;
					# reformat empty VLAN
					if(sizeof($vrf)==1) { $vrf['name'] = "/"; }
				}

				// calculate usage
                $calculate  = $Subnets->calculate_subnet_usage ($slave, false);

				# add full information
                $fullinfo = $slave['isFull']==1 ? " <span class='badge badge1 badge2 badge4'>"._("Full")."</span>" : "";
                if ($slave['isFull']!==1) {
                    # if usage is 100%, fake usFull to true!
                    if ($calculate['freehosts']==0)  { $fullinfo = "<span class='badge badge1 badge2 badge4'>"._("Full")."</span>"; }
                }

				print "<tr>";
			    print "	<td class='small'>".$vlan['number']."</td>";
			    if($User->settings->enableVRF==1)
			    print "	<td class='small'>".$vrf['name']."</td>";

			    print "	<td class='small description'><a href='".create_link("subnets",$section->id,$slave['id'])."'>$slave[description]</a></td>";
			    print "	<td><a href='".create_link("subnets",$section->id,$slave['id'])."'>".$Subnets->transform_address($slave['subnet'], "dotted")."/$slave[mask] $fullinfo</a></td>";

				# print usage
			    print ' <td class="small hidden-xs hidden-sm">'. $calculate['used'] .'/'. $calculate['maxhosts'] .'</td>'. "\n";
			    print '	<td class="small hidden-xs hidden-sm">'. $calculate['freehosts_percent'] .'</td>';

				# allow requests
				if($slave['allowRequests'] == 1) 			{ print '<td class="allowRequests small hidden-xs hidden-sm"><i class="fa fa-gray fa-check"></i></td>'; }
				else 										{ print '<td class="allowRequests small hidden-xs hidden-sm"></td>'; }

				# edit buttons
				if($permission == 3) {
					print "	<td class='actions'>";
					print "	<div class='btn-group'>";
					print "		<button class='btn btn-xs btn-default editSubnet'     data-action='edit'   data-subnetid='".$slave['id']."'  data-sectionid='".$slave['sectionId']."'><i class='fa fa-gray fa fa-pencil'></i></button>";
					print "		<button class='btn btn-xs btn-default showSubnetPerm' data-action='show'   data-subnetid='".$slave['id']."'  data-sectionid='".$slave['sectionId']."'><i class='fa fa-gray fa fa-tasks'></i></button>";
					print "		<button class='btn btn-xs btn-default editSubnet'     data-action='delete' data-subnetid='".$slave['id']."'  data-sectionid='".$slave['sectionId']."'><i class='fa fa-gray fa fa-times'></i></button>";
					print "	</div>";
					print " </td>";
				}
				else {
					print "	<td class='actions'>";
					print "	<div class='btn-group'>";
					print "		<button class='btn btn-xs btn-default disabled'><i class='fa fa-gray fa fa-pencil'></i></button>";
					print "		<button class='btn btn-xs btn-default disabled'><i class='fa fa-gray fa fa-tasks'></i></button>";
					print "		<button class='btn btn-xs btn-default disabled'><i class='fa fa-gray fa fa-times'></i></button>";
					print "	</div>";
					print " </td>";
				}
				print '</tr>' . "\n";

				$m++;				//for count
			}
		}
		# no because of permissions
		if($m==0) {
			print "<tr>";
			print "<td colspan='7' class='visible-md visible-lg'>";
			print "<td colspan='4' class='visible-xs visible-sm'>";
			$Result->show("info", _("Folder has no belonging subnets")."!", false);
			print "</td>";
			print "</tr>";
		}
		print "</tbody>";
		print '</table>'. "\n";
	}
}
else {
	print "<hr>";
	$Result->show("info", _("Folder has no subfolders or belonging subnets")."!", false);
}
?>
