<?php
# verify that user is logged in
$User->check_user_session();

# user admin
$User->is_admin();

# get groups
$groups = $Tools->fetch_all_objects ("userGroups", "g_name");

# parse permissions
$s_permissions = pf_json_decode($subnet['permissions']);

// title
print "<h4>"._('Address permissions').":</h4><hr>";

// show permissions
if ($groups!==false) {
	# parse permissions
	if(strlen($subnet['permissions'])>1)    { $permissons = $Sections->parse_section_permissions($subnet['permissions']); }
	else 								    { $permissons = ""; }

    print "<table class='ipaddress_subnet table-condensed table-auto'>";

	# print each group
    foreach ($groups as $g) {
		//cast
		$g = (array) $g;

		print "<tr>";
		print "	<th>$g[g_name]</th>";
		print "	<td>";
        print $Subnets->parse_permissions(@$permissons[$g['g_id']]);
		print "	</td>";
		print "</tr>";
    }

    print "</table>";
}
else {
    $Result->show("info", _('No groups available'));
}
?>