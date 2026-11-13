<?php

/**
 * Script to display widget edit
 *************************************/

/* functions */
require_once __DIR__.'/../../../../functions/functions.php';

// initialize user object
$Database = new Database_PDO;
$User = new User($Database);
$Admin = new Admin($Database);
$Result = new Result;

// verify that user is logged in
$User->check_user_session();
// admin check
$User->is_admin();

// check maintaneance mode
$User->check_maintaneance_mode();

if ($User->Crypto->csrf_validate($POST->csrf_cookie) === false) {
    $Result->die('danger', _('Invalid CSRF cookie'));
}

// ID must be numeric */
if ($POST->action != 'add') {
    if (! is_numeric($POST->wid)) {
        $Result->die('danger', _('Invalid ID'));
    }
}

// Title and path must be present!
if ($POST->action != 'delete') {
    if (is_blank($POST->wtitle) || is_blank($POST->wfile)) {
        $Result->die('danger', _('Filename and title are mandatory').'!');
    }
}

// Remove .php form wfile if it is present
$POST->wfile = str_replace('.php', '', trim((string) $POST->wfile));

// set update values
$values = ['wid' => $POST->wid,
    'wtitle' => $POST->wtitle,
    'wdescription' => $POST->wdescription,
    'wfile' => $POST->wfile,
    'wadminonly' => $POST->wadminonly,
    'wactive' => $POST->wactive,
    'wparams' => $POST->wparams,
    'whref' => $POST->whref,
    'wsize' => $POST->wsize,
];
// update
if (! $Admin->object_modify('widgets', $POST->action, 'wid', $values)) {
    $Result->die('danger', _('Widget '.$User->get_post_action().' error').'!');
} else {
    $Result->die('success', _('Widget '.$User->get_post_action().' success').'!');
}
