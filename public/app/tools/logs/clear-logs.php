<?php

/**
 *  clear log files
 **********************************/

/* functions */
require_once __DIR__.'/../../../../functions/functions.php';

// initialize user object
$Database = new Database_PDO;
$User = new User($Database);
$Admin = new Admin($Database);
$Result = new Result;

// verify that user is logged in
$User->check_user_session();

if ($User->Crypto->csrf_validate($GET->csrf) === false) {
    $Result->die('danger', _('Invalid CSRF cookie'));
}

// truncate logs table
if (! $Admin->truncate_table('logs')) {
    $Result->die('danger', _('Error clearing logs').'!');
} else {
    $Result->die('success', _('Logs cleared successfully').'!');
}
