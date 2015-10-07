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

# if some mail otherwise exit
if (sizeof($users)>0) {

	# fetch mailer settings
	$mail_settings = $Admin->fetch_object("settingsMail", "id", 1);


	# initialize mailer
	$phpipam_mail = new phpipam_mail($User->settings, $mail_settings);
	$phpipam_mail->initialize_mailer();


	# set subject
	if ($_POST['action'] == "Add") 			{ $subject	= _('New ipam account created'); }
	else if ($_POST['action'] == "Edit") 	{ $subject	= _('User ipam account updated'); }
	else 									{ $subject	= _('IPAM account details'); }


	# set html content
	$content[] = "<table style='margin-left:10px;margin-top:5px;width:auto;padding:0px;border-collapse:collapse;'>";
	$content[] = "<tr><td style='padding:5px;margin:0px;color:#333;font-size:16px;text-shadow:1px 1px 1px white;border-bottom:1px solid #eeeeee;' colspan='2'><font face='Helvetica, Verdana, Arial, sans-serif' style='font-size:16px;'>$subject</font></td></tr>";
	$content[] = '<tr><td style="padding: 0px;padding-left:10px;margin:0px;line-height:18px;text-align:left;border-top:1px solid white;padding-top:10px;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">&bull; '._('Name').'</font></td>	  	<td style="padding: 0px;padding-left:15px;margin:0px;line-height:18px;text-align:left;padding-top:10px;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">'. $_POST['real_name'] .'</font></td></tr>';
	//we dont need pass for domain account
	if($auth_method->type == "local") {
	$content[] = '<tr><td style="padding: 0px;padding-left:10px;margin:0px;line-height:18px;text-align:left;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">&bull; '._('Username').'</font></td>	<td style="padding: 0px;padding-left:15px;margin:0px;line-height:18px;text-align:left;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">'. $_POST['username'] 	.'</font></td></tr>';
	if(strlen($_POST['password2']) != 0) {
	$content[] = '<tr><td style="padding: 0px;padding-left:10px;margin:0px;line-height:18px;text-align:left;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">&bull; '._('Password').'</font></td>	<td style="padding: 0px;padding-left:15px;margin:0px;line-height:18px;text-align:left;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">'. $_POST['password2'] .'</font></td></tr>';
	}}
	else {
	$content[] = '<tr><td style="padding: 0px;padding-left:10px;margin:0px;line-height:18px;text-align:left;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">&bull; '._('Username').'</font></td>	<td style="padding: 0px;padding-left:15px;margin:0px;line-height:18px;text-align:left;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">* '._('your domain username').' ('. $_POST['username'] .')</font></td></tr>';
	$content[] = '<tr><td style="padding: 0px;padding-left:10px;margin:0px;line-height:18px;text-align:left;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">&bull; '._('Password').'</font></td>	<td style="padding: 0px;padding-left:15px;margin:0px;line-height:18px;text-align:left;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">* '._('your domain password').'</font></td></tr>';
	}
	$content[] = '<tr><td style="padding: 0px;padding-left:10px;margin:0px;line-height:18px;text-align:left;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">&bull; '._('Email').'</font></td>	<td style="padding: 0px;padding-left:15px;margin:0px;line-height:18px;text-align:left;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;"><a href="mailto:'.$_POST['email'].'" style="color:#08c;">'.$_POST['email'].'</a></font></td></tr>';
	$content[] = '<tr><td style="padding: 0px;padding-left:10px;margin:0px;line-height:18px;text-align:left;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">&bull; '._('Role').'</font></td>		<td style="padding: 0px;padding-left:15px;margin:0px;line-height:18px;text-align:left;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">'. $_POST['role'] 		.'</font></td></tr>';
	$content[] = '<tr><td style="padding: 0px;padding-left:10px;margin:0px;line-height:18px;text-align:left;border-bottom:1px solid #eeeeee;padding-bottom:10px;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">&bull; '._('WebApp').'</font></td>	<td style="padding: 0px;padding-left:15px;margin:0px;line-height:18px;text-align:left;border-bottom:1px solid #eeeeee;padding-bottom:10px;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;"> <a href="'. $User->settings->siteURL .'" style="color:#08c;">'. $User->settings->siteURL. '</font></a><td></tr>';
	$content[] = "<tr><td style='padding:5px;padding-left:15px;margin:0px;font-style:italic;padding-bottom:3px;text-align:right;color:#ccc;text-shadow:1px 1px 1px white;border-top:1px solid white;' colspan='2'><font face='Helvetica, Verdana, Arial, sans-serif' style='font-size:11px;'>"._('Sent by user')." ".$User->user->real_name." at ".date('Y/m/d H:i')."</font></td></tr>";
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


	# try to send
	try {
		$phpipam_mail->Php_mailer->setFrom($mail_settings->mAdminMail, $mail_settings->mAdminName);
		$phpipam_mail->Php_mailer->addAddress($_POST['email'], $_POST['real_name']);
		//add all admins to CC
		foreach($users as $admin) {
			$phpipam_mail->Php_mailer->AddCC($admin->email);
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

	//if error not sent print ok
	$Result->show("success", _('Notification mail for new account sent').'!', true);

}
?>