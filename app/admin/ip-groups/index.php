<?php

/**
 * Script to edit / add / delete scan agents
 *************************************************/

# verify that user is logged in
$User->check_user_session();

# fetch all APIs
$all_agents = $Admin->fetch_all_objects("ipGroups");
?>

<!-- display existing groups -->
<h4><?php print _('phpipam IP Groups'); ?></h4>
<hr><br>

<!-- Add new -->
<button class='btn btn-sm btn-default open_popup' style="margin-bottom:10px;" data-script='app/admin/ip-groups/edit.php' data-class='700' data-action='add'><i class='fa fa-plus'></i> <?php print _('Create new ip-group'); ?></button>


<?php
/* print existing APIs */
if($all_agents!==false) {

	print '<table id="userPrint" class="table sorted table-striped table-top table-auto nopagination nosearch" data-cookie-id-table="scanagents">';
	# headers
	print "<thead>";
	print '<tr>';
    print "<th>"._('id').'</th>';
	print "<th>"._('Name').'</th>';
    print "<th>"._('Description').'</th>';
    print "<th>"._('Type').'</th>';
    print "<th>"._('Last modified').'</th>';
    print '<th></th>';
	print '</tr>';
	print "</thead>";

	# loop
	print "<tbody>";
	foreach ($all_agents as $a) {
		//cast
		$a = (array) $a;

		// fixes
		$a['code'] = strlen($a['code'])===0 ? "/" : $a['code'];

		// print
		print '<tr>' . "\n";
		print '	<td>' . $a['id'] . '</td>'. "\n";
		print '	<td>' . $a['name'] . '</td>'. "\n";
		print '	<td>' . $a['description'] . '</td>'. "\n";
		print '	<td>' . $a['type'] . '</td>'. "\n";
		print '	<td>' . $a['last_modified'] . '</td>'. "\n";

		// add/remove ip groups
		print "	<td class='actions'>";
		print "	<div class='btn-group'>";
		print "		<a class='btn btn-xs btn-default open_popup' data-script='app/admin/ip-groups/edit.php' data-class='700' data-action='edit' data-id='$a[id]' rel='tooltip' title='"._('edit ip-groups details')."'><i class='fa fa-pencil'></i></a>";
		print "		<a class='btn btn-xs btn-default open_popup' data-script='app/admin/ip-groups/edit.php' data-class='700' data-action='delete' data-id='$a[id]' rel='tooltip' title='"._('remove ip-groups')."'><i class='fa fa-times'></i></a>";
		print "	</div>";
		print "</td>";

		print '</tr>' . "\n";
	}
	print "</tbody>";
	print "</table>";
}
else {
	$Result->show("info alert-nomargin", _("No ip groups available")."!", false);
}
?>
