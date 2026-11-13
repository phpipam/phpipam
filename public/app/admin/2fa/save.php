<?php

/**
 *  Site settings
 **************************/

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

if ($User->Crypto->csrf_validate($POST->csrf_cookie) === false) {
    $Result->die('danger', _('Invalid CSRF cookie'));
}

// check 2fa_provider
$providers = ['none', 'Google_Authenticator'];
if (! in_array($POST->{'2fa_provider'}, $providers)) {
    $Result->die('danger', _('Invalid provider'));
}

// verify name
if (strlen((string) $POST->{'2fa_name'}) > 32 || is_blank($POST->{'2fa_name'})) {
    $Result->die('danger', _('Invalid application name'));
}

// change
$POST->{'2fa_userchange'} = $POST->{'2fa_userchange'} ?? 0;

// set update values
$values = [
    'id' => 1,
    '2fa_name' => $POST->{'2fa_name'},
    '2fa_provider' => $POST->{'2fa_provider'},
    '2fa_userchange' => $POST->{'2fa_userchange'},
];
// update
if (! $Admin->object_modify('settings', 'edit', 'id', $values)) {
    $Result->warn('danger', _('Cannot update settings'));
} else {
    $Result->warn('success', _('Settings updated successfully'));
}

// force ?
if (isset($POST->{'2fa_force'})) {
    if ($POST->{'2fa_force'} == 'On') {
        $new_status = $POST->{'2fa_provider'} == 'none' ? 0 : 1;
        $old_status = $new_status == 1 ? 0 : 1;
        // update
        if ($Admin->update_object_references('users', '2fa', $old_status, $new_status) === false) {
            $Result->warn('danger', _('Failed to update all users!'));
        }
    }
}
