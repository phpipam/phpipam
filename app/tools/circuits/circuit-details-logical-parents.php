<?php

# title
print "<h4>"._('This circuit belongs to the following logical circuits')."</h4>";
print "<hr>";

# circuit
print "<table class='ipaddress_subnet table-condensed table-auto'>";


if(sizeof($logical_circuits) > 0){
  foreach($logical_circuits as $lc){
    print '<tr>';
		print "	<td><a class='btn btn-xs btn-default' href='".create_link($_GET['page'],"circuits",'logical',$lc->id)."'><i class='fa fa-random prefix'></i> $lc->logical_cid</a></td>";
		print " <td>$lc->purpose</td>";
		print "</tr>";
	}
}
print "</table>";



/*
	print '<tr>';
	print "	<th>". _('').'</th>';
	print "	<td><a href='".create_link("tools","circuits","providers",$provider->id)."'>$provider->name</a></td>";
	print "</tr>";

	print '<tr>';
	print "	<th>". _('Description').'</th>';
	print "	<td>$provider->description </td>";
	print "</tr>";

	print '<tr>';
	print "	<th>". _('Provider contact').'</a></th>';
	print "	<td>$provider->contact</td>";
	print "</tr>";

	if(sizeof($custom_provider_fields) > 0) {

    	print "<tr>";
    	print "	<td colspan='2'><hr></td>";
    	print "</tr>";

		foreach($custom_provider_fields as $field) {

			# fix for boolean
			if($field['type']=="tinyint(1)" || $field['type']=="boolean") {
				if($provider->{$field['name']}=="0")		{ $provider->{$field['name']} = "false"; }
				elseif($provider->{$field['name']}=="1")	{ $provider->{$field['name']} = "true"; }
				else										{ $provider->{$field['name']} = ""; }
			}

			# create links
			$provider->{$field['name']} = $Result->create_links ($provider->{$field['name']});

			print "<tr>";
			print "<th>".$Tools->print_custom_field_name ($field['name'])."</th>";
			print "<td>".$provider->{$field['name']}."</d>";
			print "</tr>";
		}
	}
*/
