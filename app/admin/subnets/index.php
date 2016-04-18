<?php

/**
 * Script to print subnets
 ***************************/

# verify that user is logged in
$User->check_user_session();

# fetch custom fields
$custom_fields = $Tools->fetch_custom_fields('subnets');

# fetch all sections
$sections = $Sections->fetch_all_sections();

# set hidden fields
$hidden_custom_fields = json_decode($User->settings->hiddenCustomFields, true);
$hidden_custom_fields = is_array(@$hidden_custom_fields['subnets']) ? $hidden_custom_fields['subnets'] : array();

# print all sections with delete / edit button
print '<h4>'._('Subnet management').'</h4>';
print "<hr>";

/* Foreach section fetch subnets and print it! */
if(sizeof($sections) > 0) {

	# print  table structure
	print "<table id='manageSubnets' class='table sorted table-striped table-condensed table-top'>";

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
	print "	<th class='hidden-xs hidden-sm'>"._('Requests')."</th>";

	if(sizeof($custom_fields) > 0) {
		foreach($custom_fields as $field) {
			# hidden?
			if(!in_array($field['name'], $hidden_custom_fields)) {
				print "	<th class='hidden-xs hidden-sm hidden-md'>$field[name]</th>";
			}
		}
	}
	# actions
	print "<th class='actions' style='padding:0px;'></th>";
	print "</tr>";
	print "</thead>";


	$m = 0;	//for subnet index

    print "<tbody>";
	# print titles and content
	if($sections!==false) {
		foreach($sections as $section) {
			//cast
			$section = (array) $section;
			# set colcount
			$colCount = $User->settings->enableVRF==1 ? 10 : 9;

			# just for count
			if(sizeof($custom_fields) > 0) {
				foreach($custom_fields as $field) {
					if(!in_array($field['name'], $hidden_custom_fields)) {
						$colCount++;
					}
				}
			}

			# print name
			print "<tr class='subnet-title'>";
			print "	<th colspan='$colCount'>";
			print "		<h4> $section[name] </h4>";
			print "	</th>";
			print "</tr>";

			# get all subnets in section
			$section_subnets = $Subnets->fetch_section_subnets($section['id']);

			# add new link
			print "<tr>";
			print "	<td colspan='$colCount'>";
			print "		<button class='btn btn-sm btn-default editSubnet' data-action='add' data-sectionid='$section[id]' data-subnetId='' rel='tooltip' data-placement='right' title='"._('Add new subnet to section')." $section[name]'><i class='fa fa-plus'></i> "._('Add subnet')."</button>";
			print "	</td>";
			print "	</tr>";

			# no subnets
			if(sizeof($section_subnets) == 0) {
				print "<tr><td colspan='$colCount'><div class='alert alert-info'>"._('Section has no subnets')."!</div></td></tr>";
			}
			else {
				# subnets
				$Subnets->print_subnets_tools($User->user, $section_subnets, $custom_fields);
			}
			$m++;
		}
	}
	print "</tbody>";

	# end master table
	print "</table>";
}
?>
