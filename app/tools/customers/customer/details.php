<?php
if (!isset($User)) { exit(); }

/**
 * Script to display customer details
 *
 */

# verify that user is logged in
$User->check_user_session();
# verify module permissions
$User->check_module_permissions ("customers", User::ACCESS_R, true);


print "<h4>"._('Customer details')." - $customer->title</h4>";
print "<hr>";
print "<br>";

// back
print "<a class='btn btn-sm btn-default' href='".create_link($GET->page, "customers")."'><i class='fa fa-angle-left'></i> "._("All customers")."</a><br><br>";

# circuit
print "<table class='ipaddress_subnet table-condensed table-auto'>";

	// title
	print '<tr>';
	print "	<td colspan='2' style='font-size:18px'><strong>$customer->title</strong></td>";
	print "</tr>";

	// address
	print "<tr>";
	print "	<td colspan='2'><hr></td>";
	print "</tr>";

	print '<tr>';
	print "	<th>". _('Address').'</th>';
	print "	<td>$customer->address<br>$customer->postcode<br>$customer->city <br>$customer->state</td>";
	print "</tr>";

	// contact
	print "<tr>";
	print "	<td colspan='2'><hr></td>";
	print "</tr>";

	print '<tr>';
	print "	<td></td>";
	print "</tr>";

	print '<tr>';
	print "	<th class='text-right'>";
	print _("Contact details")."<br>";
	print "		<i class='fa fa-user'></i><br>";
	print "		<i class='fa fa-at'></i><br>";
	print "		<i class='fa fa-phone'></i>";
	print " </th>";
	print "	<td><br>";

	if(!is_blank($customer->contact_person))
	print $customer->contact_person."<br>";
	else
	print "/"."<br>";

	if(!is_blank($customer->contact_mail))
	print $customer->contact_mail."<br>";
	else
	print "/"."<br>";

	if(!is_blank($customer->contact_phone))
	print $customer->contact_phone."<br>";
	else
	print "/"."<br>";

	print "</td>";
	print "</tr>";

	if(sizeof($custom_fields) > 0) {

    	print "<tr>";
    	print "	<td colspan='2'><hr></td>";
    	print "</tr>";

		foreach($custom_fields as $field) {

			# fix for boolean
			if($field['type']=="tinyint(1)" || $field['type']=="boolean") {
				if($customer->{$field['name']}=="0")		{ $customer->{$field['name']} = "false"; }
				elseif($customer->{$field['name']}=="1")	{ $customer->{$field['name']} = "true"; }
				else									{ $customer->{$field['name']} = ""; }
			}

			# create links
			$customer->{$field['name']} = $Tools->create_links ($customer->{$field['name']});

			print "<tr>";
			print "<th>".$Tools->print_custom_field_name ($field['name'])."</th>";
			print "<td>".$customer->{$field['name']}."</d>";
			print "</tr>";
		}
	}

	// edit, delete
	if($User->get_module_permissions ("customers")>=User::ACCESS_RW) {
		print "<tr>";
		print "	<td colspan='2'><hr></td>";
		print "</tr>";

    	print "<tr>";
    	print "	<td></td>";

        // actions
        print "<td class='actions'>";
        $links = [];
        if($User->get_module_permissions ("customers")>=User::ACCESS_RW) {
            $links[] = ["type"=>"header", "text"=>_("Manage")];
            $links[] = ["type"=>"link", "text"=>_("Edit customer"), "href"=>"", "class"=>"open_popup", "dataparams"=>" data-script='app/admin/customers/edit.php' data-class='700' data-action='edit' data-id='$customer->id'", "icon"=>"pencil"];
        }
        if($User->get_module_permissions ("customers")>=User::ACCESS_RWA) {
            $links[] = ["type"=>"link", "text"=>_("Delete customer"), "href"=>"", "class"=>"open_popup", "dataparams"=>" data-script='app/admin/customers/edit.php' data-class='700' data-action='delete' data-id='$customer->id'", "icon"=>"times"];
        }
        // print links
        print $User->print_actions($User->user->compress_actions, $links, true, true);
        print "</td>";
    	print "</tr>";
	}

print "</table>";
