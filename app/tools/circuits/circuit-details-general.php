<?php

print "<h4>"._('Circuit details')."</h4>";
print "<hr>";

# circuit
print "<table class='ipaddress_subnet table-condensed table-auto'>";

	// inactive status
	$circuit->status = $circuit->status=="Inactive" ? "<span class='badge badge1 badge5 alert-danger'>$circuit->status</span>" : $circuit->status;
	$circuit->status = $circuit->status=="Active" ? "<span class='badge badge1 badge5 alert-success'>$circuit->status</span>" : $circuit->status;
	$circuit->status = $circuit->status=="Reserved" ? "<span class='badge badge1 badge5 alert-default'>$circuit->status</span>" : $circuit->status;

	print '<tr>';
	print "	<th>". _('Circuit ID').'</th>';
	print "	<td><strong>$circuit->cid</strong></td>";
	print "</tr>";

	print '<tr>';
	print "	<th>". _('Provider').'</th>';
	print "	<td><a href='".create_link("tools","circuits","providers",$provider->id)."'>$provider->name</a></td>";
	print "</tr>";

	print '<tr>';
	print "	<th>". _('Status').'</th>';
	print "	<td>$circuit->status</td>";
	print "</tr>";

	print '<tr>';
	print "	<th>". _('Type').'</th>';
	print "	<td>$circuit->type</td>";
	print "</tr>";

	print '<tr>';
	print "	<th>". _('Capacity').'</th>';
	print "	<td>$circuit->capacity</td>";
	print "</tr>";

	if(sizeof($custom_fields) > 0) {

    	print "<tr>";
    	print "	<td colspan='2'><hr></td>";
    	print "</tr>";

		foreach($custom_fields as $field) {

			# fix for boolean
			if($field['type']=="tinyint(1)" || $field['type']=="boolean") {
				if($circuit->{$field['name']}=="0")		{ $circuit->{$field['name']} = "false"; }
				elseif($circuit->{$field['name']}=="1")	{ $circuit->{$field['name']} = "true"; }
				else									{ $circuit->{$field['name']} = ""; }
			}

			# create links
			$circuit->{$field['name']} = $Result->create_links ($circuit->{$field['name']});

			print "<tr>";
			print "<th>".$Tools->print_custom_field_name ($field['name'])."</th>";
			print "<td>".$circuit->{$field['name']}."</d>";
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
		print "		<a class='btn btn-xs btn-default open_popup' data-script='app/admin/circuits/edit-circuit.php' data-class='700' data-action='edit' data-circuitid='$circuit->id'><i class='fa fa-pencil'></i></a>";
		print "		<a class='btn btn-xs btn-default open_popup' data-script='app/admin/circuits/edit-circuit.php' data-class='700' data-action='delete' data-circuitid='$circuit->id'><i class='fa fa-times'></i></a>";
		print "	</div>";
		print " </td>";
    	print "</tr>";
	}

print "</table>";