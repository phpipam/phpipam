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
        $captcha = new SecurimagePHP8;
        if (! isset($_SESSION['captchaId']) || ! $captcha->check($POST->captcha, $_SESSION['captchaId'])) {
            $Result->die('danger', _('Invalid security code'));
        }

        unset($_SESSION['captchaId']);
    }

    // all good, try to authenticate user
    $User->authenticate($POST->ipamusername, $POST->ipampassword);
}
// Username / pass not provided
else {
    $Result->die('danger', _('Please enter your username and password'));
}
