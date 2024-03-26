<?php

/*
 *	Send notification mail to user if selected
 ***********************************************/

# verify that user is logged in
$User->check_user_session();

# fetch users to receive notification and filter
$users = $Admin->fetch_multiple_objects ("users", "role", "Administrator");
foreach ($users as $k=>$u) {
	if ($u->mailNotify != "Yes") {
		unset($users[$k]);
	}
}

# try to send
try {
	# fetch mailer settings
	$mail_settings = $Admin->fetch_object("settingsMail", "id", 1);

	# verify admin mail and name
	if (strlen($mail_settings->mAdminMail)==0 || strlen($mail_settings->mAdminName)==0) {
		$Result->show("danger", _("Cannot send mail, mail settings are missing. Please set them under administration > Mail Settings !"), true);
	}

	# initialize mailer
	$phpipam_mail = new phpipam_mail($User->settings, $mail_settings);

	# set subject
	if ($_POST['action'] == "Add") 			{ $subject	= _('New ipam account created'); }
	else if ($_POST['action'] == "Edit") 	{ $subject	= _('User ipam account updated'); }
	else 									{ $subject	= _('IPAM account details'); }

	# set html content
	$content[] = "<table style='margin-left:10px;margin-top:5px;width:auto;padding:0px;border-collapse:collapse;'>";
	$content[] = "<tr><td style='padding:5px;color:#333;font-size:16px;text-shadow:1px 1px 1px white;border-bottom:1px solid #eeeeee;' colspan='2'>$User->mail_font_style <strong>$subject</font></td></tr>";
	$content[] = '<tr><td style="padding: 0px;padding-left:10px;border-top:1px solid white;">'.$User->mail_font_style.' &bull; '._('Name').'</font></td>	  	<td style="padding: 0px;padding-left:15px;line-height:18px;text-align:left;padding-top:10px;">'.$User->mail_font_style.' '. $_POST['real_name'] .'</font></td></tr>';
	//we dont need pass for domain account
	if($auth_method->type == "local") {
	$content[] = '<tr><td style="padding: 0px;padding-left:10px;line-height:18px;text-align:left;">'.$User->mail_font_style.' &bull; '._('Username').'</font></td>	<td style="padding: 0px;padding-left:15px;line-height:18px;text-align:left;">'.$User->mail_font_style.' '. $_POST['username'] 	.'</font></td></tr>';
	if(strlen($_POST['password2']) != 0) {
	$content[] = '<tr><td style="padding: 0px;padding-left:10px;line-height:18px;text-align:left;">'.$User->mail_font_style.' &bull; '._('Password').'</font></td>	<td style="padding: 0px;padding-left:15px;line-height:18px;text-align:left;">'.$User->mail_font_style.' '. $_POST['password2'] .'</font></td></tr>';
	}}
	else {
	$content[] = '<tr><td style="padding: 0px;padding-left:10px;line-height:18px;text-align:left;">'.$User->mail_font_style.' &bull; '._('Username').'</font></td>	<td style="padding: 0px;padding-left:15px;line-height:18px;text-align:left;">'.$User->mail_font_style.' * '._('your domain username').' ('. $_POST['username'] .')</font></td></tr>';
	$content[] = '<tr><td style="padding: 0px;padding-left:10px;line-height:18px;text-align:left;">'.$User->mail_font_style.' &bull; '._('Password').'</font></td>	<td style="padding: 0px;padding-left:15px;line-height:18px;text-align:left;">'.$User->mail_font_style.' * '._('your domain password').'</font></td></tr>';
	}
	$content[] = '<tr><td style="padding: 0px;padding-left:10px;line-height:18px;text-align:left;">'.$User->mail_font_style.' &bull; '._('Email').'</font></td>	<td style="padding: 0px;padding-left:15px;line-height:18px;text-align:left;"><a href="mailto:'.$_POST['email'].'">'.$User->mail_font_style_href.''.$_POST['email'].'</font></a></td></tr>';
	$content[] = '<tr><td style="padding: 0px;padding-left:10px;line-height:18px;text-align:left;">'.$User->mail_font_style.' &bull; '._('Role').'</font></td>		<td style="padding: 0px;padding-left:15px;line-height:18px;text-align:left;">'.$User->mail_font_style.' '. $_POST['role'] 		.'</font></td></tr>';
	$content[] = '<tr><td style="padding: 0px;padding-left:10px;line-height:18px;text-align:left;border-bottom:1px solid #eeeeee;padding-bottom:10px;">'.$User->mail_font_style.' &bull; '._('WebApp').'</font></td>	<td style="padding: 0px;padding-left:15px;line-height:18px;text-align:left;border-bottom:1px solid #eeeeee;padding-bottom:10px;"><a href="'. $User->settings->siteURL .'">'.$User->mail_font_style_href.''. $User->settings->siteURL. '</font></a><td></tr>';
	$content[] = "<tr><td style='padding:5px;padding-left:15px;font-style:italic;padding-bottom:3px;text-align:right;' colspan='2'>$User->mail_font_style_light "._('Sent by user')." ".$User->user->real_name." at ".date('Y/m/d H:i')."</font></td></tr>";
	$content[] = "</table>";

	# plain
	$content_plain[] = "$subject"."\r\n------------------------------\r\n";
	$content_plain[] = _("Name").": $_POST[real_name]";
	# we dont need pass for domain account
	if($auth_method->type == "local") {
	$content_plain[] = _("Username").": $_POST[username]";
	if(strlen($_POST['password2']) != 0) {
	$content_plain[] = _("Password").": $_POST[password2]";
	}}
	else {
	$content_plain[] = _("Username").": * your domain username($_POST[username]";
	$content_plain[] = _("Password").": * your domain password";
	}
	$content_plain[] = _("Email").": $_POST[email]";
	$content_plain[] = _("Role").": $_POST[role]";
	$content_plain[] = _("WebApp").": ".$User->settings->siteURL;
	$content_plain[] = "\r\n"._("Sent by user")." ".$User->user->real_name." at ".date('Y/m/d H:i');

	# set content
	$content 		= $phpipam_mail->generate_message (implode("\r\n", $content));
	$content_plain 	= implode("\r\n",$content_plain);

	$phpipam_mail->Php_mailer->setFrom($mail_settings->mAdminMail, $mail_settings->mAdminName);
	$phpipam_mail->Php_mailer->addAddress(addslashes(trim($_POST['email'])), addslashes(trim($_POST['real_name'])));
	//add all admins to CC
	if (sizeof($users)>0) {
		foreach($users as $admin) {
			$phpipam_mail->Php_mailer->AddCC($admin->email);
		}
	}
	$phpipam_mail->Php_mailer->Subject = $subject;
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
$Result->show("success", _('Notification mail for new account sent').'!', true);

?>