<?php

/**
 *  Script to replace fields in IP address list
 ***********************************************/

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

// verify post
if (empty($POST->search)) {
    $Result->die('danger', _('Please enter something in search field').'!');
}

// if device verify that it exists
if ($POST->field == 'switch') {
    if (! $device1 = $Admin->fetch_object('devices', 'hostname', $POST->search)) {
        $Result->die('danger  alert-absolute', _('Switch').' "<i>'.escape_input($POST->search).'</i>" '._('does not exist, first create switch under admin menu').'!');
    }

    if (! $device2 = $Admin->fetch_object('devices', 'hostname', $POST->replace)) {
        $Result->die('danger  alert-absolute', _('Switch').' "<i>'.escape_input($POST->search).'</i>" '._('does not exist, first create switch under admin menu').'!');
    }

    // replace posts
    $POST->search = $device1->id;
    $POST->replace = $device2->id;
}

// update
$Admin->replace_fields($POST->field, $POST->search, $POST->replace);
