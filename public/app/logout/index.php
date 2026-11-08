<?php

if ($User->Crypto->csrf_validate($GET->csrf_cookie) === false) {
    $Result->die('danger', _('Invalid CSRF cookie'));
}

header('X-XSS-Protection:1; mode=block');
header('Location: '.create_link());

$User->logout();
