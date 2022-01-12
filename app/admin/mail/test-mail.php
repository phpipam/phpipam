<?php

/**
 *	Mail settings
 **************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# try to send
try {
	# fetch mailer settings
	$mail_settings = $Admin->fetch_object("settingsMail", "id", 1);

	# verify admin mail and name
	if (strlen($mail_settings->mAdminMail)==0 || strlen($mail_settings->mAdminName)==0) {
		$Result->show("danger", _("Mail settings are missing. Please set admin mail and name!"), true);
	}

	# initialize mailer
	$phpipam_mail = new phpipam_mail($User->settings, $mail_settings);
	//override settings
	$phpipam_mail->override_settings($_POST);
	//debugging
	$phpipam_mail->set_debugging(2);

	# set content
	$content 		= $phpipam_mail->generate_message ("phpIPAM test HTML message");
	$content_plain 	= "phpIPAM test text message";

	$phpipam_mail->Php_mailer->setFrom($_POST['mAdminMail'], $_POST['mAdminName']);
	$phpipam_mail->Php_mailer->addAddress($User->settings->siteAdminMail, $User->settings->siteAdminName);
	$phpipam_mail->Php_mailer->Subject = 'phpIPAM localhost mail test';
	$phpipam_mail->Php_mailer->msgHTML($content);
	$phpipam_mail->Php_mailer->AltBody = $content_plain;
	//send
	$phpipam_mail->Php_mailer->send();
} catch (PHPMailer\PHPMailer\Exception $e) {
	$Result->show("danger", "Mailer Error: ".$e->errorMessage(), true);
} catch (Exception $e) {
	$Result->show("danger", "Mailer Error: ".$e->getMessage(), true);
}

//if error not sent print ok
$Result->show("success alert-absolute", "Message sent to site admin (".$User->settings->siteAdminMail.")!", true);
?>