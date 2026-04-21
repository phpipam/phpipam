<?php

# verify that user is logged in
$User->check_user_session();

// perm check
if ($User->get_module_permissions ("vaults")==User::ACCESS_NONE) {
    $Result->show("danger", _("You do not have permissions to access this module"), false);
}
// check that vauld id is numeric
elseif(!is_numeric($GET->subnetId)) {
    $Result->show("danger", _("Invalid ID"), false);
}
else {
	// set vaultx pass variable
	$vault_id = "vault".$GET->subnetId;
	// fetch vault
	$vault = $Tools->fetch_object("vaults", "id", $GET->subnetId);

	// validate vault id
	if($vault===false) {
		$Result->show("danger", _("Invalid ID"), false);
	}
	// check if key is present
	else {
		// details
		if(isset($GET->sPage)) {
			// print cert details
			include ("vault-item-details-general.php");
		}
		else {
			// print details
			include ("vault-details-general.php");
		}
	}
}