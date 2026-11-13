<?php

/**
 * Edit device result
 ***************************/

/* functions */
require_once __DIR__.'/../../../../functions/functions.php';

// initialize user object
$Database = new Database_PDO;
$User = new User($Database);
$Admin = new Admin($Database);
$Result = new Result;

// verify that user is logged in
$User->check_user_session();
// check maintaneance mode
$User->check_maintaneance_mode();
// perm check popup
if ($POST->action == 'edit') {
    $User->check_module_permissions('devices', User::ACCESS_RW, true, true);
} else {
    $User->check_module_permissions('devices', User::ACCESS_RWA, true, true);
}

if ($User->Crypto->csrf_validate($POST->csrf_cookie) === false) {
    $Result->die('danger', _('Invalid CSRF cookie'));
}

// ID must be numeric
if ($POST->action != 'add' && ! is_numeric($POST->tid)) {
    $Result->die('danger', _('Invalid ID'));
}

// name must be present! */
if ($POST->tname == '') {
    $Result->warn('danger', _('Name is mandatory').'!');
}

// checks
if ($POST->action != 'delete') {
    if (strlen((string) $POST->bgcolor) < 4) {
        $Result->die('danger', _('Invalid bg color'));
    }

    if (strlen((string) $POST->fgcolor) < 4) {
        $Result->die('danger', _('Invalid fg color'));
    }
}

// create array of values for modification
$values = ['tid' => $POST->tid,
    'tname' => $POST->tname,
    'tdescription' => $POST->tdescription,
    'bgcolor' => $POST->bgcolor,
    'fgcolor' => $POST->fgcolor,
];

// update
if (! $Admin->object_modify('deviceTypes', $POST->action, 'tid', $values)) {
    $Result->warn('danger', _('Failed to').' '.$User->get_post_action().' '._('device type').'!');
} else {
    $Result->warn('success', _('Device type').' '.$User->get_post_action().' '._('successful').'!');
}

if ($POST->action == 'delete') {
    $Admin->remove_object_references('devices', 'type', $values['tid']);
}
