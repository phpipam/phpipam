<?php

# Check we have been included and not called directly
require( dirname(__FILE__) . '/../../../../functions/include-only.php' );

# perm check
$User->check_module_permissions ("circuits", User::ACCESS_R, true, false);


print "<h4>"._('Logical circuit details')."</h4>";
print "<hr>";

# logic circuit
print "<table class='ipaddress_subnet table-condensed table-auto'>";

	print '<tr>';
	print "	<th>". _('Logical Circuit ID').'</th>';
	print "	<td><strong>$logical_circuit->logical_cid</strong></td>";
	print "</tr>";

	print '<tr>';
	print "	<th>". _('Purpose').'</th>';
	print "	<td>$logical_circuit->purpose</td>";
	print "</tr>";

	print '<tr>';
	print "	<th>". _('Comment').'</th>';
	print "	<td>$logical_circuit->comments</td>";
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
			$logical_circuit->{$field['name']} = $Tools->create_links ($logical_circuit->{$field['name']});

			print "<tr>";
			print "<th>".$Tools->print_custom_field_name ($field['name'])."</th>";
			print "<td>".$logical_circuit->{$field['name']}."</d>";
			print "</tr>";
		}
	}

	// edit, delete
	if($User->get_module_permissions ("circuits")>=User::ACCESS_RW) {
		print "<tr>";
		print "	<td colspan='2'><hr></td>";
		print "</tr>";

    	print "<tr>";
    	print "	<td></td>";
		// actions
		print "<td class='actions'>";
        $links = [];
        $links[] = ["type"=>"header", "text"=>"Manage circuit"];
        $links[] = ["type"=>"link", "text"=>"Edit circuit", "href"=>"", "class"=>"open_popup", "dataparams"=>" data-script='app/admin/circuits/edit-logical-circuit.php' data-class='700' data-action='edit' data-circuitid='$logical_circuit->id'", "icon"=>"pencil"];
        if($User->get_module_permissions ("circuits")>=User::ACCESS_RWA) {
            $links[] = ["type"=>"link", "text"=>"Delete circuit", "href"=>"", "class"=>"open_popup", "dataparams"=>"  data-script='app/admin/circuits/edit-logical-circuit.php' data-class='700' data-action='delete' data-circuitid='$logical_circuit->id'", "icon"=>"times"];
        }
        // print links
        print $User->print_actions($User->user->compress_actions, $links, true, true);
        print "</td>";
    	print "</tr>";
	}

print "</table>";
