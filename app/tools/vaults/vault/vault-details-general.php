<?php

# verify that user is logged in
$User->check_user_session();

// get custom fields
$custom_fields_v = $Tools->fetch_custom_fields('vaults');

# printout
// perm check
if ($User->get_module_permissions ("vaults")==User::ACCESS_NONE) {
    $Result->show("danger", _("You do not have permissions to access this module"), false);
}
else {
	print "<h4>"._('Vault details')." - $vault->name</h4>";
	print "<hr>";
	print "<br>";

	// back
	print "<a class='btn btn-sm btn-default' href='".create_link($GET->page, "vaults")."'><i class='fa fa-angle-left'></i> "._("All vaults")."</a><br><br>";

	print "<table class='ipaddress_subnet table-condensed table-auto'>";

	// name
	print "<tr>";
	print "	<th>"._("Name")."</th>";
	print "	<td><strong>".$vault->name."</strong></td>";
	print "</tr>";

	// description
	print "<tr>";
	print "	<th>"._("Description")."</th>";
	print "	<td>".$vault->description."</td>";
	print "</tr>";

	// type
	print "<tr>";
	print "	<th>"._("Type")."</th>";
	print "	<td>".$vault->type."</td>";
	print "</tr>";

	// print custom fields
	if(sizeof($custom_fields_v) > 0) {

		print "<tr>";
		print "	<td colspan='2'><hr></td>";
		print "</tr>";

		foreach($custom_fields_v as $key=>$field) {
			$vault->{$key} = str_replace("\n", "<br>",$vault->{$key});

			# fix for boolean
			if($field['type']=="tinyint(1)" || $field['type']=="boolean") {
				if($vault->{$key}==0)		{ $vault->{$key} = "false"; }
				elseif($vault->{$key}==1)	{ $vault->{$key} = "true"; }
				else						{ $vault->{$key} = ""; }
			}

			// create links
			$vault->{$key} = $Tools->create_links($vault->{$key});

			print "<tr>";
			print "	<th>".$Tools->print_custom_field_name ($key)."</th>";
			print "	<td style='vertical-align:top;align-content:left;'>".$vault->{$key}."</td>";
			print "</tr>";
		}
	}

	// status
	print "<tr>";
	print "	<td colspan='2'><hr></td>";
	print "</tr>";
	print "<tr>";
	print "	<th>"._("Status")."</th>";
	print "	<td>";
	if(isset($_SESSION[$vault_id])) {
		if($User->Crypto->decrypt($vault->test, $_SESSION[$vault_id])=="test") {
			print "<span class='text-success'>"._("Unlocked")."</span>";
		}
		else {
			print "<span class='text-danger'>"._("Locked")."</span>";
		}
	}
	else {
			print "<span class='text-danger'>"._("Locked")."</span>";
	}
	print "</td>";
	print "</tr>";


	// divider
	print "<tr>";
	print "	<td colspan='2'><hr></td>";
	print "</tr>";

	if($User->Crypto->decrypt($vault->test, $_SESSION[$vault_id])=="test") {

	print "<tr>";
	print "	<td></td>";
	// actions
	print "<td class='actions'>";
	$links = [];
	$links[] = ["type"=>"header", "text"=>_("Actions")];
	$links[] = ["type"=>"link", "text"=>_("Lock Vault"), "href"=>"", "class"=>"open_popup", "dataparams"=>" data-script='app/admin/vaults/lock.php' data-class='500' data-id='$vault->id'", "icon"=>"key"];

	if($User->get_module_permissions ("vaults")>=User::ACCESS_RW) {
	    $links[] = ["type"=>"header", "text"=>_("Manage")];
	    $links[] = ["type"=>"link", "text"=>_("Edit Vault"), "href"=>"", "class"=>"open_popup", "dataparams"=>" data-script='app/admin/vaults/edit.php' data-class='700' data-action='edit' data-id='$vault->id'", "icon"=>"pencil"];
	    }
	if($User->get_module_permissions ("vaults")>=User::ACCESS_RWA) {
	    $links[] = ["type"=>"link", "text"=>_("Delete Vault"), "href"=>"", "class"=>"open_popup", "dataparams"=>" data-script='app/admin/vaults/edit.php' data-class='700' data-action='delete' data-id='$vault->id'", "icon"=>"times"];
	}
	// print links
	print $User->print_actions($User->user->compress_actions, $links, true, true);
	print "</td>";
	print "</tr>";
	}
	else {
	print "<tr>";
	print "	<td></td>";
	// actions
	print "<td class='actions'>";
	$links = [];
	$links[] = ["type"=>"header", "text"=>_("Actions")];
	$links[] = ["type"=>"link", "text"=>_("Unlock Vault"), "href"=>"", "class"=>"open_popup", "dataparams"=>" data-script='app/admin/vaults/unlock.php' data-class='500' data-id='$vault->id'", "icon"=>"key"];
	// print links
	print $User->print_actions($User->user->compress_actions, $links, true, true);
	print "</td>";
	print "</tr>";
	}

	print "</table>";

	// vault items
	if($User->Crypto->decrypt($vault->test, $_SESSION[$vault_id])=="test") {
	include ("vault-items.php");
	}
}
