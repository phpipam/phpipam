<?php

# Check we have been included and not called directly
require( dirname(__FILE__) . '/../../../../functions/include-only.php' );

?>
<script>
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
# perm check
$User->check_module_permissions ("circuits", User::ACCESS_R, true, false);

# get custom fields
$custom_fields = $Tools->fetch_custom_fields('circuits');
# filter circuits or fetch print all?
$circuits = $Tools->fetch_all_circuits($custom_fields);
$circuit_types = $Tools->fetch_all_objects ("circuitTypes", "ctname");
$type_hash = [];
foreach($circuit_types as $t){  $type_hash[$t->id] = $t->ctname; }

# strip tags - XSS
$_GET = $User->strip_input_tags ($_GET);

# title
print "<h4>"._('List of physical circuits')."</h4>";
print "<hr>";

# print link to manage
print "<div class='btn-group'>";
	// add
	if($User->get_module_permissions ("circuits")>=User::ACCESS_RWA) {
    print "<a href='' class='btn btn-sm btn-default open_popup' data-script='app/admin/circuits/edit-circuit.php' data-class='700' data-action='add' data-circuitid='' style='margin-bottom:10px;'><i class='fa fa-plus'></i> "._('Add circuit')."</a>";
	}
print "</div>";

# table
print '<table id="circuitManagement" class="table sorted table-striped table-top" data-cookie-id-table="all_circuits">';

# headers
print "<thead>";
print '<tr>';
print "	<th>"._('Circuit ID')."</th>";
print "	<th>"._('Provider')."</th>";
if($User->settings->enableCustomers=="1")
print "	<th>"._('Customer').'</th>';
print "	<th>"._('Type').'</th>';
print "	<th><span class='hidden-sm hidden-xs'>"._('Capacity').'</span></th>';
print "	<th><span class='hidden-sm hidden-xs'>"._('Status').'</span></th>';
print "	<th><span class='hidden-sm hidden-xs'>"._('Point A').'</span></th>';
print "	<th><span class='hidden-sm hidden-xs'>"._('Point B').'</span></th>';
print "	<th><span class='hidden-sm hidden-xs'>"._('Comment').'</span></th>';
$colspanCustom = 0;
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
		$locationA_html = "<span class='text-muted'>"._("Not set")."</span>";
		if($locationA!==false) {
			$locationA_html = "<a href='".create_link($_GET['page'],$locationA['type'],$locationA['id'])."'>$locationA[name]</a> <i class='fa fa-gray $locationA[icon]'></i>";
		}

		$locationB = $Tools->reformat_circuit_location ($circuit->device2, $circuit->location2);
		$locationB_html = "<span class='text-muted'>"._("Not set")."</span>";
		if($locationB!==false) {
			$locationB_html = "<a href='".create_link($_GET['page'],$locationB['type'],$locationB['id'])."'>$locationB[name]</a> <i class='fa fa-gray $locationB[icon]'></i>";
		}

		//print details
		print '<tr>'. "\n";
		print "	<td><a class='btn btn-xs btn-default' href='".create_link($_GET['page'],"circuits",$circuit->id)."'><i class='fa fa-random prefix'></i> $circuit->cid</a></td>";
		print "	<td class='description'><a href='".create_link($_GET['page'],"circuits","providers",$circuit->pid)."'>$circuit->name</a></td>";
		// customers
		if($User->settings->enableCustomers=="1") {
			 $customer = $Tools->fetch_object ("customers", "id", $circuit->customer_id);
			 print $customer===false ? "<td></td>" : "<td>".$customer->title." <a target='_blank' href='".create_link("tools","customers",$customer->title)."'><i class='fa fa-external-link'></i></a></td>";
		}
		print "	<td>".$type_hash[$circuit->type]."</td>";
		print " <td class='hidden-xs hidden-sm'>$circuit->capacity</td>";
		print " <td class='hidden-xs hidden-sm'>$circuit->status</td>";
		print "	<td class='hidden-xs hidden-sm'>$locationA_html</td>";
		print "	<td class='hidden-xs hidden-sm'>$locationB_html</td>";
		print " <td class='hidden-xs hidden-sm'>$circuit->comment</td>";
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
        $links = [];
        if($User->get_module_permissions ("circuits")>=User::ACCESS_R) {
            $links[] = ["type"=>"header", "text"=>_("Show circuit")];
            $links[] = ["type"=>"link", "text"=>_("View"), "href"=>create_link($_GET['page'], "circuits", $circuit->id), "icon"=>"eye", "visible"=>"dropdown"];
            $links[] = ["type"=>"divider"];
        }
        if($User->get_module_permissions ("circuits")>=User::ACCESS_RW) {
            $links[] = ["type"=>"header", "text"=>_("Manage circuit")];
            $links[] = ["type"=>"link", "text"=>_("Edit circuit"), "href"=>"", "class"=>"open_popup", "dataparams"=>" data-script='app/admin/circuits/edit-circuit.php' data-class='700' data-action='edit' data-circuitid='$circuit->id'", "icon"=>"pencil"];
        }
        if($User->get_module_permissions ("circuits")>=User::ACCESS_RWA) {
            $links[] = ["type"=>"link", "text"=>_("Delete circuit"), "href"=>"", "class"=>"open_popup", "dataparams"=>"  data-script='app/admin/circuits/edit-circuit.php' data-class='700' data-action='delete' data-circuitid='$circuit->id'", "icon"=>"times"];
        }
        // print links
        print $User->print_actions($User->user->compress_actions, $links);
        print "</td>";


		print '</tr>'. "\n";

	}
}

print '</table>';
