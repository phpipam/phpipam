<?php

// verify that user is logged in
$User->check_user_session();

// perm check
if ($User->get_module_permissions('vaults') == User::ACCESS_NONE) {
    $Result->warn('danger', _('You do not have permissions to access this module'));
}
// check that vauld id is numeric
elseif (! is_numeric($GET->subnetId)) {
    $Result->warn('danger', _('Invalid ID'));
} else {
    // set vaultx pass variable
    $vault_id = 'vault'.$GET->subnetId;
    $vault = $Tools->fetch_object('vaults', 'id', $GET->subnetId);

    if (! is_object($vault)) {
        $Result->warn('danger', _('Invalid ID'));
    } else {
        // details
        if (isset($GET->sPage)) {
            include __DIR__.'/vault-item-details-general.php';
        } else {
            include __DIR__.'/vault-details-general.php';
        }
    }
}
