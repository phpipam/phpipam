<?php

/**
 * List of scanned networks
 */

# verify that user is logged in
$User->check_user_session();

# fetch all agents
$agents = $Subnets->fetch_scanning_agents ();

# title
print "<h4>"._('Scanned subnets summary')."</h4>";
print "<hr>";

# none
if ($agents===false) {
	$Result->show("info", _("No agents available"), false);
}
# print
else {

	# table
	print "<table class='table table-striped table-condensed table-top'>";

	print "<tr>";
	print "	<th>"._('Subnet')."</th>";
	print "	<th>"._('Description')."</th>";
	print "	<th>"._('Section')."</th>";
	print "	<th>"._('Hosts check')."</th>";
	print "	<th>"._('Discover')."</th>";
	print "	<th></th>";
	print "</tr>";

	// loop
	$ac = 0;

	foreach ($agents as $a) {

		$ac++;	// for ids

		print "<tr>";
		print "	<th colspan='6'>$ac.) $a->name ($a->description) :: $a->type</th>";
		print "</tr>";

		# fetch all scanned subnets
		$subnets = $Subnets->fetch_scanned_subnets($a->id);

		// count
		$cnt=0;

		//loop
		if($subnets!==false) {
			foreach($subnets as $subnet) {
				//fetch section
				$section = $Sections->fetch_section(null, $subnet->sectionId);
				//set hosts check
				$status_check = $subnet->pingSubnet==1 ? "<i class='fa fa-check'></i>" : "";
				//set discovery
				$discovery 	  = $subnet->discoverSubnet==1 ? "<i class='fa fa-check'></i>" : "";

				# check permission
				$permission = $Subnets->check_permission ($User->user, $subnet->id);

				# permission
				if($permission > 0) {
					$cnt++;
					# print
					print "<tr>";
					print "	<td><a href='".create_link("subnets", $section->id, $subnet->id)."'>".$Subnets->transform_to_dotted($subnet->subnet)."/$subnet->mask</a></td>";
					print "	<td>$subnet->description</td>";
					print "	<td>$section->name ($section->description)</td>";
					print "	<td>$status_check</td>";
					print "	<td>$discovery</td>";

					print "	<td class='actions' style='padding:0px;'>";
					print "	<div class='btn-group'>";
					print "		<button class='btn btn-xs btn-default editSubnet'     data-action='edit'   data-subnetid='".$subnet->id."'  data-sectionid='".$section->id."'><i class='fa fa-gray fa-pencil'></i></button>";
					print "		<button class='btn btn-xs btn-default editSubnet'     data-action='delete' data-subnetid='".$subnet->id."'  data-sectionid='".$section->id."'><i class='fa fa-gray fa-times'></i></button>";
					print "	</div>";
					print "	</td>";
					print "</tr>";
				}
			}
			# none available
			if ($cnt===0) {
				print "<tr>";
				print "	<td colspan=6>";
				$Result->show("info", _('No subnets available'), false);
				print "	</td>";
				print "</tr>";
			}
		}
		else {
			print "<tr>";
			print "	<td colspan=6>";
			$Result->show("info", _('No subnets'), false);
			print "	</td>";
			print "</tr>";
		}
	}

	print "</table>";
}
?>