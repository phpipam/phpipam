<?php

/*
 * Script to display search results
 **********************************/

# verify that user is logged in
$User->check_user_session();

# get all custom fields
$custom_circuit_fields   = $GET->circuits=="on"  ? $Tools->fetch_custom_fields ("circuits") : array();
$custom_circuit_p_fields = $GET->circuits=="on"  ? $Tools->fetch_custom_fields ("circuitProviders") : array();

$hidden_circuit_fields   = is_array(@$hidden_fields['circuits']) ? $hidden_fields['circuits'] : array();
$hidden_circuit_p_fields = is_array(@$hidden_fields['circuitProviders']) ? $hidden_fields['circuitProviders'] : array();

# search circuits
$result_circuits   = $Tools->search_circuits ($searchTerm, $custom_circuit_fields);
$result_circuits_p = $Tools->search_circuit_providers ($searchTerm, $custom_circuit_p_fields);
?>


<!-- search result table -->
<br>
<h4><?php print _('Search results (Circuits)');?>:</h4>
<hr>

<table class="searchTable table sorted table-striped table-condensed table-top" data-cookie-id-table="search_circuits">

<!-- headers -->
<thead>
<tr id="searchHeader">
	<th><?php print _('Circuit ID');?></th>
	<th><?php print _('Provider');?></th>
	<th><?php print _('Type');?></th>
	<th><?php print _('Capacity');?></th>
	<th><?php print _('Status');?></th>
	<th><?php print _('Comment');?></th>
	<?php
	if(sizeof($custom_circuit_fields) > 0) {
		foreach($custom_circuit_fields as $field) {
			if(!in_array($field['name'], $hidden_circuit_fields)) {
				print "	<th class='hidden-xs hidden-sm'>".$Tools->print_custom_field_name ($field['name'])."</th>";
			}
		}
	}
	?>
	<th></th>
</tr>
</thead>

<tbody>
<?php
if(sizeof($result_circuits) > 0) {
	# print vlans
	foreach($result_circuits as $circuit) {
		print "<tr class='nolink'>";
		print " <td><dd><a class='btn btn-xs btn-default' href='".create_link("tools","circuits",$circuit->id)."'><i class='fa fa-random prefix'></i> $circuit->cid</a></dd></td>";
		print " <td><dd><a href='".create_link("tools","circuits","providers",$circuit->pid)."'>$circuit->name</a></dd></td>";
		print " <td><dd>$circuit->type</dd></td>";
		print " <td><dd>$circuit->capacity</dd></td>";
		print " <td><dd>$circuit->status</dd></td>";
		print " <td><dd>$circuit->comment</dd></td>";
		# custom fields
		if(sizeof($custom_circuit_fields) > 0) {
			foreach($custom_circuit_fields as $field) {
				if(!in_array($field['name'], $hidden_circuit_fields)) {
					$circuit->{$field['name']} = $Tools->create_links ($circuit->{$field['name']}, $field['type']);
					print "	<td class='hidden-xs hidden-sm'>".$circuit->{$field['name']}."</td>";
				}
			}
		}
		# for admins print link
		print " <td class='actions'>";
		if($User->is_admin(false)) {
		print '<div class="btn-group">';
		print "		<a class='btn btn-xs btn-default open_popup' data-script='app/admin/circuits/edit-circuit.php' data-class='700' data-action='edit' data-circuitid='$circuit->id'><i class='fa fa-pencil'></i></a>";
		print "		<a class='btn btn-xs btn-default open_popup' data-script='app/admin/circuits/edit-circuit.php' data-class='700' data-action='delete' data-circuitid='$circuit->id'><i class='fa fa-times'></i></a>";
		print '</div>';
		}
		print "</td>";
		print '</tr>'. "\n";
    }
}
?>
</tbody>
</table>
<?php
if(sizeof($result_circuits) == 0) {
	$Result->show("info", _("No results"), false);
}
?>




<!-- search result table -->
<br>
<h4><?php print _('Search results (Circuit Providers)');?>:</h4>
<hr>

<table class="searchTable sorted table table-striped table-condensed table-top" data-cookie-id-table="search_circuit_providers">

<!-- headers -->
<thead>
<tr id="searchHeader">
	<th><?php print _('Name');?></th>
	<th><?php print _('Description');?></th>
	<th><?php print _('Contact');?></th>
	<?php
	if(sizeof($custom_circuit_p_fields) > 0) {
		foreach($custom_circuit_p_fields as $field) {
			if(!in_array($field['name'], $hidden_circuit_p_fields)) {
				print "	<th class='hidden-xs hidden-sm'>".$Tools->print_custom_field_name ($field['name'])."</th>";
			}
		}
	}
	?>
</tr>
</thead>

<tbody>
<?php
if(sizeof($result_circuits_p) > 0) {
	# print vlans
	foreach($result_circuits_p as $provider) {
		print "<tr class='nolink'>";
		print " <td><dd><a class='btn btn-xs btn-default' href='".create_link("tools","circuits","providers",$provider->id)."'>$provider->name</a></dd></td>";
		print " <td><dd>$provider->description</dd></td>";
		print " <td><dd>$provider->contact</dd></td>";
		# custom fields
		if(sizeof($custom_circuit_p_fields) > 0) {
			foreach($custom_circuit_p_fields as $field) {
				if(!in_array($field['name'], $hidden_circuit_p_fields)) {
					$provider->{$field['name']} = $Tools->create_links ($provider->{$field['name']}, $field['type']);
					print "	<td class='hidden-xs hidden-sm'>".$provider->{$field['name']}."</td>";
				}
			}
		}
		print '</tr>'. "\n";
    }
}
?>
</tbody>
</table>
<?php
if(sizeof($result_circuits_p) == 0) {
	$Result->show("info", _("No results"), false);
}
