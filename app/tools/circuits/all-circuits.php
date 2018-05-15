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
$custom_fields = $Tools->fetch_custom_fields('circuits');
# filter circuits or fetch print all?
$circuits = $Tools->fetch_all_circuits($custom_fields);

# strip tags - XSS
$_GET = $User->strip_input_tags ($_GET);

# title
print "<h4>"._('List of circuits')."</h4>";
print "<hr>";

# print link to manage
print "<div class='btn-group'>";
	// add
	if($User->is_admin(false) || $User->user->editCircuits=="Yes") {
    print "<a href='' class='btn btn-sm btn-default open_popup' data-script='app/admin/circuits/edit-circuit.php' data-class='700' data-action='add' data-circuitid='' style='margin-bottom:10px;'><i class='fa fa-plus'></i> "._('Add circuit')."</a>";
	}
print "</div>";

# table
print '<table id="circuitManagement" class="table sorted table-striped table-top" data-cookie-id-table="all_circuits">';

# headers
print "<thead>";
print '<tr>';
print "	<th><span rel='tooltip' data-container='body' title='"._('Sort by Id')."'>"._('Circuit ID')."</span></th>";
print "	<th><span rel='tooltip' data-container='body' title='"._('Sort by Provider')."'>"._('Provider')."</span></th>";
print "	<th><span rel='tooltip' data-container='body' title='"._('Sort by type')."'>"._('Type').'</span></th>';
print "	<th><span rel='tooltip' data-container='body' title='"._('Sort by Capacity')."' class='hidden-sm hidden-xs'>"._('Capacity').'</span></th>';
print "	<th><span rel='tooltip' data-container='body' title='"._('Sort by Capacity')."' class='hidden-sm hidden-xs'>"._('Status').'</span></th>';
print "	<th><span rel='tooltip' data-container='body' title='"._('Sort by location A')."' class='hidden-sm hidden-xs'>"._('Point A').'</span></th>';
print "	<th><span rel='tooltip' data-container='body' title='"._('Sort by location B')."' class='hidden-sm hidden-xs'>"._('Point B').'</span></th>';
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
		// reformat locations
		$locationA = $Tools->reformat_circuit_location ($circuit->device1, $circuit->location1);
		$locationA_html = "<span class='text-muted'>Not set</span>";
		if($locationA!==false) {
			$locationA_html = "<a href='".create_link($_GET['page'],$locationA['type'],$locationA['id'])."'>$locationA[name]</a> <i class='fa fa-gray $locationA[icon]'></i>";
		}

		$locationB = $Tools->reformat_circuit_location ($circuit->device2, $circuit->location2);
		$locationB_html = "<span class='text-muted'>Not set</span>";
		if($locationB!==false) {
			$locationB_html = "<a href='".create_link($_GET['page'],$locationB['type'],$locationB['id'])."'>$locationB[name]</a> <i class='fa fa-gray $locationB[icon]'></i>";
		}

		//print details
		print '<tr>'. "\n";
		print "	<td><a class='btn btn-xs btn-default' href='".create_link($_GET['page'],"circuits",$circuit->id)."'><i class='fa fa-random prefix'></i> $circuit->cid</a></td>";
		print "	<td class='description'><a href='".create_link($_GET['page'],"circuits","providers",$circuit->pid)."'>$circuit->name</a></td>";
		print "	<td>$circuit->type</td>";
		print " <td class='hidden-xs hidden-sm'>$circuit->capacity</td>";
		print " <td class='hidden-xs hidden-sm'>$circuit->status</td>";
		print "	<td class='hidden-xs hidden-sm'>$locationA_html</td>";
		print "	<td class='hidden-xs hidden-sm'>$locationB_html</td>";
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
		print "		<a class='btn btn-xs btn-default' href='".create_link($_GET['page'],"circuits",$circuit->id)."''><i class='fa fa-eye'></i></a>";
		if($User->is_admin(false) || $User->user->editCircuits=="Yes") {
		print "		<a class='btn btn-xs btn-default open_popup' data-script='app/admin/circuits/edit-circuit.php' data-class='700' data-action='edit' data-circuitid='$circuit->id'><i class='fa fa-pencil'></i></a>";
		print "		<a class='btn btn-xs btn-default open_popup' data-script='app/admin/circuits/edit-circuit.php' data-class='700' data-action='delete' data-circuitid='$circuit->id'><i class='fa fa-times'></i></a>";
		}
		print "	</div>";
		print "</td>";

		print '</tr>'. "\n";

	}
}

print '</table>';