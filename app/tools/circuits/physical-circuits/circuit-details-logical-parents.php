<?php

# Check we have been included and not called directly
require( dirname(__FILE__) . '/../../../../functions/include-only.php' );

# perm check
$User->check_module_permissions ("circuits", User::ACCESS_R, true, false);

# title
print "<h4>"._('Logical circuits')."</h4>";
print "<hr>";
# circuit
if($logical_circuits!==false){

	print "<span class='text-muted'>"._("This circuit is member of the following logical circuits").":</span>";

	# table
	print '<table class="table sorted table-striped table-top" data-cookie-id-table="all_logical_circuits">';
	# headers
	print "<thead>";
	print '<tr>';
	print "	<th>"._('Circuit ID')."</th>";
	print "	<th>"._('Purpose').'</th>';
	print "	<th>"._('Circuit Count').'</th>';
	print '</tr>';
	print "</thead>";

	print "<tbody>";
	foreach ($logical_circuits as $circuit) {
		//print details
		print '<tr>'. "\n";
		print "	<td><a class='btn btn-xs btn-default' href='".create_link($GET->page,"circuits",'logical',$circuit->id)."'><i class='fa fa-random prefix'></i> $circuit->logical_cid</a></td>";
		print "	<td>".$circuit->purpose."</td>";
		print "	<td>".$circuit->member_count."</td>";
		print '</tr>'. "\n";

	}
	print "</tbody>";
	print "</table>";
}
else {
	$Result->show("info", _("This circuit is not a member of any logical circuit."));
}