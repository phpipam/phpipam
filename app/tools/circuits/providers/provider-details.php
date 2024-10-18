<?php

# Check we have been included and not called directly
require( dirname(__FILE__) . '/../../../../functions/include-only.php' );

/**
 * Script to display providers
 */

# verify that user is logged in
$User->check_user_session();
# perm check
$User->check_module_permissions ("circuits", User::ACCESS_R, true, false);

# check
is_numeric($GET->sPage) ? : $Result->show("danger", _("Invalid ID"), true);

# fetch provider
$provider = $Tools->fetch_object ("circuitProviders", "id", $GET->sPage);
$circuit_types = $Tools->fetch_all_objects ("circuitTypes", "ctname");
$type_hash = [];
foreach($circuit_types as $t){ $type_hash[$t->id] = $t->ctname; }

// print back link
print "<div class='btn-group'>";
print "<a class='btn btn-sm btn-default' href='".create_link("tools","circuits","providers")."' style='margin-bottom:10px;'><i class='fa fa-angle-left'></i> ". _('All providers')."</a>";
print "</div>";

# print
if($provider!==false) {
	// get custom fields
	$custom_fields = $Tools->fetch_custom_fields('circuitProviders');
	$custom_fields_circuits = $Tools->fetch_custom_fields('circuits');
	$colspanCustom = 0;
	
	// details
	print "<div class='col-xs-12'>";

	// title
	print "<h4>"._('Provider details')."</h4>";
	print "<hr>";

    # provider
	print "<table class='ipaddress_subnet table-condensed table-auto'>";

    	print '<tr>';
    	print "	<th>". _('Name').'</th>';
    	print "	<td><strong>$provider->name</strong></td>";
    	print "</tr>";

    	print '<tr>';
    	print "	<th>". _('Description').'</th>';
    	print "	<td>$provider->description</td>";
    	print "</tr>";

    	print '<tr>';
    	print "	<th>". _('Contact').'</th>';
    	print "	<td>$provider->contact</td>";
    	print "</tr>";

    	if(sizeof($custom_fields) > 0) {

	    	print "<tr>";
	    	print "	<td colspan='2'><hr></td>";
	    	print "</tr>";

    		foreach($custom_fields as $field) {
    			# fix for boolean
    			if($field['type']=="tinyint(1)" || $field['type']=="boolean") {
    				if($provider->{$field['name']}=="0")		{ $provider->{$field['name']} = "false"; }
    				elseif($provider->{$field['name']}=="1")	{ $provider->{$field['name']} = "true"; }
    				else										{ $provider->{$field['name']} = ""; }
    			}

    			# create links
    			$provider->{$field['name']} = $Tools->create_links ($provider->{$field['name']});

    			print "<tr>";
    			print "<th>".$Tools->print_custom_field_name ($field['name'])."</th>";
    			print "<td>".$provider->{$field['name']}."</d>";
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
            $links[] = ["type"=>"header", "text"=>_("Manage provider")];
            $links[] = ["type"=>"link", "text"=>_("Edit provider"), "href"=>"", "class"=>"open_popup", "dataparams"=>"  data-script='app/admin/circuits/edit-provider.php' data-class='700' data-action='edit' data-providerid='$provider->id'", "icon"=>"pencil"];
	        if($User->get_module_permissions ("circuits")>=User::ACCESS_RWA) {
	            $links[] = ["type"=>"link", "text"=>_("Delete provider"), "href"=>"", "class"=>"open_popup", "dataparams"=>"  data-script='app/admin/circuits/edit-provider.php' data-class='700' data-action='delete' data-providerid='$provider->id'", "icon"=>"times"];
	        }
	        // print links
	        print $User->print_actions($User->user->compress_actions, $links, true, true);

    		print " </td>";
	    	print "</tr>";
    	}

    print "</table>";
    print "</div>";


    // circuits
	print "<div class='col-xs-12' style='margin-top:20px;'>";

	# title
	print "<h4>"._('Provider circuits')."</h4>";
	print "<hr>";

	// fetch circuits
	$provider_circuits = $Tools->fetch_all_provider_circuits ($provider->id, $custom_fields_circuits);

	// print
	if($provider_circuits===false) {
		$Result->show("info", _("No circuits"), false);
	}
	else {
		# table
		print '<table id="circuitManagement" class="table sorted table-striped table-top" data-cookie-id-table="circu_prov_details">';

		# headers
		print "<thead>";
		print '<tr>';
		print "	<th>"._('Circuit ID')."</th>";
		print "	<th>"._('Provider')."</th>";
		print "	<th>"._('Type').'</th>';
		print "	<th>"._('Capacity').'</th>';
		print "	<th>"._('Status').'</th>';
		print "	<th>"._('Point A').'</th>';
		print "	<th>"._('Point B').'</th>';
		if(sizeof(@$custom_fields_circuits) > 0) {
			foreach($custom_fields_circuits as $field) {
				if(!in_array($field['name'], $hidden_circuit_fields)) {
					print "<th>".$Tools->print_custom_field_name ($field['name'])."</th>";
					$colspanCustom++;
				}
			}
		}
		print '	<th class="actions"></th>';
		print '</tr>';
		print "</thead>";

		foreach ($provider_circuits as $circuit) {
			// reformat locations
			$locationA = $Tools->reformat_circuit_location ($circuit->device1, $circuit->location1);
			$locationA_html = "<span class='text-muted'>"._("Not set")."</span>";
			if($locationA!==false) {
				$locationA_html = "<a href='".create_link($GET->page,$locationA['type'],$locationA['id'])."'>$locationA[name]</a> <i class='fa fa-gray $locationA[icon]'></i>";
			}

			$locationB = $Tools->reformat_circuit_location ($circuit->device2, $circuit->location2);
			$locationB_html = "<span class='text-muted'>"._("Not set")."</span>";
			if($locationB!==false) {
				$locationB_html = "<a href='".create_link($GET->page,$locationB['type'],$locationB['id'])."'>$locationB[name]</a> <i class='fa fa-gray $locationB[icon]'></i>";
			}

			//print details
			print '<tr>'. "\n";
			print "	<td><a class='btn btn-xs btn-default' href='".create_link($GET->page,"circuits",$circuit->id)."'><i class='fa fa-random prefix'></i> $circuit->cid</a></td>";
			print "	<td>$circuit->name</td>";
			print "	<td>{$type_hash[$circuit->type]}</td>";
			print " <td class='hidden-xs hidden-sm'>$circuit->capacity</td>";
			print " <td class='hidden-xs hidden-sm'>$circuit->status</td>";
			print "	<td class='hidden-xs hidden-sm'>$locationA_html</td>";
			print "	<td class='hidden-xs hidden-sm'>$locationB_html</td>";
			//custom
			if(sizeof(@$custom_fields_circuits) > 0) {
				foreach($custom_fields_circuits as $field) {
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
	            $links[] = ["type"=>"link", "text"=>_("View"), "href"=>create_link($GET->page, "circuits", $circuit->id), "icon"=>"eye", "visible"=>"dropdown"];
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

		print '</table>';
	}

    print "</div>";

}
else {
	$Result->show("danger", _("Invalid provider id"), true);
}
