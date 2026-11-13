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

// valid params
$passwordPolicy = [
    'minLength' => 0,
    'maxLength' => 0,
    'minNumbers' => 0,
    'minLetters' => 0,
    'minLowerCase' => 0,
    'minUpperCase' => 0,
    'minSymbols' => 0,
    'maxSymbols' => 0,
];

// check for numbers and set parameters
foreach ($passwordPolicy as $k => $f) {
    if (isset($POST->{$k})) {
        if (is_blank($POST->{$k})) {
            $passwordPolicy[$k] = 0;
        } elseif (! is_numeric($POST->{$k})) {
            $Result->die('danger', _('Values must be numeric'));
        } else {
            $passwordPolicy[$k] = $POST->{$k};
        }
    }
}

// symbols
if (! is_blank($POST->allowedSymbols)) {
    $POST->allowedSymbols = str_replace(' ', '', (string) $POST->allowedSymbols);
    $passwordPolicy['allowedSymbols'] = $POST->allowedSymbols;
}

// set update values
$values = ['id' => 1, 'passwordPolicy' => json_encode($passwordPolicy)];

if (! $Admin->object_modify('settings', 'edit', 'id', $values)) {
    $Result->die('danger', _('Cannot update settings'));
} else {
    $Result->warn('success', _('Settings updated successfully'));
}

// if required check all user sertings and force them to update password
if ($POST->enforce == 1) {
    try {
        $Database->runQuery("update `users` set `passChange` = 'Yes' where `authMethod` = 1;");
    } catch (Exception $e) {
        $Result->warn('danger', _('Error updating users: ').$e->getMessage());
    }
}
