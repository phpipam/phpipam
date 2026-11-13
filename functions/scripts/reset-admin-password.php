<?php

/**
 * This script resets the admin password, provided via CLI
 *
 *  either provide it by first argument or via readline
 */

// include required scripts
require_once __DIR__.'/../functions.php';

// Don't corrupt output with php errors!
disable_php_errors();

// set debugging
$debugging = false;
$disable_email_notification = false;

// initialize objects
$Database = new Database_PDO;
$Admin = new Admin($Database, false);
$User = new User($Database, true);
$Result = new Result;

// script can only be run from cli
if (PHP_SAPI != 'cli') {
    $Result->die(false, 'This script can only be run from cli');
}

// check if argv[1] provided, if not check readline support and wait for user pass
if (isset($argv[1])) {
    $password = $argv[1];
} else {
    // get available extensions
    $available_extensions = get_loaded_extensions();
    // not in array
    if (! in_array('readline', $available_extensions)) {
        $Result->die(false, "readline php extension is required.\nOr provide password as first argument");
    } else {
        // read password
        $password = trim(readline('Enter new admin password: '));
    }
}

// validate password
if (strlen((string) $password) < 8) {
    $Result->die(false, 'Password must be at least 8 characters long');
}

// hash password
$password_crypted = $User->crypt_user_pass($password);
// save type
$crypt_type = $User->return_crypt_type();

// set update array
$values = ['id' => 1, 'password' => $password_crypted];

// Disable 2FA
if ($User->settings->dbversion >= 4) {
    $values['2fa'] = 0;
}

// Disable passkeys
if ($User->settings->dbversion >= 40) {
    $values['passkey_only'] = 0;
}

// update password
if (! $Admin->object_modify('users', 'edit', 'id', $values)) {
    $Result->warn(false, 'Failed to update Admin password');
} else {
    $Result->warn(false, 'Admin password updated');
}

// debug ?
if ($debugging) {
    $Result->warn(false, '---------');
    $Result->warn(false, 'Crypt type: '.$crypt_type);
    $Result->warn(false, 'Password: '.$password_crypted);
    $Result->warn(false, '---------');
}

// fail
if ($disable_email_notification) {
    exit();
}

// check for recipients
foreach ($Admin->fetch_multiple_objects('users', 'role', 'Administrator') as $admin) {
    $recepients[] = ['name' => $admin->real_name, 'email' => $admin->email];
}

// none?
if (! isset($recepients)) {
    exit();
}

// fetch settings
$settings = $Admin->get_settings();

// try to send
try {
    // fetch mailer settings
    $mail_settings = $Admin->fetch_object('settingsMail', 'id', 1);
    // initialize mailer
    $phpipam_mail = new phpipam_mail($settings, $mail_settings);

    // set subject
    $subject = 'phpIPAM Administrator password updated';
    // html
    $content[] = '<h3>phpIPAM Administrator password updated</h3>';
    $content[] = '<hr>';
    $content[] = 'Administrator password was updated via cli script';
    // plain
    $content_plain[] = "phpIPAM Administrator password updated \r\n------------------------------";
    $content_plain[] = 'Administrator password was updated via cli script';

    // set content
    $content = $phpipam_mail->generate_message(implode("\r\n", $content));
    $content_plain = implode("\r\n", $content_plain);

    $phpipam_mail->Php_mailer->setFrom($mail_settings->mAdminMail, $mail_settings->mAdminName);
    // add all admins to CC
    foreach ($recepients as $admin) {
        $phpipam_mail->Php_mailer->addAddress(addslashes((string) $admin['email']), addslashes((string) $admin['name']));
    }

    $phpipam_mail->Php_mailer->Subject = $subject;
    $phpipam_mail->Php_mailer->msgHTML($content);
    $phpipam_mail->Php_mailer->AltBody = $content_plain;
    // send
    $phpipam_mail->Php_mailer->send();
} catch (PHPMailer\PHPMailer\Exception $e) {
    $Result->die(false, 'Mailer Error: '.$e->errorMessage());
} catch (Exception $e) {
    $Result->die(false, 'Mailer Error: '.$e->getMessage());
}
