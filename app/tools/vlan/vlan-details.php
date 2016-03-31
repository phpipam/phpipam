<?php
/**
 * Display VLAN details
 ***********************************************************************/

# verify that user is logged in
$User->check_user_session();

# get VLAN details
$vlan = (array) $Tools->fetch_object("vlans", "vlanId", $_GET['sPage']);

# fetch l2 domain
$vlan_domain = $Tools->fetch_object("vlanDomains", "id", $vlan['domainId']);
if($vlan_domain===false)			{ $Result->show("danger", _("Invalid ID"), true); }

# not existing
if($vlan[0]===false)				{ $Result->show("danger", _('Invalid VLAN id'), true); }

# get custom VLAN fields
$custom_fields = $Tools->fetch_custom_fields('vlans');
?>


<!-- subnet details upper table -->
<h4><?php print _('VLAN details'); ?></h4>
<hr>

<?php
print "<a class='btn btn-sm btn-default' href='".create_link($_GET['page'], $_GET['section'], $vlan_domain->id)."' data-action='add'  data-switchid='' style='margin-bottom:10px;'><i class='fa fa-chevron-left'></i> ". _('Back')."</a>";
?>

<table class="ipaddress_subnet table-condensed">
	<tr>
		<th><?php print _('Number'); ?></th>
		<td><?php print '<b>'. $vlan['number']; ?></td>
	</tr>
	<tr>
		<th><?php print _('Name'); ?></th>
		<td>
			<?php print $vlan['name']; ?>
		</td>
	</tr>
	<tr>
		<th><?php print _('l2 domain'); ?></th>
		<td><?php print $vlan_domain->name ?></td>
	</tr>
	<tr>
		<th><?php print _('Description'); ?></th>
		<td><?php print html_entity_decode($vlan['description']); ?></td>
	</tr>

	<?php
	/* print custom subnet fields if any */
	if(sizeof($custom_fields) > 0) {

		print "<tr>";
		print "	<td colspan='2'><hr></td>";
		print "</tr>";

		foreach($custom_fields as $key=>$field) {
			$vlan[$key] = str_replace("\n", "<br>",$vlan[$key]);

			# fix for boolean
			if($field['type']=="tinyint(1)" || $field['type']=="boolean") {
				if($vlan[$key]==0)		{ $vlan[$key] = "false"; }
				elseif($vlan[$key]==1)	{ $vlan[$key] = "true"; }
				else					{ $vlan[$key] = ""; }
			}

			// create links
			$vlan[$key] = $Result->create_links($vlan[$key]);

			print "<tr>";
			print "	<th>$key</th>";
			print "	<td style='vertical-align:top;align:left;'>$vlan[$key]</td>";
			print "</tr>";
		}
	}

	print "<tr>";
	print "	<td colspan='2'><hr></td>";
	print "</tr>";

	/* action button groups */
	print "<tr>";
	print "	<th style='vertical-align:bottom;align:left;'>"._('Actions')."</th>";
	print "	<td style='vertical-align:bottom;align:left;'>";

	print "	<div class='btn-toolbar' style='margin-bottom:0px'>";
	print "	<div class='btn-group'>";

	# permissions
	if($User->is_admin()==true) {
		print "		<button class='btn btn-xs btn-default editVLAN' data-action='edit'   data-vlanid='$vlan[vlanId]'><i class='fa fa-pencil'></i></button>";
        print "		<button class='btn btn-xs btn-default moveVLAN' 					 data-vlanid='$vlan[vlanId]'><i class='fa fa-external-link'></i></button>";
		print "		<button class='btn btn-xs btn-default editVLAN' data-action='delete' data-vlanid='$vlan[vlanId]'><i class='fa fa-times'></i></button>";
	}

	print "	</div>";
	print "	</div>";

	print "	</td>";
	print "</tr>";

	?>

</table>
<br>

<?php

# fetch all subnets belonging to this vlan
$subnets = $Subnets->fetch_vlan_subnets($vlan['vlanId']);

# subnet count
$scnt = 0 ;
# check each subnet
if($subnets!==false) {
	foreach ($subnets as $subnet) {
		# cast
		$subnet = (array) $subnet;
		# check permission
		$permission = $Subnets->check_permission ($User->user, $subnet['id']);
		# add to array if permitted
		if($permission > 0) {
			//add to cnt
			$scnt++;
			# fetch secton details
			$section = (array) $Sections->fetch_section(null, $subnet['sectionId']);

			$html[] =  "<tr>";
		    $html[] =  "<td class='small description'><a href='".create_link("subnets",$section['id'],$subnet['id'])."'>".$Subnets->transform_to_dotted($subnet['subnet'])."/$subnet[mask]</a></td>";
		    $html[] =  "<td>$subnet[description]</td>";

		    # section
		    $html[] =  "<td><a href='".create_link("subnets",$section['id'])."'>".$section['name']."</a></td>";

			# host check
			if($subnet['pingSubnet']==1) 		{ $html[] = '<td class="allowRequests small hidden-xs hidden-sm"><i class="fa fa-gray fa-check"></i></td>'; }
			else 								{ $html[] = '<td class="allowRequests small hidden-xs hidden-sm"></td>'; }

			# allow requests
			if($subnet['allowRequests'] == 1) 	{ $html[] = '<td class="allowRequests small hidden-xs hidden-sm"><i class="fa fa-gray fa-check"></i></td>'; }
			else 								{ $html[] = '<td class="allowRequests small hidden-xs hidden-sm"></td>'; }

			# edit
			if($permission == 3) {
				$html[] =  "<td class='actions'>";
				$html[] =  "	<div class='btn-group'>";
				$html[] =  "		<button class='btn btn-xs btn-default editSubnet'     data-action='edit'   data-subnetid='".$subnet['id']."'  data-sectionid='".$subnet['sectionId']."'><i class='fa fa-gray fa-pencil'></i></button>";
				$html[] =  "		<button class='btn btn-xs btn-default showSubnetPerm' data-action='show'   data-subnetid='".$subnet['id']."'  data-sectionid='".$subnet['sectionId']."'><i class='fa fa-gray fa-tasks'></i></button>";
				$html[] =  "		<button class='btn btn-xs btn-default editSubnet'     data-action='delete' data-subnetid='".$subnet['id']."'  data-sectionid='".$subnet['sectionId']."'><i class='fa fa-gray fa-times'></i></button>";
				$html[] =  "	</div>";
				$html[] =  "</td>";
			}
			else {
				$html[] =  "<td class='small actions'>";
				$html[] =  "	<div class='btn-group'>";
				$html[] =  "		<button class='btn btn-xs btn-default disabled'><i class='fa fa-gray fa-pencil'></i></button>";
				$html[] =  "		<button class='btn btn-xs btn-default disabled'><i class='fa fa-gray fa-tasks'></i></button>";
				$html[] =  "		<button class='btn btn-xs btn-default disabled'><i class='fa fa-gray fa-times'></i></button>";
				$html[] =  "	</div>";
				$html[] =  "</td>";
			}
			$html[] =  '</tr>' . "\n";
		}
	}
}

# print if some are present
if($scnt==0) {
	print "<br>";
	print "<h4>"._('VLAN')." "._('has no belonging subnets')."</h4><hr>";
}
else {
	# print title
	print "<br>";
	print "<h4>"._('VLAN')." "._('has')." ".sizeof($subnets)." "._('belonging subnets').":</h4><hr><br>";

	# print HTML tabl
	print '<table class="slaves table table-striped table-condensed table-hover table-full table-top">'. "\n";

	# headers
	print "<tr>";
	print "	<th class='small description'>"._('Subnet')."</th>";
	print "	<th>"._('Subnet description')."</th>";
	print "	<th>"._('Section')."</th>";
	print "	<th class='small hidden-xs hidden-sm'>"._('Hosts check')."</th>";
	print "	<th class='hidden-xs hidden-sm'>"._('Requests')."</th>";
	print " <th></th>";
	print "</tr>";

	# content
	print implode("\n", $html);

	print '</table>'. "\n";
}

?>