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
$custom_fields = $Tools->fetch_custom_fields('circuitsLogical');
# filter circuits or fetch print all?
$circuits = $Tools->fetch_all_objects ("circuitsLogical", "logical_cid");
$circuit_types = $Tools->fetch_all_objects ("circuitTypes", "ctname");
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
	if($User->get_module_permissions ("circuits")>=User::ACCESS_RWA) {
    	print "<a href='' class='btn btn-sm btn-default open_popup' data-script='app/admin/circuits/edit-logical-circuit.php' data-class='700' data-action='add' data-circuitid='' style='margin-bottom:10px;'><i class='fa fa-plus'></i> "._('Add logical circuit')."</a>";
	}
print "</div>";

# table
print '<table id="userPrint" class="table sorted table-striped table-top" data-cookie-id-table="all_logical_circuits">';

# headers
print "<thead>";
print '<tr>';
print "	<th>"._('Circuit ID')."</th>";
print "	<th>"._('Purpose').'</th>';
print "	<th>"._('Circuit Count').'</th>';
print "	<th>"._('Members').'</th>';
print "	<th>"._('Comment').'</th>';
$colspanCustom = 0;
if(sizeof(@$custom_fields) > 0) {
	foreach($custom_fields as $field) {
		if(!in_array($field['name'], $hidden_circuit_fields)) {
			print "<th class='hidden-sm hidden-xs hidden-md'>".$Tools->print_custom_field_name ($field['name'])."</th>";
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
		print "<tr>";
		print "	<td style='vertical-align:top !important;'><a class='btn btn-xs btn-default' href='".create_link($_GET['page'],"circuits",'logical',$circuit->id)."'><i class='fa fa-random prefix'></i> $circuit->logical_cid</a></td>";
		print "	<td>".$circuit->purpose."</td>";
		print "	<td>".$circuit->member_count."</td>";
		// members
		print "	<td>";
		$member_circuits = $Tools->fetch_all_logical_circuit_members ($circuit->id);
		if($member_circuits!==false) {
			foreach ($member_circuits as $mc) {
				print "<a class='btn btn-xs btn-default' href='".create_link($_GET['page'],"circuits",$mc->id)."'><i class='fa fa-random prefix' style='border:none;'></i> $mc->cid</a><br>";
			}
		}
		else {
			print "<span class='text-muted'>"._("No members")."</span>";
		}
		print "	<td>".$circuit->comments."</td>";
		print "	</td>";
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
            $links[] = ["type"=>"link", "text"=>_("View"), "href"=>create_link($_GET['page'],"circuits","logical",$circuit->id), "icon"=>"eye", "visible"=>"dropdown"];
            $links[] = ["type"=>"divider"];
        }
        if($User->get_module_permissions ("circuits")>=User::ACCESS_RW) {
            $links[] = ["type"=>"header", "text"=>_("Manage circuit")];
            $links[] = ["type"=>"link", "text"=>_("Edit circuit"), "href"=>"", "class"=>"open_popup", "dataparams"=>" data-script='app/admin/circuits/edit-logical-circuit.php' data-class='700' data-action='edit' data-circuitid='$circuit->id'", "icon"=>"pencil"];
        }
        if($User->get_module_permissions ("circuits")>=User::ACCESS_RWA) {
            $links[] = ["type"=>"link", "text"=>_("Delete circuit"), "href"=>"", "class"=>"open_popup", "dataparams"=>"  data-script='app/admin/circuits/edit-logical-circuit.php' data-class='700' data-action='delete' data-circuitid='$circuit->id'", "icon"=>"times"];
        }
        // print links
        print $User->print_actions($User->user->compress_actions, $links);
        print "</td>";

		print '</tr>'. "\n";

	}
}
print '</table>';
