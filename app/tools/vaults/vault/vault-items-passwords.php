<?php

# verify that user is logged in
$User->check_user_session();

# fetch items
$passwords = $Tools->fetch_multiple_objects ("vaultItems", "vaultId", $_GET['subnetId']);

// create new item
if ($User->get_module_permissions ("vaults")>=User::ACCESS_RW) {
	print "<button class='btn btn-sm btn-default open_popup' style='margin-bottom:10px;' data-script='app/admin/vaults/edit-item-password.php' data-class='700' data-action='add' data-vaultId='{$vault->id}'><i class='fa fa-plus'></i> "._('Create password')."</button>";
}

# get custom fields
$custom_fields = $Tools->fetch_custom_fields('vaultItems');
# size of custom fields
$csize = sizeof($custom_fields) + 5;

# printout
print "<table class='table sorted table-striped sorted table-passwords' data-cookie-id-table='vault-passwords'>";

// headers
print "<thead>";
print "	<th>"._("Name")."</th>";
print "	<th>"._("Username")."</th>";
print "	<th data-width='300' data-width-unit='px'>"._("Password")."</th>";
print "	<th class='hidden-sm'>"._("Description")."</th>";
// custom
if(sizeof(@$custom_fields) > 0) {
	foreach($custom_fields as $field) {
		print "	<th class='hidden-xs hidden-sm hidden-md'>".$Tools->print_custom_field_name ($field['name'])."</th>";
	}
}
print "	<th style='width:50px;'></th>";
print "</thead>";

print "<tbody>";
if($passwords!==false) {
	// loop
	foreach ($passwords as $p) {
		// decrypt values
		$values = $User->Crypto->decrypt($p->values, $_SESSION["vault".$vault->id]);
		// check
		if($values===false) {
			$values = ["name"=>"Cannot decrypt","username"=>"Cannot decrypt", "password"=>"Cannot decrypt", "description"=>"Cannot decrypt"];
			$trclass = "alert-danger";
		}
		else {
			$values = pf_json_decode($values, true);
			$trclass = "";
		}

		// print
		print "<tr class='$trclass'>";
		print "	<td><strong>".$values['name']."</strong></td>";
		print "	<td>".$values['username']."</td>";
		if($trclass=="alert-danger") {
		print "	<td><i class='fa fa-warning' style='width:20px;'></i> <span>********</span></td>";
		}
		else {
		print "	<td><i class='fa fa-eye passShow' style='width:20px;' rel='tooltip' title='Show password' data-pass='".$values['password']."'></i> <span>********</span></td>";
		}
		print "	<td class='hidden-sm'>".$values['description']."</td>";

        // custom fields
        if(sizeof(@$custom_fields) > 0) {
	   		foreach($custom_fields as $field) {
				print "<td class='hidden-xs hidden-sm hidden-md'>";
				$Tools->print_custom_field ($field['type'], $p->{$field['name']});
				print "</td>";
	    	}
	    }

		// actions
		print " <td class='actions' style='width:50px;'>";
		$links = [];
		if($User->get_module_permissions ("vaults")>=User::ACCESS_RW) {
		    $links[] = ["type"=>"header", "text"=>_("Manage")];
		    $links[] = ["type"=>"link", "text"=>_("Edit password"), "href"=>"", "class"=>"open_popup", "dataparams"=>" data-script='app/admin/vaults/edit-item-password.php' data-class='700' data-action='edit' data-vaultid='$vault->id', data-id='$p->id'", "icon"=>"pencil"];
		    $links[] = ["type"=>"link", "text"=>_("Delete password"), "href"=>"", "class"=>"open_popup", "dataparams"=>" data-script='app/admin/vaults/edit-item-password.php' data-class='700' data-action='delete' data-vaultId='$vault->id', data-id='$p->id'", "icon"=>"times"];
		}
		// print links
		print $User->print_actions($User->user->compress_actions, $links, false, true);
		print "</td>";

		print "	</tr>";
	}
}
else {
	print "<tr>";
	print "	<td colspan='$csize'>";
	$Result->show('info', _("No items"));
	print "	</td>";
	print "</tr>";
}

print "</tbody>";
print "</table>";
?>

<script type="text/javascript">
$(document).ready(function () {
//show pass
$(document).on("click", ".fa-eye.passShow", function () {
	$(this).removeClass('fa-eye').addClass('fa-lock');
	$(this).attr('data-original-title', "Hide password");
	$(this).next().next().html($(this).attr("data-pass"))
})

// hide pass
$(document).on("click", ".fa-lock.passShow", function () {
	$(this).removeClass('fa-lock').addClass('fa-eye');
	$(this).attr('data-original-title', "Show password");
	$(this).next().next().html("********")
})

});
</script>