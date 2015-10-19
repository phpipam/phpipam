<?php

/**
 * Script to disaply api edit result
 *************************************/

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

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
if($_POST['type']!="subnets"&&$_POST['type']!="ipaddresses") 		{ $Result->show("danger", _("Invalid type"), true); }
if(!is_numeric($_POST['id'])) 										{ $Result->show("danger", _("Invalid ID"), true); }
if(strlen($_POST['code'])!=32) 										{ $Result->show("danger", _("Invalid code"), true); }
if($_POST['validity']<date("Y-m-d H:i:s"))							{ $Result->show("danger", _("Invalid date"), true); }
if($_POST['validity']>date("Y-m-d H:i:s", strtotime("+ 7 days")))	{ $Result->show("danger", _("1 week is max validity time"), true); }
# verify each recipient
if(strlen($_POST['email'])>0) {
	foreach (explode(",", $_POST['email']) as $rec) {
		if(!filter_var(trim($rec), FILTER_VALIDATE_EMAIL)) 			{ $Result->show("danger", _("Invalid email address")." - ".$rec, true); }
	}
}

# fetch object
$object = $Admin->fetch_object ($_POST['type'], "id", $_POST['id']);

if($_POST['type']=="subnets") {
	$tmp[] = "Share type: subnet";
	$tmp[] = "\t".$Subnets->transform_to_dotted($object->subnet)."/$object->mask";
	$tmp[] = "\t".$object->description;
}
else {
	$tmp[] = "Share type: IP address";
	$tmp[] = "\t".$Subnets->transform_to_dotted($object->ip_addr);
	$tmp[] = "\t".$object->description;
}

# set new access
$new_access[$_POST['code']] = array("id"=>$_POST['id'],
									"type"=>$_POST['type'],
									"code"=>$_POST['code'],
									"validity"=>strtotime($_POST['validity']),
									"userId"=>$User->user->id
									);

# create array of values for modification
$old_access = json_decode($User->settings->tempAccess, true);
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
if(strlen($_POST['email'])>0) {

	# fetch mailer settings
	$mail_settings = $Admin->fetch_object("settingsMail", "id", 1);

	# initialize mailer
	$phpipam_mail = new phpipam_mail($User->settings, $mail_settings);
	$phpipam_mail->initialize_mailer();

	// generate url
	$url = $Result->createURL().create_link("temp_share",$_POST['code']);

	// set subject
	$subject	= "New ipam share created";

	// set html content
	$content[] = "<table style='margin-left:10px;margin-top:5px;width:auto;padding:0px;border-collapse:collapse;'>";
	$content[] = "<tr><td style='padding:5px;margin:0px;color:#333;font-size:16px;text-shadow:1px 1px 1px white;border-bottom:1px solid #eeeeee;'><font face='Helvetica, Verdana, Arial, sans-serif' style='font-size:16px;'>$subject</font></td></tr>";

	$content[] = "<tr><td colspan='2'>Hi, new share was created on ".$User->settings->siteTitle.", available on following address:</td></tr>";
	$content[] = "<tr><td colspan='2'><a href='$url'><xmp>$url</xmp></a></td></tr>";
	$content[] = "<tr><td colsapn='2'><br>Details:<br>".implode("<br>", $tmp)."<br></td></tr>";
	$content[] = "<tr><td style='padding:5px;padding-left:15px;margin:0px;font-style:italic;padding-bottom:3px;text-align:right;color:#ccc;text-shadow:1px 1px 1px white;border-top:1px solid white;'><font face='Helvetica, Verdana, Arial, sans-serif' style='font-size:11px;'>Sent by user ".$User->user->real_name." at ".date('Y/m/d H:i')."</font></td></tr>";
	//set al content
	$content_plain[] = "$subject"."\r\n------------------------------\r\n";
	$content_plain[] = "Hi, new share was created on ".$User->settings->siteTitle.", available on following address:\r\n ".$url;
	$content_plain[] = "\r\nDetails: \r\n".implode("\r\n", $tmp)."\r\n";
	$content_plain[] = "\r\n\r\n"._("Sent by user")." ".$User->user->real_name." at ".date('Y/m/d H:i');
	$content[] = "</table>";

	// set alt content
	$content 		= $phpipam_mail->generate_message (implode("\r\n", $content));
	$content_plain 	= implode("\r\n",$content_plain);


	# try to send
	try {
		$phpipam_mail->Php_mailer->setFrom($mail_settings->mAdminMail, $mail_settings->mAdminName);
		foreach(explode(",", $_POST['email']) as $r) {
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
}
?>