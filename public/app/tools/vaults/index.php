<?php

# verify that user is logged in
$User->check_user_session();

// module check
if($User->settings->enableVaults==0) {
    $Result->show("danger", _("Module disabled"), false);
}
// perm check
elseif ($User->get_module_permissions ("vaults")==User::ACCESS_NONE) {
    $Result->show("danger", _("You do not have permissions to access this module"), false);
}
else {
	// all vaults
	if(!isset($GET->subnetId)) {
		include('all-vaults.php');
	}
	// vault
	else {
		include('vault/index.php');
	}
}