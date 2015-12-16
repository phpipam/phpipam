<?php

/**
 * Script to edit / add / delete scan agents
 *************************************************/

# verify that user is logged in
$User->check_user_session();

# fetch all APIs
$all_agents = $Admin->fetch_all_objects("scanAgents");
?>

<!-- display existing groups -->
<h4><?php print _('phpipam Scan agents'); ?></h4>
<hr><br>

<!-- Add new -->
<button class='btn btn-sm btn-default editAgent' style="margin-bottom:10px;" data-action='add'><i class='fa fa-plus'></i> <?php print _('Create new agent'); ?></button>


<?php
/* print existing APIs */
if($all_agents!==false) {

	print '<table id="userPrint" class="table table-striped table-top table-auto">';
	# headers
	print '<tr>';
    print "<th>"._('Agent id').'</th>';
	print "<th>"._('Name').'</th>';
    print "<th>"._('Description').'</th>';
    print "<th>"._('Type').'</th>';
    print "<th>"._('Code').'</th>';
    print "<th>"._('Last access').'</th>';
    print '<th></th>';
	print '</tr>';

	# loop
	foreach ($all_agents as $a) {
		//cast
		$a = (array) $a;

		// fixes
		$a['code'] = strlen($a['code'])===0 ? "/" : $a['code'];
		$a['last_access'] = strlen($a['last_access'])===0 ? "<span class='text-muted'>Never</span>" : $a['last_access'];

		// print
		print '<tr>' . "\n";
		print '	<td>' . $a['id'] . '</td>'. "\n";
		print '	<td>' . $a['name'] . '</td>'. "\n";
		print '	<td>' . $a['description'] . '</td>'. "\n";
		print '	<td>' . $a['type'] . '</td>'. "\n";
		print '	<td>' . $a['code'] . '</td>'. "\n";
		print '	<td>' . $a['last_access'] . '</td>'. "\n";

		// add/remove agents
		print "	<td class='actions'>";
		print "	<div class='btn-group'>";
		print "		<button class='btn btn-xs btn-default editAgent'  data-container='body' data-id='$a[id]' data-action='edit'   rel='tooltip' title='"._('edit agent details')."'>	<i class='fa fa-pencil'></i></button>";
		print "		<button class='btn btn-xs btn-default editAgent'  data-container='body' data-id='$a[id]' data-action='delete' rel='tooltip' title='"._('remove agent')."'>		<i class='fa fa-times'></i></button>";
		print "	</div>";
		print "</td>";

		print '</tr>' . "\n";
	}
	print "</table>";
}
else {
	$Result->show("info alert-nomargin", _("No agents available")."!", false);
}
?>
<hr>

<h4><?php print _('phpipam-agent documentation'); ?></h4>
<a href="https://github.com/phpipam/phpipam-agent">https://github.com/phpipam/phpipam-agent</a>
