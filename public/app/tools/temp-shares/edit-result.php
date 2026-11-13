<?php

/**
 * Script to display api edit result
 *************************************/

/* functions */
require_once __DIR__.'/../../../../functions/functions.php';

// initialize user object
$Database = new Database_PDO;
$User = new User($Database);
$Subnets = new Subnets($Database);
$Admin = new Admin($Database, false);
$Result = new Result;

// verify that user is logged in
$User->check_user_session();

if ($User->Crypto->csrf_validate($POST->csrf_cookie) === false) {
    $Result->die('danger', _('Invalid CSRF cookie'));
}

/* checks */
if ($User->settings->tempShare != 1) {
    $Result->die('danger', _('Temporary sharing disabled'));
}

if ($POST->type != 'subnets' && $POST->type != 'ipaddresses') {
    $Result->die('danger', _('Invalid type'));
}

if (! is_numeric($POST->id)) {
    $Result->die('danger', _('Invalid ID'));
}

if (strlen((string) $POST->code) != 32) {
    $Result->die('danger', _('Invalid code'));
}

if ($POST->validity < date('Y-m-d H:i:s')) {
    $Result->die('danger', _('Invalid date'));
}

if ($POST->validity > date('Y-m-d H:i:s', strtotime('+ 7 days'))) {
    $Result->die('danger', _('1 week is max validity time'));
}

// verify each recipient
if (! is_blank($POST->email)) {
    foreach (explode(',', (string) $POST->email) as $rec) {
        if (! filter_var(trim($rec), FILTER_VALIDATE_EMAIL)) {
            $Result->die('danger', _('Invalid email address').' - '.$rec);
        }
    }
}

// fetch object
$object = $Admin->fetch_object($POST->type, 'id', $POST->id);
if (! is_object($object)) {
    $Result->die('danger', _('Invalid ID'), true);
}

if ($POST->type == 'subnets') {
    if ($Subnets->check_permission($User->user, $object->id) < User::ACCESS_RW) {
        $Result->die('danger', _('Invalid ID'), true);
    }

    $tmp[] = _('Share type: subnet');
    $tmp[] = "\t".$Subnets->transform_to_dotted($object->subnet)."/$object->mask";
    $tmp[] = "\t".$object->description;
} else {
    if ($Subnets->check_permission($User->user, $object->subnetId) < User::ACCESS_RW) {
        $Result->die('danger', _('Invalid ID'), true);
    }

    $tmp[] = _('Share type: IP address');
    $tmp[] = "\t".$Subnets->transform_to_dotted($object->ip_addr);
    $tmp[] = "\t".$object->description;
}

// set new access
$new_access[$POST->code] = ['id' => $POST->id,
    'type' => $POST->type,
    'code' => $POST->code,
    'validity' => strtotime((string) $POST->validity),
    'userId' => $User->user->id,
];

// create array of values for modification
$old_access = db_json_decode($User->settings->tempAccess, true);
if (! is_array($old_access)) {
    $old_access = [];
} else {
    // remove all expired
    foreach ($old_access as $k => $a) {
        if (time() > $a['validity']) {
            unset($old_access[$k]);
        }
    }

    // reset array
    is_array($old_access) ?: $old_access = [];
}

$new_access = json_encode(array_merge($old_access, array_filter($new_access)));
if (strlen($new_access) > 65535) {
    $Result->die('danger', _('Maximum number of shares exceeded'), true);
}

// execute
if (! $Admin->object_modify('settings', 'edit', 'id', ['id' => 1, 'tempAccess' => $new_access])) {
    $Result->die('danger', _('Temporary share create error'));
} else {
    $Result->warn('success', _('Temporary share created'));
}

// send mail
if (! is_blank($POST->email)) {
    // try to send
    try {
        // fetch mailer settings
        $mail_settings = $Admin->fetch_object('settingsMail', 'id', 1);

        // initialize mailer
        $phpipam_mail = new phpipam_mail($User->settings, $mail_settings);

        // generate url
        $url = $Admin->createURL().create_link('temp_share', $POST->code);

        // set html content
        $content[] = "<table style='margin-left:10px;margin-top:5px;width:auto;padding:0px;border-collapse:collapse;'>";
        $content[] = "<tr><td>$User->mail_font_style<strong>New ipam share created</strong></font><br><br></td></tr>";

        $content[] = "<tr><td colspan='2'>$User->mail_font_style Hi, new share was created on ".$User->settings->siteTitle.', available on following address:</font></td></tr>';
        $content[] = "<tr><td colspan='2'><a href='$url'>$User->mail_font_style_href <xmp>$url</xmp></font></a></td></tr>";
        $content[] = "<tr><td colsapn='2' style='line-height:18px;'>$User->mail_font_style <strong>Details:</strong><br>".implode('<br> - ', $tmp).'</font><br></td></tr>';
        $content[] = "<tr><td style='padding:5px;padding-left:15px;padding-top:20px;font-style:italic;'>$User->mail_font_style_light Sent by user ".$User->user->real_name.' at '.date('Y/m/d H:i').'</font></td></tr>';
        // set al content
        $content_plain = [];
        $content_plain[] = 'New ipam share created'."\r\n------------------------------\r\n";
        $content_plain[] = 'Hi, new share was created on '.$User->settings->siteTitle.", available on following address:\r\n ".$url;
        $content_plain[] = "\r\nDetails: \r\n".implode("\r\n", $tmp)."\r\n";
        $content_plain[] = "\r\n\r\n"._('Sent by user').' '.$User->user->real_name.' at '.date('Y/m/d H:i');
        $content[] = '</table>';

        // set alt content
        $content = $phpipam_mail->generate_message(implode("\r\n", $content));
        $content_plain = implode("\r\n", $content_plain);

        $phpipam_mail->Php_mailer->setFrom($mail_settings->mAdminMail, $mail_settings->mAdminName);
        foreach (explode(',', (string) $POST->email) as $r) {
            $phpipam_mail->Php_mailer->addAddress(addslashes(trim($r)));
        }

        $phpipam_mail->Php_mailer->Subject = 'New ipam share created';
        $phpipam_mail->Php_mailer->msgHTML($content);
        $phpipam_mail->Php_mailer->AltBody = $content_plain;
        // send
        $phpipam_mail->Php_mailer->send();
    } catch (PHPMailer\PHPMailer\Exception $e) {
        $Result->die('danger', 'Mailer Error: '.$e->errorMessage());
    } catch (Exception $e) {
        $Result->die('danger', 'Mailer Error: '.$e->getMessage());
    }

    // all good
    $Result->die('success', _('Sending mail succeeded').'!');
}
