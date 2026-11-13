<?php

require_once __DIR__.'/../../../../functions/functions.php';

// Classes
$Database = new Database_PDO;
$User = new User($Database);
$Result = new Result;
$Password_check = new Password_check;

// user must be authenticated
$User->check_user_session();

if ($User->Crypto->csrf_validate($POST->csrf_cookie) === false) {
    $Result->die('danger', _('Invalid CSRF cookie'));
}

// Check old password
if (! hash_equals($User->user->password, crypt((string) $POST->oldpassword, (string) $User->user->password))) {
    $Result->die('danger', _('Invalid password'));
}

// Check new password != old password
if ($POST->ipampassword1 == $POST->oldpassword) {
    $Result->die('danger', _('New password must be different'));
}

// Enforce password policy
$policy = (db_json_decode($User->settings->passwordPolicy, true));
$Password_check->set_requirements($policy, explode(',', (string) $policy['allowedSymbols']));
if (! $Password_check->validate($POST->ipampassword1)) {
    $Result->die('danger alert-danger ', _('Password validation errors').':<br> - '.implode('<br> - ', $Password_check->get_errors()));
}

if ($POST->ipampassword1 != $POST->ipampassword2) {
    $Result->die('danger', _('New passwords do not match'));
}

// update pass
$User->update_user_pass($POST->ipampassword1);
