<?php

/**
 * Script to display api edit result
 *************************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Subnets 	= new Subnets ($Database);
$Admin	 	= new Admin ($Database, false);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

/* checks */
if($User->settings->tempShare!=1)									{ $Result->show("danger", _("Temporary sharing disabled"), true); }
if($POST->type!="subnets"&&$POST->type!="ipaddresses") 		{ $Result->show("danger", _("Invalid type"), true); }
if(!is_numeric($POST->id)) 										{ $Result->show("danger", _("Invalid ID"), true); }
if(strlen($POST->code)!=32) 										{ $Result->show("danger", _("Invalid code"), true); }
if($POST->validity<date("Y-m-d H:i:s"))							{ $Result->show("danger", _("Invalid date"), true); }
if($POST->validity>date("Y-m-d H:i:s", strtotime("+ 7 days")))	{ $Result->show("danger", _("1 week is max validity time"), true); }
# verify each recipient
if(!is_blank($POST->email)) {
	foreach (pf_explode(",", $POST->email) as $rec) {
		if(!filter_var(trim($rec), FILTER_VALIDATE_EMAIL)) 			{ $Result->show("danger", _("Invalid email address")." - ".escape_input($rec), true); }
	}
}

# fetch object
$object = $Admin->fetch_object ($POST->type, "id", $POST->id);

if($POST->type=="subnets") {
	$tmp[] = _("Share type: subnet");
	$tmp[] = "\t".$Subnets->transform_to_dotted($object->subnet)."/$object->mask";
	$tmp[] = "\t".$object->description;
}
else {
	$tmp[] = _("Share type: IP address");
	$tmp[] = "\t".$Subnets->transform_to_dotted($object->ip_addr);
	$tmp[] = "\t".$object->description;
}

# set new access
$new_access[$POST->code] = array("id"=>$POST->id,
									"type"=>$POST->type,
									"code"=>$POST->code,
									"validity"=>strtotime($POST->validity),
									"userId"=>$User->user->id
									);

# create array of values for modification
$old_access = db_json_decode($User->settings->tempAccess, true);
if(!is_array($old_access)) {
	$old_access = array();
} else {
	//remove all expired
	foreach($old_access as $k=>$a) {
		if(time()>$a['validity']) {
			unset($old_access[$k]);
		}
	}
	//reset array
	is_array($old_access) ? : $old_access = array();
}
$new_access = json_encode(array_merge($old_access, array_filter($new_access)));

# execute
if(!$Admin->object_modify("settings", "edit", "id", array("id"=>1,"tempAccess"=>$new_access))) 	{ $Result->show("danger",  _("Temporary share create error"), true); }
else 																							{ $Result->show("success", _("Temporary share created"), false); }

# send mail
if(!is_blank($POST->email)) {
	# try to send
	try {
		# fetch mailer settings
		$mail_settings = $Admin->fetch_object("settingsMail", "id", 1);

		# initialize mailer
		$phpipam_mail = new phpipam_mail($User->settings, $mail_settings);

		// generate url
		$url = $Admin->createURL().create_link("temp_share",$POST->code);

		// set html content
		$content[] = "<table style='margin-left:10px;margin-top:5px;width:auto;padding:0px;border-collapse:collapse;'>";
		$content[] = "<tr><td>$User->mail_font_style<strong>New ipam share created</strong></font><br><br></td></tr>";

		$content[] = "<tr><td colspan='2'>$User->mail_font_style Hi, new share was created on ".$User->settings->siteTitle.", available on following address:</font></td></tr>";
		$content[] = "<tr><td colspan='2'><a href='$url'>$User->mail_font_style_href <xmp>$url</xmp></font></a></td></tr>";
		$content[] = "<tr><td colsapn='2' style='line-height:18px;'>$User->mail_font_style <strong>Details:</strong><br>".implode("<br> - ", $tmp)."</font><br></td></tr>";
		$content[] = "<tr><td style='padding:5px;padding-left:15px;padding-top:20px;font-style:italic;'>$User->mail_font_style_light Sent by user ".$User->user->real_name." at ".date('Y/m/d H:i')."</font></td></tr>";
		//set al content
		$content_plain[] = "$subject"."\r\n------------------------------\r\n";
		$content_plain[] = "Hi, new share was created on ".$User->settings->siteTitle.", available on following address:\r\n ".$url;
		$content_plain[] = "\r\nDetails: \r\n".implode("\r\n", $tmp)."\r\n";
		$content_plain[] = "\r\n\r\n"._("Sent by user")." ".$User->user->real_name." at ".date('Y/m/d H:i');
		$content[] = "</table>";

		// set alt content
		$content 		= $phpipam_mail->generate_message (implode("\r\n", $content));
		$content_plain 	= implode("\r\n",$content_plain);

		$phpipam_mail->Php_mailer->setFrom($mail_settings->mAdminMail, $mail_settings->mAdminName);
		foreach(pf_explode(",", $POST->email) as $r) {
		$phpipam_mail->Php_mailer->addAddress(addslashes(trim($r)));
		}
		$phpipam_mail->Php_mailer->Subject = "New ipam share created";
		$phpipam_mail->Php_mailer->msgHTML($content);
		$phpipam_mail->Php_mailer->AltBody = $content_plain;
		//send
		$phpipam_mail->Php_mailer->send();
	} catch (PHPMailer\PHPMailer\Exception $e) {
		$Result->show("danger", "Mailer Error: ".$e->errorMessage(), true);
	} catch (Exception $e) {
		$Result->show("danger", "Mailer Error: ".$e->getMessage(), true);
	}

	# all good
	$Result->show("success", _('Sending mail succeeded')."!" , true);
}
