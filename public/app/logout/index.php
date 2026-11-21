<?php

if (session_status() === PHP_SESSION_ACTIVE) {
    if ($User->Crypto->csrf_validate($POST->csrf_cookie) === false) {
        $Result->die('danger', _('Invalid CSRF cookie'));
    }

    // write log
    if ($User->is_authenticated()) {
        $Log->write(_('User logged out'), _('User').' '.$User->username.' '._('has logged out'), 0, $User->username);
    }

    $User->logout();
}

header('X-XSS-Protection:1; mode=block');
header('Location: '.create_link());
