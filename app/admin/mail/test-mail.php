<?php

/**
 *	Mail settings
 **************************/

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');
require( dirname(__FILE__) . '/../../../functions/classes/class.Mail.php');


# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# fetch mailer settings
$mail_settings = $Admin->fetch_object("settingsMail", "id", 1);



# initialize mailer
$phpipam_mail = new phpipam_mail($User->settings, $mail_settings);
//override settings
$phpipam_mail->override_settings($_POST);
//create object
$phpipam_mail->initialize_mailer();


# set content
$content 		= $phpipam_mail->generate_message ("phpIPAM test HTML message");
$content_plain 	= "phpIPAM test text message";


# try to send
try {
	$phpipam_mail->Php_mailer->setFrom($_POST['mAdminMail'], $_POST['mAdminName']);
	$phpipam_mail->Php_mailer->addAddress($User->settings->siteAdminMail, $User->settings->siteAdminName);
	$phpipam_mail->Php_mailer->Subject = 'phpIPAM localhost mail test';
	$phpipam_mail->Php_mailer->msgHTML($content);
	$phpipam_mail->Php_mailer->AltBody = $content_plain;
	//send
	$phpipam_mail->Php_mailer->send();
} catch (phpmailerException $e) {
	$Result->show("danger", "Mailer Error: ".$e->errorMessage(), true);
} catch (Exception $e) {
	$Result->show("danger", "Mailer Error: ".$e->errorMessage(), true);
}

//if error not sent print ok
$Result->show("success alert-absolute", "Message sent to site admin (".$User->settings->siteAdminMail.")!", true);
?>