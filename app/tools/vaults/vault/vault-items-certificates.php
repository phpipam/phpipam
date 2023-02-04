<?php

# verify that user is logged in
$User->check_user_session();

# fetch items
$certificates_db = $Tools->fetch_multiple_objects ("vaultItems", "vaultId", $_GET['subnetId'], 'id', false);


// create new item
if ($User->get_module_permissions ("vaults")>=User::ACCESS_RW) {
	print "<button class='btn btn-sm btn-default open_popup' style='margin-bottom:10px;' data-script='app/admin/vaults/edit-item-certificate.php' data-class='700' data-action='add' data-vaultId='{$vault->id}'><i class='fa fa-plus'></i> "._('Import certificate')."</button>";
}

# get custom fields
$custom_fields = $Tools->fetch_custom_fields('vaultItems');
# size of custom fields
$csize = sizeof($custom_fields) + 9;

# printout
print "<table class='table sorted table-striped sorted table-certificates' data-cookie-id-table='vault-certificates'>";

// headers
print "<thead>";
print "	<th>"._("Name")."</th>";
print "	<th>"._("Key")."</th>";
print "	<th>"._("Status")."</th>";
print "	<th>"._("Name")."</th>";
print "	<th class='hidden-sm'>"._("Expires")."</th>";
print "	<th class='hidden-sm'>"._("Issuer")."</th>";
print "	<th class='hidden-sm'>"._("Alt names")."</th>";
// custom
if(sizeof(@$custom_fields) > 0) {
	foreach($custom_fields as $field) {
		print "	<th class='hidden-xs hidden-sm hidden-md'>".$Tools->print_custom_field_name ($field['name'])."</th>";
	}
}
print "	<th style='width:50px;'></th>";
print "</thead>";

print "<tbody>";
if($certificates_db!==false) {
	// loop
	foreach ($certificates_db as $p) {
		// decrypt values
		$values = $User->Crypto->decrypt($p->values, $_SESSION["vault".$vault->id]);
		// check
		if($values===false || $values===NULL) {
			// print
			print "<tr class='alert-danger'>";
			print "	<td colspan='7'><strong>"._("Cannot decrypt")."</strong></td>";
			print "</tr>";
		}
		else {
			$values = pf_json_decode($values, true);
			$trclass = "";
			// parse certificate
			$certificate = openssl_x509_parse(base64_decode($values['certificate']));

			// if($values['name']=="pkey test") {
			// 	print "<pre>";
			// 	print_r($certificate);
			// }

			// validity
			$validFrom = date("Y-m-d H:i:s", $certificate['validFrom_time_t']);
			$validTo   = date("Y-m-d H:i:s", $certificate['validTo_time_t']);
			$valid_days = round((strtotime(date("Y-m-d", $certificate['validTo_time_t']))-time())/ (60 * 60 * 24));


			// warning class
			// status
			if($valid_days<0)  		{ $status = "<span class='badge alert-danger'>"._("Expired")."</span>"; $warning = "danger";  $warningIcon = "<i class='fa fa-warning'></i>"; }
			elseif($valid_days<30) 	{ $status = "<span class='badge alert-warning'>"._("Warning")."</span>"; $warning = "warning"; $warningIcon = "<i class='fa fa-warning'></i>"; }
			else 					{ $status = "<span class='badge alert-success'>"._("OK")."</span>"; $warning = ""; $warningIcon = ""; }

			// pkey
			$pkey = openssl_get_privatekey(base64_decode($values['certificate']))===false ? "-" : "<i class='fa fa-key' rel='tooltip' title='"._("Certificate has private key")."'></i>";

			// print
			print "<tr class='text-top $warning'>";
			print "	<td><strong><a href='".create_link("tools","vaults",$vault->id, $p->id)."'>".$values['name']."</a> $warningIcon</strong></td>";
			print "	<td>"._($pkey)."</td>";
			print "	<td>$status</td>";
			print "	<td>".$certificate['subject']['CN']."</td>";
			print "	<td>".$validTo." ($valid_days days)</td>";
			print "	<td>".$certificate['issuer']['O']."</td>";
			print "	<td>".str_replace([","], "<br>", $certificate['extensions']['subjectAltName'])."</td>";

	        // custom fields
	        if(sizeof(@$custom_fields) > 0) {
		   		foreach($custom_fields as $field) {
					print "<td class='hidden-xs hidden-sm hidden-md'>";

					// fix for text
					if($field['type']=="text") { $field['type'] = "varchar(255)"; }

					$Tools->print_custom_field ($field['type'], $p->{$field['name']}, "\n", "<br>");
					print "</td>";
		    	}
		    }

			// actions
			print " <td class='actions' style='width:50px;'>";
			$links = [];

			$links[] = ["type"=>"header", "text"=>_("Download")];
			$links[] = ["type"=>"link", "text"=>_("Download certificate"), "href"=>"", "class"=>"open_popup", "dataparams"=>" data-script='app/admin/vaults/download-certificate.php' data-class='700' data-vaultid='$vault->id', data-id='$p->id'", "icon"=>"download"];

			if($User->get_module_permissions ("vaults")>=User::ACCESS_RW) {
			    $links[] = ["type"=>"header", "text"=>_("Manage")];
			    $links[] = ["type"=>"link", "text"=>_("Edit certificate"), "href"=>"", "class"=>"open_popup", "dataparams"=>" data-script='app/admin/vaults/edit-item-certificate.php' data-class='700' data-action='edit' data-vaultid='$vault->id', data-id='$p->id'", "icon"=>"pencil"];
			    $links[] = ["type"=>"link", "text"=>_("Delete certificate"), "href"=>"", "class"=>"open_popup", "dataparams"=>" data-script='app/admin/vaults/edit-item-certificate.php' data-class='700' data-action='delete' data-vaultId='$vault->id', data-id='$p->id'", "icon"=>"times"];
			}
			// print links
			print $User->print_actions($User->user->compress_actions, $links, false, true);
			print "</td>";

			print "	</tr>";
		}
	}
}
else {
	print "<tr>";
	print "	<td colspan='$csize'>";
	$Result->show('info', "No items");
	print "	</td>";
	print "</tr>";
}

print "</tbody>";
print "</table>";