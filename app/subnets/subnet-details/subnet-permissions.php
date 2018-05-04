<h4><?php print _('Permissions'); ?></h4>
<hr>

<?php
# verify that user is logged in
$User->check_user_session();

# user admin
$User->is_admin();

# get groups
$groups = $Tools->fetch_all_objects ("userGroups", "g_name");

// show permissions
if ($groups!==false) {
	# parse permissions
	if(strlen($subnet['permissions'])>1)    { $s_permissons = $Sections->parse_section_permissions($subnet['permissions']); }
	else 								    { $s_permissons = ""; }

    print "<table class='ipaddress_subnet table-condensed table-auto'>";

	# print each group
    foreach ($groups as $g) {
		//cast
		$g = (array) $g;

		print "<tr>";
		print "	<th>$g[g_name]</th>";
		print "	<td>";
        print $Subnets->parse_permissions(@$s_permissons[$g['g_id']]);
		print "	</td>";
		print "</tr>";
    }

    # manage
    print "<tr>";
    print " <td colspan='2'><hr></td>";
    print "</tr>";

    print "<tr>";
    print " <td></td>";
    print " <td>";
    print " <a class='showSubnetPerm btn btn-sm btn-default' href='' data-subnetid='$subnet[id]' data-sectionid='$subnet[sectionId]' data-action='show'><i class='fa fa-tasks'></i> "._("Manage subnet permissions")."</a>";
    print " </td>";
    print "</tr>";

    print "</table>";
}
else {
    $Result->show("info", _('No groups available'));
}
?>