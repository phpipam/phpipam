<?php

# verify that user is logged in
$User->check_user_session();

# printout
// perm check
if ($User->get_module_permissions ("vaults")==User::ACCESS_NONE) {
    $Result->show("danger", _("You do not have permissions to access this module"), false);
}
else {
	print "<h4>"._('Certificate details')."</h4>";
	print "<hr>";
	print "<br>";

	// back
	print "<a class='btn btn-sm btn-default' href='".create_link($_GET['page'], "vaults", $_GET['subnetId'])."'><i class='fa fa-angle-left'></i> "._("All vault items")."</a><br><br>";

	// test
	if($User->Crypto->decrypt($vault->test, $_SESSION[$vault_id])!="test") {
	    $Result->show("danger", _("Cannot unlock vault"), false);

	}
	else {

		// fetch item
		$vault_item = $Tools->fetch_object("vaultItems", "id", $_GET['sPage']);
		$vault_item_values = pf_json_decode($User->Crypto->decrypt($vault_item->values, $_SESSION[$vault_id]));

		// get custom fields
		$custom_fields = $Tools->fetch_custom_fields('vaultItems');

		print "<table class='ipaddress_subnet table-condensed table-auto'>";

		// name
		print "<tr>";
		print "	<th>"._("Name")."</th>";
		print "	<td><strong>".$vault_item_values->name."</strong></td>";
		print "</tr>";

		// description
		print "<tr>";
		print "	<th>"._("Description")."</th>";
		print "	<td>".$vault_item_values->description."</td>";
		print "</tr>";

		// private key
		$pkey = openssl_get_privatekey(base64_decode($vault_item_values->certificate))===false ? "-" : "<i class='fa fa-key'></i> ["._("Certificate has private key")."]";
		print "<tr>";
		print "	<th>"._("Private key")."</th>";
		print "	<td>".$pkey."</td>";
		print "</tr>";

		// print custom fields
		if(sizeof($custom_fields) > 0) {

			print "<tr>";
			print "	<td colspan='2'><hr></td>";
			print "</tr>";

			foreach($custom_fields as $key=>$field) {
				$vault_item->{$key} = str_replace("\n", "<br>",$vault_item->{$key});

				# fix for boolean
				if($field['type']=="tinyint(1)" || $field['type']=="boolean") {
					if($vault_item->{$key}==0)		{ $vault_item->{$key} = "false"; }
					elseif($vault_item->{$key}==1)	{ $vault_item->{$key} = "true"; }
					else							{ $vault_item->{$key} = ""; }
				}

				// create links
				$vault_item->{$key} = $Tools->create_links($vault_item->{$key});

				print "<tr>";
				print "	<th>".$Tools->print_custom_field_name ($key)."</th>";
				print "	<td style='vertical-align:top;align-content:left;'>".$vault_item->{$key}."</td>";
				print "</tr>";
			}
		}

		// actions
		if($User->Crypto->decrypt($vault->test, $_SESSION[$vault_id])=="test") {
			// divider
			print "<tr>";
			print "	<td colspan='2'><hr></td>";
			print "</tr>";

			print "<tr>";
			print "	<td></td>";
			// actions
			print "<td class='actions'>";
			$links = [];

			$links[] = ["type"=>"header", "text"=>_("Download")];
			$links[] = ["type"=>"link", "text"=>_("Download certificate (.cer)"), "href"=>"", "class"=>"open_popup", "dataparams"=>" data-script='app/admin/vaults/download-certificate.php' data-class='700' data-type='public' data-vaultid='$vault->id', data-id='$p->id'", "icon"=>"download"];
			if(openssl_get_privatekey(base64_decode($values['certificate']))!==false) {
			$links[] = ["type"=>"link", "text"=>_("Download certificate with key (.p12)"), "href"=>"", "class"=>"open_popup", "dataparams"=>" data-script='app/admin/vaults/download-certificate.php' data-class='700' data-type='pkcs12' data-vaultid='$vault->id', data-id='$p->id'", "icon"=>"download"];
			}

			if($User->get_module_permissions ("vaults")>=User::ACCESS_RW) {
			    $links[] = ["type"=>"header", "text"=>_("Manage")];
			    $links[] = ["type"=>"link", "text"=>_("Edit item"), "href"=>"", "class"=>"open_popup", "dataparams"=>" data-script='app/admin/vaults/edit-item-certificate.php' data-class='700' data-action='edit' data-vaultId='$vault->id' data-id='$vault_item->id'", "icon"=>"pencil"];
			    }
			if($User->get_module_permissions ("vaults")>=User::ACCESS_RWA) {
			    $links[] = ["type"=>"link", "text"=>_("Delete item"), "href"=>"", "class"=>"open_popup", "dataparams"=>" data-script='app/admin/vaults/edit-item-certificate.php' data-class='700' data-action='delete' data-vaultId='$vault->id' data-id='$vault_item->id'", "icon"=>"times"];
			}
			// print links
			print $User->print_actions($User->user->compress_actions, $links, true, true);
			print "</td>";
			print "</tr>";

			$cert_chain = preg_split("/(?=-----BEGIN CERTIFICATE-----)/m", base64_decode($vault_item_values->certificate), -1, PREG_SPLIT_NO_EMPTY);

			foreach($cert_chain as $link) {
				// decode and print cert
				$certificate_details = openssl_x509_parse($link, true);

				// get public key details
				$key = openssl_pkey_get_public(base64_decode($vault_item_values->certificate));
				$key_details = openssl_pkey_get_details($key);

				// get hash
				$cert = openssl_x509_read(base64_decode($vault_item_values->certificate));
				$sha1_hash = openssl_x509_fingerprint($cert); // sha1 hash
				$sha256_hash = openssl_x509_fingerprint($cert, 'SHA256'); // md5 hash

				// print "<pre>";
				// print_r($key_details);
				// print_r($certificate_details);
				// print_r($sha1_hash);
				// print "</pre>";

				// Subject name
				print "<tr>";
				print "	<td colspan='2'><h4 style='margin-top:50px;'>"._("Subject name")."</h4><hr></td>";
				print "</tr>";
				print "<tr>";
				print "	<th>"._("Common name")."</th>";
				print "	<td>".$certificate_details['subject']['CN']."</td>";
				print "</tr>";
				print "	<th>"._("Alt names")."</th>";
				print "	<td>".str_replace(",","<br>",$certificate_details['extensions']['subjectAltName'])."</td>";
				print "</tr>";

				// Certificate details
				print "<tr>";
				print "	<td colspan='2'><h4 style='margin-top:30px;'>"._("Certificate details")."</h4><hr></td>";
				print "</tr>";
				print "<tr>";
				print "	<th>"._("Serial number")."</th>";
				print "	<td>".chunk_split($certificate_details['serialNumberHex'], 2, ' ')."</td>";
				print "</tr>";
				print "<tr>";
				print "	<th>"._("Key size")."</th>";
				print "	<td>".$key_details['bits']." kB</td>";
				print "</tr>";
				print "<tr>";
				print "	<th>"._("Version")."</th>";
				print "	<td>".$certificate_details['version']."</td>";
				print "</tr>";
				print "<tr>";
				print "	<th>"._("Signature algorithm")."</th>";
				print "	<td>".$certificate_details['signatureTypeSN']."</td>";
				print "</tr>";
				print "<tr>";
				print "	<th>"._("Not valid before")."</th>";
				print "	<td>".date("Y-m-d H:i:s", $certificate_details['validFrom_time_t'])."</td>";
				print "</tr>";
				print "<tr>";
				print "	<th>"._("Not valid after")."</th>";
				print "	<td>".date("Y-m-d H:i:s", $certificate_details['validTo_time_t'])."</td>";
				print "</tr>";

				// Fingerptints
				print "<tr>";
				print "	<td colspan='2'><h4 style='margin-top:30px;'>"._("Fingerprints")."</h4><hr></td>";
				print "</tr>";
				print "<tr>";
				print "	<th>"._("SHA-256")."</th>";
				print "	<td>".chunk_split(openssl_x509_fingerprint($cert, 'SHA256'), 2, ' ')."</td>";
				print "</tr>";
				print "<tr>";
				print "	<th>"._("SHA-1")."</th>";
				print "	<td>".chunk_split(openssl_x509_fingerprint($cert, 'SHA1'), 2, ' ')."</td>";
				print "</tr>";

				// Issuer
				print "<tr>";
				print "	<td colspan='2'><h4 style='margin-top:30px;'>"._("Issuer")."</h4><hr></td>";
				print "</tr>";
				if(!is_blank($certificate_details['issuer']['C'])) {
				print "<tr>";
				print "	<th>"._("Country")."</th>";
				print "	<td>".$certificate_details['issuer']['C']."</td>";
				print "</tr>";
				}
				if(strlen($certificate_details['issuer']['ST'])) {
				print "<tr>";
				print "	<th>"._("County")."</th>";
				print "	<td>".$certificate_details['issuer']['ST']."</td>";
				print "</tr>";
				}
				if(strlen($certificate_details['issuer']['L'])) {
				print "<tr>";
				print "	<th>"._("Locality")."</th>";
				print "	<td>".$certificate_details['issuer']['L']."</td>";
				print "</tr>";
				}
				if(!is_blank($certificate_details['issuer']['O'])) {
				print "<tr>";
				print "	<th>"._("Organisation name")."</th>";
				print "	<td>".$certificate_details['issuer']['O']."</td>";
				print "</tr>";
				}
				print "<tr>";
				print "	<th>"._("Common name")."</th>";
				print "	<td>".$certificate_details['issuer']['CN']."</td>";
				print "</tr>";

				// Extensions

				unset($certificate_details['extensions']['ct_precert_scts']);

				print "<tr>";
				print "	<td colspan='2'><h4 style='margin-top:30px;'>"._("Extensions")."</h4><hr></td>";
				print "</tr>";
				foreach($certificate_details['extensions'] as $ext_key=>$e) {
					print "<tr>";
					print "	<th>".ucwords(preg_replace('/(?<!\ )[A-Z]/', ' $0', $ext_key))."</th>";
					print "	<td>".str_replace(",","<br>",$e)."</td>";
					print "</tr>";
				}
			}
		}

		print "</table>";
	}
}