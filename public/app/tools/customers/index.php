<?php

if (! isset($User)) {
    exit();
}

/**
 * Based on GET parameter we load:
 *  - all customers
 *  - specific customer
 */

// verify that user is logged in
$User->check_user_session();

// perm check
if ($User->get_module_permissions('customers') == User::ACCESS_NONE) {
    $Result->show('danger', _('You do not have permissions to access this module'), false);
}
// load subpage
elseif (! isset($GET->subnetId)) {
    include __DIR__.'/all-customers.php';
} else {
    include __DIR__.'/customer/index.php';
}
