<script type="text/javascript">
/* fix for ajax-loading tooltips */
$('body').tooltip({ selector: '[rel=tooltip]' });
</script>

<?php

/**
 * Script to display circuits
 *
 */

# verify that user is logged in
$User->check_user_session();

# get custom fields
$custom_fields = $Tools->fetch_custom_fields('logicalCircuit');
# filter circuits or fetch print all?
$circuits = $Tools->fetch_all_logical_circuits($custom_fields);
$circuit_types = $Tools->fetch_all_circuit_types();
$type_hash = [];
foreach($circuit_types as $t){  $type_hash[$t->id] = $t->ctname; }

# strip tags - XSS
$_GET = $User->strip_input_tags ($_GET);

# title
print "<h4>"._('List of logical circuits')."</h4>";
print "<hr>";

# print link to manage
print "<div class='btn-group'>";
	// add
	if($User->is_admin(false) || $User->user->editCircuits=="Yes") {
    print "<a href='' class='btn btn-sm btn-default open_popup' data-script='app/admin/circuits/edit-logical-circuit.php' data-class='max' data-action='add' data-circuitid='' style='margin-bottom:10px;'><i class='fa fa-plus'></i> "._('Add logical circuit')."</a>";
	}
print "</div>";

# table
print '<table id="circuitManagement" class="table sorted table-striped table-top" data-cookie-id-table="all_logical_circuits">';

# headers
print "<thead>";
print '<tr>';
print "	<th><span rel='tooltip' data-container='body' title='"._('Sort by Id')."'>"._('Circuit ID')."</span></th>";
print "	<th><span rel='tooltip' data-container='body' title='"._('Sort by purpose')."'>"._('Purpose').'</span></th>';
print "	<th><span rel='tooltip' data-container='body' title='"._('Sort by count of circuits')."'>"._('Circuit Count').'</span></th>';
if(sizeof(@$custom_fields) > 0) {
	foreach($custom_fields as $field) {
		if(!in_array($field['name'], $hidden_circuit_fields)) {
			print "<th class='hidden-sm hidden-xs hidden-md'><span rel='tooltip' data-container='body' title='"._('Sort by')." ".$Tools->print_custom_field_name ($field['name'])."'>".$Tools->print_custom_field_name ($field['name'])."</th>";
			$colspanCustom++;
		}
	}
}
print '	<th class="actions"></th>';
print '</tr>';
print "</thead>";

// no circuits
if($circuits===false) {
	$colspan = 6 + $colspanCustom;
	print "<tr>";
	print "	<td colspan='$colspan'>".$Result->show('info', _('No results')."!", false, false, true)."</td>";
	print "</tr>";
}
// result
else {
	foreach ($circuits as $circuit) {
		//print details
		print '<tr>'. "\n";
		print "	<td><a class='btn btn-xs btn-default' href='".create_link($_GET['page'],"circuits",'logical',$circuit->id)."'><i class='fa fa-random prefix'></i> $circuit->logical_cid</a></td>";
		print "	<td>".$circuit->purpose."</td>";
		print "	<td>".$circuit->member_count."</td>";
		//custom
		if(sizeof(@$custom_fields) > 0) {
			foreach($custom_fields as $field) {
				if(!in_array($field['name'], $hidden_circuit_fields)) {
					// create html links
					$circuit->{$field['name']} = $User->create_links($circuit->{$field['name']}, $field['type']);

					print "<td class='hidden-xs hidden-sm hidden-md'>".$circuit->{$field['name']}."</td>";
				}
			}
		}


		// actions
		print "<td class='actions'>";
		print "	<div class='btn-group'>";
		print "		<a class='btn btn-xs btn-default' href='".create_link($_GET['page'],"circuits",'logical',$circuit->id)."''><i class='fa fa-eye'></i></a>";
		if($User->is_admin(false) || $User->user->editCircuits=="Yes") {
		print "		<a class='btn btn-xs btn-default open_popup' data-script='app/admin/circuits/edit-logical-circuit.php' data-class='max' data-action='edit' data-circuitid='$circuit->id'><i class='fa fa-pencil'></i></a>";
		print "		<a class='btn btn-xs btn-default open_popup' data-script='app/admin/circuits/edit-logical-circuit.php' data-class='700' data-action='delete' data-circuitid='$circuit->id'><i class='fa fa-times'></i></a>";
		}
		print "	</div>";
		print "</td>";

		print '</tr>'. "\n";

	}
}

print '</table>';
