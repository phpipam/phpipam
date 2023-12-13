<?php

# verify that user is logged in
$User->check_user_session();

// perm check
if ($User->get_module_permissions ("vaults")==User::ACCESS_NONE) {
    $Result->show("danger", _("You do not have permissions to access this module"), false);
}
else {

	// title
	print "<h4>"._("Vaults")."</h4><hr>";

	// fetch vaults
	$all_vaults = $Tools->fetch_all_objects ("vaults");

	// get custom fields
	$custom_fields_v = $Tools->fetch_custom_fields('vaults');
	$colspan = 4 + sizeof(@$custom_fields_v);

	// create new Vault
	if ($User->get_module_permissions ("vaults")==User::ACCESS_RWA) {
		print "<button class='btn btn-sm btn-default open_popup' style='margin-bottom:10px;' data-script='app/admin/vaults/edit.php' data-class='700' data-action='add'><i class='fa fa-plus'></i> "._('Create Vault')."</button>";
	}


	// group by type
	$all_vault_types = ["certificates"=>[], "passwords"=>[]];
	if(is_array($all_vaults)) {
		foreach ($all_vaults as $v) {
			$all_vault_types[$v->type][] = $v;
		}
	}

	// print
	foreach ($all_vault_types as $key=>$all_vaults_grouped) {

		// title
		print "<h4 style='margin-top:30px;'>"._(ucwords($key))."</h4><hr>";

		print '<table id="userPrint" class="table sorted table-striped sorted tab1le-auto" data-cookie-id-table="admin_vaults">';
		# headers
		print "<thead>";
		print '<tr>';
	    print "<th data-width='300' data-width-unit='px'>"._('Name').'</th>';
	    print "<th data-width='120' data-width-unit='px'>"._('Status').'</th>';
		print "<th>"._('Description').'</th>';
		// custom
		if(sizeof(@$custom_fields_v) > 0) {
			foreach($custom_fields_v as $field) {
				print "	<th class='hidden-xs hidden-sm hidden-md'>".$Tools->print_custom_field_name ($field['name'])."</th>";
			}
		}
	    print '<th></th>';
		print '</tr>';
		print "</thead>";

		// icon
		if($key=="passwords")   { $icon = "fa fa-key"; }
		else 					{ $icon = "fa fa-certificate"; }

		# loop
		print "<tbody>";

		if(sizeof($all_vaults_grouped)>0) {
			foreach ($all_vaults_grouped as $a) {
				// set link
				if($User->Crypto->decrypt($a->test, $_SESSION['vault'.$a->id]) == "test") {
					$href = '<a class="btn btn-xs btn-success" href="'.create_link("tools", "vaults", $a->id).'">'.$a->name.'</a>';
					$status = "Unlocked";
				}
				else {
					$href = '<a class="btn btn-xs btn-default open_popup" data-script="app/admin/vaults/unlock.php" data-class="500" data-id="'.$a->id.'">'.$a->name.'</a>';
					$status = "Locked";
				}

				print '<tr class="text-top">';
				print '	<td><i class="'.$icon.'"></i> '.$href.'</td>';
				print '	<td>'._($status).'</td>';
				print '	<td class="text-muted">' . $a->description . '</td>'. "\n";

		        // custom fields
		        if(sizeof(@$custom_fields_v) > 0) {
			   		foreach($custom_fields_v as $field) {
						print "<td class='hidden-xs hidden-sm hidden-md'>";

						// fix for text
						if($status=="Unlocked") {
							if($field['type']=="text") { $field['type'] = "varchar(255)"; }
							$Tools->print_custom_field ($field['type'], $a->{$field['name']}, "\n", "<br>");
						}
						else {
							print "********";
						}
						print "</td>";
			    	}
			    }

				// add/remove vaults
				print "	<td class='actions'>";
				print "	<div class='btn-group'>";
				if($User->Crypto->decrypt($a->test, $_SESSION['vault'.$a->id]) == "test") {
					print "		<button class='btn btn-xs btn-success open_popup' data-script='app/admin/vaults/lock.php' data-class='500' data-id='{$a->id}' rel='tooltip' title='"._('Lock Vault')."'><i class='fa fa-lock'></i></button>";
				}
				else {
					print "		<button class='btn btn-xs btn-default open_popup' data-script='app/admin/vaults/unlock.php' data-class='500' data-id='{$a->id}' rel='tooltip' title='"._('Unlock Vault')."'><i class='fa fa-unlock'></i></button>";
				}
				print "		<button class='btn btn-xs btn-default open_popup' data-script='app/admin/vaults/edit.php' data-class='700' data-action='edit' data-id='{$a->id}' rel='tooltip' title='"._('Edit Vault details')."'><i class='fa fa-pencil'></i></button>";
				print "		<button class='btn btn-xs btn-default open_popup' data-script='app/admin/vaults/edit.php' data-class='700' data-action='delete' data-id='{$a->id}' rel='tooltip' title='"._('Remove Vault')."'><i class='fa fa-times'></i></button>";
				print "	</div>";
				print "</td>";

				print '</tr>' . "\n";
			}
		}
	else {
		print '<tr class="text-top">';
		print '	<td colspan="'.$colspan.'">'.$Result->show("info", _("No vaults created"), false, false, true).'</td>';
		print "</tr>";
	}

	print "</tbody>";
	print "</table>";
	}
}
