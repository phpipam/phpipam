<?php

if ($User->Crypto->csrf_validate($GET->csrf_cookie) === false) {
    $Result->show('danger', _('Invalid CSRF cookie'), true);
}

header('X-XSS-Protection:1; mode=block');
header('Location: '.create_link());

$User->logout();
