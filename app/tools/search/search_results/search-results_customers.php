<?php

/*
 * Script to display search results
 **********************************/

# verify that user is logged in
$User->check_user_session();

# get all custom fields
$custom_customers_fields = $GET->customers=="on"     ? $Tools->fetch_custom_fields ("customers") : array();
$hidden_customer_fields = is_array(@$hidden_fields['customers']) ? $hidden_fields['customers'] : array();

# search cusotmers
$result_customers = $Tools->search_customers ($searchTerm, $custom_customers_fields);
?>

<br>
<h4><?php print _('Search results (Customers)');?>:</h4>
<hr>


<?php

// no customers
if($result_customers===false) {
	$Result->show("info", _("No results"), false);
}
// result
else {

	# table
	print '<table class="searchTable table sorted table-striped table-top" data-cookie-id-table="customers">';

	# headers
	print "<thead>";
	print '<tr>';
	print "	<th>"._('Title')."</th>";
	print "	<th>"._('Address').'</th>';
	print "	<th>"._('Contact').'</th>';
	if(sizeof(@$custom_customers_fields) > 0) {
		foreach($custom_customers_fields as $field) {
			if(!in_array($field['name'], $hidden_fields)) {
				print "<th class='hidden-sm hidden-xs hidden-md'>".$Tools->print_custom_field_name ($field['name'])."</th>";
				$colspanCustom++;
			}
		}
	}
	print '	<th class="actions"></th>';
	print '</tr>';
	print "</thead>";

	foreach ($result_customers as $customer) {
		// print details
		print '<tr>'. "\n";
		print "	<td><strong><a class='btn btn-sm btn-default' href='".create_link($Params->page,"customers",$customer->title)."'>$customer->title</a></strong></td>";
		print "	<td>$customer->address, $customer->postcode $customer->city, $customer->state</td>";
		// contact
		if(!is_blank($customer->contact_person))
		print " <td><a href='mailto:$customer->contact_mail'>$customer->contact_person</a> ($customer->contact_phone)</td>";
		else
		print " <td><span class='muted'>/</span></td>";
		// custom
		if(sizeof(@$custom_customers_fields) > 0) {
			foreach($custom_customers_fields as $field) {
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
            $links[] = ["type"=>"link", "text"=>_("Show customer"), "href"=>create_link($Params->page, "customers", $customer->title), "icon"=>"eye", "visible"=>"dropdown"];
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

	print '</table>';
}