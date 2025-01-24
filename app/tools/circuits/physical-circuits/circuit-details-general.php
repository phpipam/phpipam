<?php

# Check we have been included and not called directly
require( dirname(__FILE__) . '/../../../../functions/include-only.php' );

# perm check
$User->check_module_permissions ("circuits", User::ACCESS_R, true, false);

print "<h4>"._('Circuit details')."</h4>";
print "<hr>";

# circuit
print "<table class='ipaddress_subnet table-condensed table-auto'>";

// inactive status
$circuit->status = $circuit->status=="Inactive" ? "<span class='badge badge1 badge5 alert-danger'>"._("Inactive")."</span>" : $circuit->status;
$circuit->status = $circuit->status=="Active" ? "<span class='badge badge1 badge5 alert-success'>"._("Active")."</span>" : $circuit->status;
$circuit->status = $circuit->status=="Reserved" ? "<span class='badge badge1 badge5 alert-default'>"._("Reserved")."</span>" : $circuit->status;
$circuit_types = $Tools->fetch_all_objects ("circuitTypes", "ctname");
$type_hash = [];
foreach($circuit_types as $t){  $type_hash[$t->id] = $t->ctname; }

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
print "	<td>".$type_hash[$circuit->type]."</td>";
print "</tr>";

print '<tr>';
print "	<th>". _('Capacity').'</th>';
print "	<td>$circuit->capacity</td>";
print "</tr>";

print '<tr>';
print "	<th>". _('Comment').'</th>';
print "	<td>$circuit->comment</td>";
print "</tr>";

if ($User->settings->enableCustomers=="1") {
	$customer = $Tools->fetch_object ("customers", "id", $circuit->customer_id);
	if($customer===false) {
		$customer = new Params ();
		$customer->title = "/";
	}
print "	<th>". _('Customer').'</th>';
print "	<td>$customer->title <a target='_blank' href='".create_link("tools","customers",$customer->title)."'><i class='fa fa-external-link'></i></a></td>";
print "</tr>";
}


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
		$circuit->{$field['name']} = $Tools->create_links ($circuit->{$field['name']});

		print "<tr>";
		print "<th>".$Tools->print_custom_field_name ($field['name'])."</th>";
		print "<td>".$circuit->{$field['name']}."</d>";
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
	print "	<td class='actions'>";

        $links = [];
        $links[] = ["type"=>"header", "text"=>_("Manage circuit")];
        $links[] = ["type"=>"link", "text"=>_("Edit circuit"), "href"=>"", "class"=>"open_popup", "dataparams"=>" data-script='app/admin/circuits/edit-circuit.php' data-class='700' data-action='edit' data-circuitid='$circuit->id'", "icon"=>"pencil"];
        if($User->get_module_permissions ("circuits")>=User::ACCESS_RWA) {
            $links[] = ["type"=>"link", "text"=>_("Delete circuit"), "href"=>"", "class"=>"open_popup", "dataparams"=>"  data-script='app/admin/circuits/edit-circuit.php' data-class='700' data-action='delete' data-circuitid='$circuit->id'", "icon"=>"times"];
        }
        // print links
        print $User->print_actions($User->user->compress_actions, $links, true, true);
        print "</td>";

	print " </td>";
	print "</tr>";
}

print "</table>";
