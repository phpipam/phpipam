<?php

/**
 * Edit switch result
 ***************************/

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
if ($POST->action == 'edit') {
    $User->check_module_permissions('l2dom', User::ACCESS_RW, true, false);
} else {
    $User->check_module_permissions('l2dom', User::ACCESS_RWA, true, false);
}

if ($User->Crypto->csrf_validate($POST->csrf_cookie) === false) {
    $Result->die('danger', _('Invalid CSRF cookie'));
}

// we cannot delete default domain
if ($POST->id == 1 && $POST->action == 'delete') {
    $Result->die('danger', _('Default domain cannot be deleted'));
}

// ID must be numeric
if ($POST->action != 'add' && ! is_numeric($POST->id)) {
    $Result->die('danger', _('Invalid ID'));
}

// Hostname must be present
if ($POST->name == '') {
    $Result->die('danger', _('Name is mandatory').'!');
}

// set sections
if ($POST->id != 1) {
    $temp = [];
    foreach ($POST as $key => $line) {
        if (! is_blank(strstr((string) $key, 'section-'))) {
            $key2 = str_replace('section-', '', (string) $key);
            $temp[] = $key2;
            unset($POST->{$key});
        }
    }

    // glue sections together
    $POST->permissions = count($temp) > 0 ? implode(';', $temp) : null;
} else {
    $POST->permissions = '';
}

// set update values
$values = [
    'id' => $POST->id,
    'name' => $POST->name,
    'description' => $POST->description,
    'permissions' => $POST->permissions,
];

// update domain
if ($Admin->object_modify('vlanDomains', $POST->action, 'id', $values)) {
    $Result->warn('success', _('Domain').' '.$User->get_post_action().' '._('successful').'!');
}

// if delete move all vlans to default domain!
if ($POST->action == 'delete') {
    $Admin->update_object_references('vlans', 'domainId', $POST->id, 1);
}
