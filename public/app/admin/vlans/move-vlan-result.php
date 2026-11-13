<?php

/**
 * Move VLAN to new domain
 *******************************/

/* functions */
require_once __DIR__.'/../../../../functions/functions.php';

// initialize user object
$Database = new Database_PDO;
$User = new User($Database);
$Admin = new Admin($Database, false);
$Tools = new Tools($Database);
$Result = new Result;

// verify that user is logged in
$User->check_user_session();
// check maintaneance mode
$User->check_maintaneance_mode();
// perm check popup
$User->check_module_permissions('vlan', User::ACCESS_RW, true, false);

// checks
if (! is_numeric($POST->newDomainId)) {
    $Result->die('danger', _('Invalid ID'));
}

if (! is_numeric($POST->vlanid)) {
    $Result->die('danger', _('Invalid ID'));
}

// verify that new exists
$vlan_domain = $Admin->fetch_object('vlanDomains', 'id', $POST->newDomainId);
if ($vlan_domain === false) {
    $Result->die('danger', _('Invalid ID'));
}

// fetch vlan
$vlan = $Admin->fetch_object('vlans', 'vlanid', $POST->vlanid);
if ($vlan === false) {
    $Result->die('danger', _('Invalid ID'));
}

// check that it is not already set !
if ($User->settings->vlanDuplicate == 0) {
    $check_vlan = $Admin->fetch_multiple_objects('vlans', 'domainId', $vlan_domain->id, 'vlanid');
    if ($check_vlan !== false) {
        foreach ($check_vlan as $v) {
            if ($v->number == $vlan->number) {
                $Result->die('danger', _('VLAN already exists'));
            }
        }
    }
}

// formulate update query
$values = [
    'vlanid' => $POST->vlanid,
    'domainId' => $vlan_domain->id,
];
// update
if (! $Admin->object_modify('vlans', 'edit', 'vlanid', $values)) {
    $Result->die('danger', _('Failed to move VLAN to new domain').'!');
} else {
    $Result->warn('success', _('VLAN moved to new domain successfully').'!');
}
