<?php

print "<h4>"._('Logical circuit details')."</h4>";
print "<hr>";

# circuit
print "<table class='ipaddress_subnet table-condensed table-auto'>";

	// inactive status
	/*
	$circuit->status = $circuit->status=="Inactive" ? "<span class='badge badge1 badge5 alert-danger'>$circuit->status</span>" : $circuit->status;
	$circuit->status = $circuit->status=="Active" ? "<span class='badge badge1 badge5 alert-success'>$circuit->status</span>" : $circuit->status;
	$circuit->status = $circuit->status=="Reserved" ? "<span class='badge badge1 badge5 alert-default'>$circuit->status</span>" : $circuit->status;
	$circuit_types = $Tools->fetch_all_circuit_types();
	$type_hash = [];
	foreach($circuit_types as $t){  $type_hash[$t->id] = $t->ctname; }
*/
	print '<tr>';
	print "	<th>". _('Logical Circuit ID').'</th>';
	print "	<td><strong>$logical_circuit->logical_cid</strong></td>";
	print "</tr>";

	print '<tr>';
	print "	<th>". _('Purpose').'</th>';
	print "	<td>$logical_circuit->purpose</td>";
	print "</tr>";

  /* Maybe put in a calculated cost value here */


	if(sizeof($custom_fields) > 0) {

    	print "<tr>";
    	print "	<td colspan='2'><hr></td>";
    	print "</tr>";

		foreach($custom_fields as $field) {

			# fix for boolean
			if($field['type']=="tinyint(1)" || $field['type']=="boolean") {
				if($logical_circuit->{$field['name']}=="0")		{ $logical_circuit->{$field['name']} = "false"; }
				elseif($logical_circuit->{$field['name']}=="1")	{ $logical_circuit->{$field['name']} = "true"; }
				else									{ $logical_circuit->{$field['name']} = ""; }
			}

			# create links
			$logical_circuit->{$field['name']} = $Result->create_links ($logical_circuit->{$field['name']});

			print "<tr>";
			print "<th>".$Tools->print_custom_field_name ($field['name'])."</th>";
			print "<td>".$logical_circuit->{$field['name']}."</d>";
			print "</tr>";
		}
	}


	// edit, delete
	if($User->is_admin(false) || $User->user->editCircuits=="Yes") {
		print "<tr>";
		print "	<td colspan='2'><hr></td>";
		print "</tr>";

    	print "<tr>";
    	print "	<td></td>";
		print "	<td class='actions'>";
		print "	<div class='btn-group'>";
		print "		<a class='btn btn-xs btn-default open_popup' data-script='app/admin/circuits/edit-logical-circuit.php' data-class='max' data-action='edit' data-circuitid='$logical_circuit->id'><i class='fa fa-pencil'></i></a>";
		print "		<a class='btn btn-xs btn-default open_popup' data-script='app/admin/circuits/edit-logical-circuit.php' data-class='700' data-action='delete' data-circuitid='$logical_circuit->id'><i class='fa fa-times'></i></a>";
		print "	</div>";
		print " </td>";
    	print "</tr>";
	}

print "</table>";
