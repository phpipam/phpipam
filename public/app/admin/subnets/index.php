<?php

/**
 * Script to print subnets
 ***************************/

# verify that user is logged in
$User->check_user_session();

# fetch all sections
$sections = $Sections->fetch_all_sections();

# print all sections with delete / edit button
print '<h4>'._('Subnet management').'</h4>';
print "<hr>";

/* Foreach section fetch subnets and print it! */
if(is_array($sections)) {
	foreach($sections as $section) {
		# check permission
		if($Sections->check_permission($User->user, $section->id)) {
			print "<br><br><h4>"._('Available subnets in section')." $section->name: [$section->description]</h4>";
			print $Sections->print_section_subnets_table($User, $section->id);
		}
	}
}
