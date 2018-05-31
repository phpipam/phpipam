<?php

/**
 * Script to display available VLAN Groups
 */

# verify that user is logged in
$User->check_user_session();

$vlan_domain = $Tools->fetch_object("vlanDomains", "id", $_GET['subnetId']);
$vlanGroups= $Tools->fetch_multiple_objects("vlanGroups", "domainId", $_GET["subnetId"]);

print "<h4>"._('Available VLAN Groups in domain:')." $vlan_domain->name</h4>";
print "<hr>";
print "<div class='text-muted' style='padding-left:10px;'>".$vlan_domain->description."</div><hr>";
?>

<br>
<div class="btn-group" style="margin-bottom:10px;  float:left;">
    <?php
    // back
    if(sizeof($vlan_domains)>1) {
    print "<a class='btn btn-sm btn-default' href='".create_link($_GET['page'], $_GET['section'])."'><i class='fa fa-angle-left'></i> "._('L2 Domains')."</a>";
    }

    ?>
	<button class="btn btn-sm btn-default editVLANgroup" data-action="add" data-domain="<?php print $vlan_domain->id; ?>" style="margin-bottom:10px;"><i class="fa fa-plus"></i> <?php print _('Add VLAN Group'); ?></button>

<?php
# table
	print "</div><table class='table sorted vlans table-condensed table-top'>";

	# headers
	$n = @$n==1 ? 0 : 1;
	$class = $n==0 ? "odd" : "even";
	print "<thead>";
	print "<tr class='$class change'>" . "\n";
	print ' <th data-field="name" data-sortable="true">'._('Name').'</th>' . "\n";
	print ' <th data-field="first" data-sortable="true">'._('First VLAN').'</th>' . "\n";
	print ' <th data-field="last" data-sortable="true">'._('Last VLAN').'</th>' . "\n";
    print "<th></th>";
	print "</tr>";
	print "</thead>";

	print "<tbody>";

	// VLAN group - details
	$link = create_link($_GET['page'], $_GET['section'], "vlanGroup", "all", $_GET["subnetId"]);
	print "<tr class='$class change'>";
	print "	<td><a class='btn btn-xs btn-default' href=".'"'.$link.'"'.">"."<i class='fa fa-cloud prefix'></i> ". "All VLANs</a></td>";
	print "	<td>1</td>";
	print "	<td>".$User->settings->vlanMax."</td>";

	$m = 0;
	if ($vlanGroups) {
		foreach ($vlanGroups as $group) {
			//set odd / even
			$n = @$n==1 ? 0 : 1;
			$class = $n==0 ? "odd" : "even";
			// VLAN - details
			$link = create_link($_GET['page'], $_GET['section'], "vlanGroup", $group->id);
			print "<tr class='$class change'>";
			print "	<td><a href=".'"'.$link.'"'."'><i class='fa fa-cloud prefix'></i> ".$group->name." </a></td>";
			print "	<td>".$group->firstVlan."</td>";
			print "	<td>".$group->lastVlan."</td>";

			// VLAN - buttons
			if ($k==0) {
				print "	<td class='actions'>";
				print "	<div class='btn-group'>";
				print "		<button class='btn btn-xs btn-default editVLANgroup' data-action='edit'   data-id='$group->id' data-domain='$group->domainId'><i class='fa fa-pencil'></i></button>";
				print "		<button class='btn btn-xs btn-default editVLANgroup' data-action='delete' data-id='$group->id' data-domain='$group->domainId'><i class='fa fa-times'></i></button>";
				print "	</div>";
				print "	</td>";
			}
			else {
				print "<td></td>";
			}
		    print "</tr>";
		}
	}
	print "</tbody>";

	print '</table>';
 ?>
</div>
