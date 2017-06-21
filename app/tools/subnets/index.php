<?php

/**
 * print subnets
 */

# verify that user is logged in
$User->check_user_session();

# get all sections
$sections = $Sections->fetch_all_sections();

# get custom fields
$custom_fields = $Tools->fetch_custom_fields('subnets');

# set hidden fields
$hidden_fields = json_decode($User->settings->hiddenCustomFields, true);
$hidden_fields = is_array($hidden_fields['subnets']) ? $hidden_fields['subnets'] : array();

# title
print "<h4>"._('Available subnets')."</h4>";
print "<hr>";

# table
print "<table id='manageSubnets' class='table sorted table-striped table-condensed table-top table-absolute'>";

$section_count = 0;
# print vlans in each section
if ($sections!==false) {
    $m=0;

	# headers
	print "<thead>";
	print "	<tr>";
	print "	<th>"._('Subnet')."</th>";
	print "	<th>"._('Description')."</th>";
	print "	<th>"._('VLAN')."</th>";
	if($User->settings->enableVRF == 1) {
	print "	<th>"._('VRF')."</th>";
	}
	print "	<th>"._('Master Subnet')."</th>";
	print "	<th>"._('Device')."</th>";
	if($User->settings->enableIPrequests == 1) {
	print "	<th class='hidden-xs hidden-sm'>"._('Requests')."</th>";
	}
	if(sizeof($custom_fields) > 0) {
		foreach($custom_fields as $field) {
			# hidden?
			if(!in_array($field['name'], $hidden_fields)) {
				print "	<th class='hidden-xs hidden-sm hidden-md'>$field[name]</th>";
			}
		}
	}
	# actions
	print "<th class='actions' style='padding:0px;'></th>";
	print "</tr>";
	print "</thead>";


    print "<tbody>";
	// loop
	foreach ($sections as $section) {
		# cast
		$section = (array) $section;

		# check permission
		$permission = $Sections->check_permission ($User->user, $section['id']);
		if($permission > 0) {
			# set colspan
			$colSpan = 9 + (sizeof($custom_fields));

			# section names
			print "	<tr class='subnets-title'>";
			print "		<th colspan='$colSpan'><h4>$section[name] [$section[description]]</h4></th>";
			print "	</tr>";

			# get all subnets in section
			$subnets = $Subnets->fetch_section_subnets ($section['id']);

			# no subnets
			if(sizeof($subnets) == 0) {
				print "<tr><td colspan='$colSpan'>";
				$Result->show("info", _('Section has no subnets')."!", false);
				print "</td></tr>";
			}
			else {
				// print subnets, if none print no available
				if($Subnets->print_subnets_tools($User->user, $subnets, $custom_fields)===false) {
					print "<tr><td colspan='$colSpan'><div class='alert alert-info'>". _("No subnets available")."</td></tr>";
				}

				$section_count++;
			}
            # index count
            $m++;

		}	# end permission check
	}
	print "</tbody>";
}

# none
if ($section_count===0) {
	print "<tr><td colspan='$colSpan'>".$Result->show("info", _("No subnets available"), false)."</td></tr>";
}

?>
</table>