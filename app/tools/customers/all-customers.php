<script>
/* fix for ajax-loading tooltips */
$('body').tooltip({ selector: '[rel=tooltip]' });
</script>

<?php

/**
 * Script to display all customers
 *
 */

# verify that user is logged in
$User->check_user_session();
# verify module permissions
$User->check_module_permissions ("customers", User::ACCESS_R, true);
# filter customers or fetch print all?
$customers = $Tools->fetch_all_objects("customers", "title");

# strip tags - XSS
$_GET = $User->strip_input_tags ($_GET);

# get custom fields
$custom_fields = $Tools->fetch_custom_fields('customers');
# get hidden fields */
$hidden_fields = pf_json_decode($User->settings->hiddenCustomFields, true);
$hidden_fields = is_array(@$hidden_fields['customers']) ? $hidden_fields['customers'] : array();

$colspanCustom = 0;

# title
print "<h4>"._('All customers')."</h4>";
print "<hr>";

# print link to manage
print "<div class='btn-group'>";
	// add
	if($User->get_module_permissions("customers")>=User::ACCESS_RWA) {
    print "<a href='' class='btn btn-sm btn-default open_popup' data-script='app/admin/customers/edit.php' data-class='700' data-action='add' data-id='' style='margin-bottom:10px;'><i class='fa fa-plus'></i> "._('Add customer')."</a>";
	}
print "</div>";

# table
print '<table id="customers" class="table sorted table-striped table-top" data-cookie-id-table="customers">';

# headers
print "<thead>";
print '<tr>';
print "	<th>"._('Title')."</th>";
print "	<th>"._('Address').'</th>';
print "	<th>"._('Contact').'</th>';
if(sizeof(@$custom_fields) > 0) {
	foreach($custom_fields as $field) {
		if(!in_array($field['name'], $hidden_fields)) {
			print "<th class='hidden-sm hidden-xs hidden-md'>".$Tools->print_custom_field_name ($field['name'])."</th>";
			$colspanCustom++;
		}
	}
}
print '	<th class="actions"></th>';
print '</tr>';
print "</thead>";

// no customers
if($customers===false) {
	$colspan = 4 + $colspanCustom;
	print "<tr>";
	print "	<td colspan='$colspan'>".$Result->show('info', _('No results')."!", false, false, true)."</td>";
	print "</tr>";
}
// result
else {
	foreach ($customers as $customer) {
		// print details
		print '<tr>'. "\n";
		print "	<td><strong><a class='btn btn-sm btn-default' href='".create_link($_GET['page'],"customers",$customer->title)."'>$customer->title</a></strong></td>";
		print "	<td>$customer->address, $customer->postcode $customer->city, $customer->state</td>";
		// contact
		if(!is_blank($customer->contact_person))
		print " <td><a href='mailto:$customer->contact_mail'>$customer->contact_person</a> ($customer->contact_phone)</td>";
		else
		print " <td><span class='muted'>/</span></td>";
		// custom
		if(sizeof(@$custom_fields) > 0) {
			foreach($custom_fields as $field) {
				if(!in_array($field['name'], $hidden_fields)) {
					// create html links
					$customer->{$field['name']} = $User->create_links($customer->{$field['name']}, $field['type']);

					print "<td class='hidden-sm hidden-xs hidden-md'>".$customer->{$field['name']}."</td>";
				}
			}
		}

        // actions
        print "<td class='actions'>";
        $links = [];
        if($User->get_module_permissions ("customers")>=User::ACCESS_R) {
            $links[] = ["type"=>"header", "text"=>_("Show")];
            $links[] = ["type"=>"link", "text"=>_("Show customer"), "href"=>create_link($_GET['page'], "customers", $customer->title), "icon"=>"eye", "visible"=>"dropdown"];
            $links[] = ["type"=>"divider"];
        }
        if($User->get_module_permissions ("customers")>=User::ACCESS_RW) {
            $links[] = ["type"=>"header", "text"=>_("Manage")];
            $links[] = ["type"=>"link", "text"=>_("Edit customer"), "href"=>"", "class"=>"open_popup", "dataparams"=>" data-script='app/admin/customers/edit.php' data-class='700' data-action='edit' data-id='$customer->id'", "icon"=>"pencil"];
        }
        if($User->get_module_permissions ("customers")>=User::ACCESS_RWA) {
            $links[] = ["type"=>"link", "text"=>_("Delete customer"), "href"=>"", "class"=>"open_popup", "dataparams"=>" data-script='app/admin/customers/edit.php' data-class='700' data-action='delete' data-id='$customer->id'", "icon"=>"times"];
        }
        // print links
        print $User->print_actions($User->user->compress_actions, $links);
        print "</td>";

		print '</tr>';
	}
}

print '</table>';
