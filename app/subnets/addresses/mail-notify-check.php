<?php

/**
 * Script to verify posted data for mail notif
 *************************************************/

# include required scripts
require( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize required objects
$Database 	= new Database_PDO;
$Result		= new Result;
$User		= new User ($Database);
$Tools		= new Tools ($Database);


# verify that user is logged in
$User->check_user_session();

# verify each recipient
foreach (explode(",", $_POST['recipients']) as $rec) {
	if(!filter_var(trim($rec), FILTER_VALIDATE_EMAIL)) {
		$Result->show("danger", _("Invalid email address")." - ".$rec, true);
	}
}
# strip html tags
$_POST = $Tools->strip_input_tags($_POST);



# fetch mailer settings
$mail_settings = $Tools->fetch_object("settingsMail", "id", 1);

# initialize mailer
$phpipam_mail = new phpipam_mail($User->settings, $mail_settings);
$phpipam_mail->initialize_mailer();


// set subject
$subject	= $_POST['subject'];

// set html content
$content[] = "<table style='margin-left:10px;margin-top:5px;width:auto;padding:0px;border-collapse:collapse;'>";
$content[] = "<tr><td style='padding:5px;margin:0px;border-bottom:1px solid #eeeeee;'>$User->mail_font_style<strong>$subject</strong></font></td></tr>";
foreach(explode("\r\n", $_POST['content']) as $c) {
$content[] = "<tr><td style='padding-left:15px;margin:0px;'>$User->mail_font_style $c</font></td></tr>";
}
$content[] = "<tr><td style='padding-left:15px;padding-top:20px;margin:0px;font-style:italic;'>$User->mail_font_style_light Sent by user ".$User->user->real_name." at ".date('Y/m/d H:i')."</font></td></tr>";
//set al content
$content_plain[] = "$subject"."\r\n------------------------------\r\n";
$content_plain[] = str_replace("&middot;", "\t - ", $_POST['content']);
$content_plain[] = "\r\n\r\n"._("Sent by user")." ".$User->user->real_name." at ".date('Y/m/d H:i');
$content[] = "</table>";

// set alt content
$content 		= $phpipam_mail->generate_message (implode("\r\n", $content));
$content_plain 	= implode("\r\n",$content_plain);


# try to send
try {
	$phpipam_mail->Php_mailer->setFrom($mail_settings->mAdminMail, $mail_settings->mAdminName);
	foreach(explode(",", $_POST['recipients']) as $r) {
	$phpipam_mail->Php_mailer->addAddress(addslashes(trim($r)));
	}
	$phpipam_mail->Php_mailer->Subject = $subject;
	$phpipam_mail->Php_mailer->msgHTML($content);
	$phpipam_mail->Php_mailer->AltBody = $content_plain;
	//send
	$phpipam_mail->Php_mailer->send();
} catch (phpmailerException $e) {
	$Result->show("danger", "Mailer Error: ".$e->errorMessage(), true);
} catch (Exception $e) {
	$Result->show("danger", "Mailer Error: ".$e->errorMessage(), true);
}

# all good
$Result->show("success", _('Sending mail succeeded')."!" , true);
?>