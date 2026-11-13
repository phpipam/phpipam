<?php

/**
 * Script to display language edit
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

// check if site is demo
$User->is_demo();
// check maintaneance mode
$User->check_maintaneance_mode();

if ($User->Crypto->csrf_validate($POST->csrf_cookie) === false) {
    $Result->die('danger', _('Invalid CSRF cookie'));
}

// verify that description is present if action != delete
if ($POST->action != 'delete' && strlen((string) $POST->l_code) < 2) {
    $Result->die('danger', _('Code must be at least 2 characters long'));
}

if ($POST->action != 'delete' && strlen((string) $POST->l_name) < 2) {
    $Result->die('danger', _('Name must be at least 2 characters long'));
}

// create update array
$values = ['l_id' => $POST->l_id,
    'l_code' => $POST->l_code,
    'l_name' => $POST->l_name,
];

// update
if (! $Admin->object_modify('lang', $POST->action, 'l_id', $values)) {
    $Result->die('danger', _('Language '.$User->get_post_action().' error'));
} else {
    $Result->die('success', _('Language '.$User->get_post_action().' success'));
}
