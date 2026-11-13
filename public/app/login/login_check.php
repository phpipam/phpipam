<?php

/**
 * Script to verify userentered input and verify it against database
 *
 * If successful write values to session and go to main page!
 */

/* functions */
require_once __DIR__.'/../../../functions/functions.php';

// initialize user object
$Database = new Database_PDO;
$User = new User($Database);
$Result = new Result;
$Log = new Logging($Database);

// Authenticate
if (! empty($POST->ipamusername) && ! empty($POST->ipampassword)) {
    // initialize array
    $ipampassword = [];

    // check failed table
    $cnt = $User->block_check_ip();

    // check for failed logins and captcha
    if ($User->blocklimit > $cnt) {
        // all good
    }
    // count set, captcha required
    elseif (! isset($POST->captcha)) {
        $Log->write(_('Login IP blocked'), _('Login from IP address').' '.$User->get_user_ip().' '._('was blocked because of 5 minute block after 5 failed 2fa attempts'), 1);
        $Result->die('danger', _('You have been blocked for 5 minutes due to authentication failures'));
    }
    // captcha check
    else {
        // check captcha
        $captcha_code = isset($_SESSION['securimage_code_value']) ? $_SESSION['securimage_code_value']['default'] : '';
        if ($captcha_code == '') {
            $Result->die('danger', _('Missing security code'));
        }

        if (strtolower($POST->captcha) != strtolower((string) $captcha_code)) {
            $Result->die('danger', _('Invalid security code'));
        }
    }

    // all good, try to authenticate user
    $User->authenticate($POST->ipamusername, $POST->ipampassword);
}
// Username / pass not provided
else {
    $Result->die('danger', _('Please enter your username and password'));
}
