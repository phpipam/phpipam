<?php

// verify that user is logged in
$User->check_user_session();

// module check
if ($User->settings->enableVaults == 0) {
    $Result->warn('danger', _('Module disabled'));
}
// perm check
elseif ($User->get_module_permissions('vaults') == User::ACCESS_NONE) {
    $Result->warn('danger', _('You do not have permissions to access this module'));
} else {
    // all vaults
    if (! isset($GET->subnetId)) {
        include __DIR__.'/all-vaults.php';
    }
    // vault
    else {
        include __DIR__.'/vault/index.php';
    }
}
