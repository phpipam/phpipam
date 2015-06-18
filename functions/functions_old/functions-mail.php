<?php

/**
 * SendMail functions
 *
 */

/**
 *	Get all settings / needed for footer and mail settings
 */
$settings 		= getAllSettings();
$mailsettings 	= getAllMailSettings();

# get active user name */
$mail['sender'] = getActiveUserDetails();

# set html header
$mail['header'] = "<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.01 Transitional//EN' 'http://www.w3.org/TR/html4/loose.dtd'>
<html>
<head><meta http-equiv='Content-Type' content='text/html; charset=utf-8'></head>
<body style='margin:0px;padding:0px;background:#f9f9f9;border-collapse:collapse;'>
<table style='margin-left:10px;margin-top:5px;width:auto;padding:0px;border-collapse:collapse;'>";

# set html footer - single td
$mail['footer'] = "
<tr>
	<td style='padding:8px;margin:0px;'>
		<table>
		<tr>
			<td><font face='Helvetica, Verdana, Arial, sans-serif' style='font-size:13px;'>E-mail</font></td>
			<td><font face='Helvetica, Verdana, Arial, sans-serif' style='font-size:13px;'><a href='mailto:$settings[siteAdminMail]' style='color:#08c;'>$settings[siteAdminName]</a></font></td>
		</tr>
		<tr>
			<td><font face='Helvetica, Verdana, Arial, sans-serif' style='font-size:13px;'>www</font></td>
			<td><font face='Helvetica, Verdana, Arial, sans-serif' style='font-size:13px;'><a href='$settings[siteURL]' style='color:#08c;'>$settings[siteURL]</a></font></td>
		</tr>
		</table>
	</td>
</tr>
</table>
</body>
</html>";

# set html footer - double td
$mail['footer2'] = "
<tr>
	<td style='padding:8px;margin:0px;' colspan='2'>
		<table>
		<tr>
			<td><font face='Helvetica, Verdana, Arial, sans-serif' style='font-size:13px;'>E-mail</font></td>
			<td><font face='Helvetica, Verdana, Arial, sans-serif' style='font-size:13px;'><a href='mailto:$settings[siteAdminMail]' style='color:#08c;'>$settings[siteAdminName]</a></font></td>
		</tr>
		<tr>
			<td><font face='Helvetica, Verdana, Arial, sans-serif' style='font-size:13px;'>www</font></td>
			<td><font face='Helvetica, Verdana, Arial, sans-serif' style='font-size:13px;'><a href='$settings[siteURL]' style='color:#08c;'>$settings[siteURL]</a></font></td>
		</tr>
		</table>
	</td>
</tr>
</table>

</body>
</html>";

# set html footer - tripple td
$mail['footer4'] = "
<tr>
	<td style='padding:8px;margin:0px;padding-top:30px;' colspan='4'>
		<table>
		<tr>
			<td><font face='Helvetica, Verdana, Arial, sans-serif' style='font-size:13px;'>E-mail</font></td>
			<td><font face='Helvetica, Verdana, Arial, sans-serif' style='font-size:13px;'><a href='mailto:$settings[siteAdminMail]' style='color:#08c;'>$settings[siteAdminName]</a></font></td>
		</tr>
		<tr>
			<td><font face='Helvetica, Verdana, Arial, sans-serif' style='font-size:13px;'>www</font></td>
			<td><font face='Helvetica, Verdana, Arial, sans-serif' style='font-size:13px;'><a href='$settings[siteURL]' style='color:#08c;'>$settings[siteURL]</a></font></td>
		</tr>
		</table>
	</td>
</tr>
</table>

</body>
</html>";



# alt header
$mail['headerAlt'] = "";
# alt footer
$mail['footerAlt'] = "\r\n------------------------------\r\n$settings[siteAdminName] ($settings[siteAdminMail]) :: $settings[siteURL]";



/**
 *	phpMAiler initialize
 *	--------------------
 */
//require_once 'phpMailer/class.phpmailer.php';
require_once( dirname(__FILE__) . '/phpMailer/class.phpmailer.php' );
// initialize
$pmail = new PHPMailer(true);				//localhost
$pmail->CharSet="UTF-8";					//set utf8
$pmail->SMTPDebug = 0;						//debugging
$pmail->Debugoutput = 'html';				//debug type

//set smtp if required
if($mailsettings['mtype']=="smtp") {
	//set smtp
	$pmail->isSMTP();
	//tls, sll?
	if($mailsettings['msecure']=='ssl')	{
	$pmail->SMTPSecure = 'ssl';
	} elseif($mailsettings['msecure']=='tls')
	$pmail->SMTPSecure = 'tls';
	//server
	$pmail->Host = $mailsettings['mserver'];
	$pmail->Port = $mailsettings['mport'];
	//auth or not?
	if($mailsettings['mauth']=="yes") {
		$pmail->SMTPAuth = true;
		$pmail->Username = $mailsettings['muser'];
		$pmail->Password = $mailsettings['mpass'];
	} else {
		$pmail->SMTPAuth = false;
	}
}




/**
 *	Send IP address details mail
 */
function sendIPnotifEmail($to, $subject, $content)
{
	# get settings
	global $settings;
	global $mailsettings;
	global $mail;
	global $pmail;

	# reformat \n to breaks
	$contentarray = explode('\r\n', $content);

	# get active user name */
	$sender = getActiveUserDetails();

	# set html content
	$mail['content']  = $mail['header'];
	$mail['content'] .= "<tr><td style='padding:5px;margin:0px;color:#333;font-size:16px;text-shadow:1px 1px 1px white;border-bottom:1px solid #eeeeee;'><font face='Helvetica, Verdana, Arial, sans-serif' style='font-size:16px;'>$subject</font></td></tr>\n";
	foreach($contentarray as $c) {
	$mail['content'] .= "<tr><td style='padding:3px;padding-left:15px;margin:0px;'><font face='Helvetica, Verdana, Arial, sans-serif' style='font-size:13px;'>$c</font></td></tr>\n";
	}
	$mail['content'] .= "<tr><td style='padding:5px;padding-left:15px;margin:0px;font-style:italic;padding-bottom:3px;text-align:right;color:#ccc;text-shadow:1px 1px 1px white;border-top:1px solid white;'><font face='Helvetica, Verdana, Arial, sans-serif' style='font-size:11px;'>Sent by user ".$mail['sender']['real_name']." at ".date('Y/m/d H:i')."</font></td></tr>\n";
	$mail['content'] .= $mail['footer'];

	# Alt content - no html
	$mail['contentAltt']  = str_replace("\t", " ", $mail['contentAltt']);
	$mail['contentAltt']  = strip_tags($mail['contentAltt']);

	$mail['contentAlt']  = $mail['headerAlt'];
	$mail['contentAlt'] .= "$subject"."\r\n------------------------------\r\n\r\n";
	$mail['contentAlt'] .= $mail['contentAltt'];
	$mail['contentAlt'] .= "\r\n\r\n"._("Sent by user")." ".$mail['sender']['real_name']." at ".date('Y/m/d H:i');
	$mail['contentAlt'] .= $mail['footerAlt'];

	# set mail parameters
	try {
		$pmail->SetFrom($mailsettings['mAdminMail'], $mailsettings['mAdminName']);
		# check for multiple recepients
		$recipients = explode(",", $to);
		foreach($recipients as $r) {
		$pmail->AddAddress(trim($r));
		}
		$pmail->ClearReplyTos();
		$pmail->AddReplyTo($sender['email'], $sender['real_name']);
		// CC ender
		$pmail->AddCC($sender['email'], $sender['real_name']);
		// content
		$pmail->Subject = $subject;
		$pmail->AltBody = $mail['contentAlt'];

		$pmail->MsgHTML($mail['content']);

		# pošlji
		$pmail->Send();
	} catch (phpmailerException $e) {
	  	updateLogTable ("Sending notification mail failed!", $e->errorMessage(), 2);
	  	print "<div class='alert alert-danger'>".$e."</div>";
	  	return false;
	} catch (Exception $e) {
	  	updateLogTable ("Sending notification mail failed!", $e->errorMessage(), 2);
	  	print "<div class='alert alert-danger'>".$e."</div>";
		return false;
	}

	# write log for ok
	updateLogTable ("Sending notification mail to succeeded!", $to, 0);
	return true;
}


/**
 *	Send user account details
 */
function sendUserAccDetailsEmail($userDetails, $subject)
{
	# get settings
	global $settings;
	global $mailsettings;
	global $mail;
	global $pmail;

	# get active user name */
	$sender = getActiveUserDetails();

	# set html content
	$mail['content']  = $mail['header'];
	$mail['content'] .= "<tr><td style='padding:5px;margin:0px;color:#333;font-size:16px;text-shadow:1px 1px 1px white;border-bottom:1px solid #eeeeee;' colspan='2'><font face='Helvetica, Verdana, Arial, sans-serif' style='font-size:16px;'>$subject</font></td></tr>";
	$mail['content'] .= '<tr><td style="padding: 0px;padding-left:10px;margin:0px;line-height:18px;text-align:left;border-top:1px solid white;padding-top:10px;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">&bull; '._('Name').'</font></td>	  	<td style="padding: 0px;padding-left:15px;margin:0px;line-height:18px;text-align:left;padding-top:10px;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">'. $userDetails['real_name'] .'</font></td></tr>' . "\n";
	# we dont need pass for domain account
	if($userDetails['domainUser'] == 0) {
	$mail['content'] .= '<tr><td style="padding: 0px;padding-left:10px;margin:0px;line-height:18px;text-align:left;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">&bull; '._('Username').'</font></td>	<td style="padding: 0px;padding-left:15px;margin:0px;line-height:18px;text-align:left;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">'. $userDetails['username'] 	.'</font></td></tr>' . "\n";
	if(strlen($userDetails['plainpass']) != 0) {
	$mail['content'] .= '<tr><td style="padding: 0px;padding-left:10px;margin:0px;line-height:18px;text-align:left;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">&bull; '._('Password').'</font></td>	<td style="padding: 0px;padding-left:15px;margin:0px;line-height:18px;text-align:left;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">'. $userDetails['plainpass'] .'</font></td></tr>' . "\n";
	}
	}
	else {
	$mail['content'] .= '<tr><td style="padding: 0px;padding-left:10px;margin:0px;line-height:18px;text-align:left;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">&bull; '._('Username').'</font></td>	<td style="padding: 0px;padding-left:15px;margin:0px;line-height:18px;text-align:left;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">* '._('your domain username').' ('. $userDetails['username'] .')</font></td></tr>' . "\n";
	$mail['content'] .= '<tr><td style="padding: 0px;padding-left:10px;margin:0px;line-height:18px;text-align:left;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">&bull; '._('Password').'</font></td>	<td style="padding: 0px;padding-left:15px;margin:0px;line-height:18px;text-align:left;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">* '._('your domain password').'</font></td></tr>' . "\n";
	}
	$mail['content'] .= '<tr><td style="padding: 0px;padding-left:10px;margin:0px;line-height:18px;text-align:left;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">&bull; '._('Email').'</font></td>	<td style="padding: 0px;padding-left:15px;margin:0px;line-height:18px;text-align:left;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;"><a href="mailto:'.$userDetails['email'].'" style="color:#08c;">'.$userDetails['email'].'</a></font></td></tr>' . "\n";
	$mail['content'] .= '<tr><td style="padding: 0px;padding-left:10px;margin:0px;line-height:18px;text-align:left;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">&bull; '._('Role').'</font></td>		<td style="padding: 0px;padding-left:15px;margin:0px;line-height:18px;text-align:left;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">'. $userDetails['role'] 		.'</font></td></tr>' . "\n";
	$mail['content'] .= '<tr><td style="padding: 0px;padding-left:10px;margin:0px;line-height:18px;text-align:left;border-bottom:1px solid #eeeeee;padding-bottom:10px;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">&bull; '._('WebApp').'</font></td>	<td style="padding: 0px;padding-left:15px;margin:0px;line-height:18px;text-align:left;border-bottom:1px solid #eeeeee;padding-bottom:10px;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;"> <a href="'. $settings['siteURL'] .'" style="color:#08c;">'. $settings['siteURL']. '</font></a><td></tr>' . "\n";

	$mail['content'] .= "<tr><td style='padding:5px;padding-left:15px;margin:0px;font-style:italic;padding-bottom:3px;text-align:right;color:#ccc;text-shadow:1px 1px 1px white;border-top:1px solid white;' colspan='2'><font face='Helvetica, Verdana, Arial, sans-serif' style='font-size:11px;'>"._('Sent by user')." ".$mail['sender']['real_name']." at ".date('Y/m/d H:i')."</font></td></tr>";
	$mail['content'] .= $mail['footer2'];

	# plain
	$mail['contentAlt']  = $mail['headerAlt'];
	$mail['contentAlt'] .= "$subject"."\r\n------------------------------\r\n\r\n";

	$mail['contentAlt'] .= _("Name").": $userDetails[real_name]\r\n";
	# we dont need pass for domain account
	if($userDetails['domainUser'] == 0) {
	$mail['contentAlt'] .= _("Username").": $userDetails[username]\r\n";
	if(strlen($userDetails['plainpass']) != 0) {
	$mail['contentAlt'] .= _("Password").": $userDetails[plainpass]\r\n";
	}
	}
	else {
	$mail['contentAlt'] .= _("Username").": * your domain username($userDetails[username]\r\n";
	$mail['contentAlt'] .= _("Password").": * your domain password\r\n";
	}
	$mail['contentAlt'] .= _("Email").": $userDetails[email]\r\n";
	$mail['contentAlt'] .= _("Role").": $userDetails[role]\r\n";
	$mail['contentAlt'] .= _("WebApp").": $settings[siteURL]\r\n";
	$mail['contentAlt'] .= "\r\n"._("Sent by user")." ".$mail['sender']['real_name']." at ".date('Y/m/d H:i');
	$mail['contentAlt'] .= $mail['footerAlt'];

	# set mail parameters
	try {
		$pmail->SetFrom($mailsettings['mAdminMail'], $mailsettings['mAdminName']);
		$pmail->AddAddress($userDetails['email'], $userDetails['real_name']);
		$pmail->ClearReplyTos();
		$pmail->AddReplyTo($sender['email'], $sender['real_name']);
		// add admins to CC
		$admins = getAllAdminUsers ();
		foreach($admins as $admin) {
			if($admin['mailNotify']=="Yes") {
			$pmail->AddAddress($admin['email']);
		}	}
		// content
		$pmail->Subject = $subject;
		$pmail->AltBody = $mail['contentAlt'];

		$pmail->MsgHTML($mail['content']);

		# pošlji
		$pmail->Send();
	} catch (phpmailerException $e) {
	  	updateLogTable ("Sending notification mail for new account failed!", $e->errorMessage(), 2);
	  	return false;
	} catch (Exception $e) {
	  	updateLogTable ("Sending notification mail for new account failed!", $e->errorMessage(), 2);
		return false;
	}

	# write log for ok
	updateLogTable ("Sending notification mail for new account succeeded!", $userDetails['email'], 0);
	return true;
}


/**
 *	Send IP request mail
 */
function sendIPReqEmail($request)
{
	# get settings
	global $settings;
	global $mailsettings;
	global $mail;
	global $pmail;

	# get active user name */
	$sender = getActiveUserDetails();

	# get subnet details
	$subnet = getSubnetDetailsById($request['subnetId']);
	$subnet2 = Transform2long($subnet['subnet'])."/".$subnet['mask'];

	# get section detaiils
	$section = getSectionDetailsById($subnet['sectionId']);

	# set subject
	$subject	= _('New IP address request in subnet').' '.$subnet2;

	# reformat \n to breaks for comments
	$request['comment'] = str_replace("\n", "<br>", $request['comment']);

	# set html content
	$mail['content']  = $mail['header'];
	$mail['content'] .= "<tr><td style='padding:5px;margin:0px;color:#333;font-size:16px;text-shadow:1px 1px 1px white;border-bottom:1px solid #eeeeee;' colspan='2'><font face='Helvetica, Verdana, Arial, sans-serif' style='font-size:16px;'>$subject</font></td></tr>";
	$mail['content'] .= '<tr><td style="padding: 0px;padding-left:10px;margin:0px;line-height:18px;text-align:left;border-top:1px solid white;padding-top:10px;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">&bull; '._('Section').'   	</font></td><td style="padding: 0px;padding-left:10px;margin:0px;line-height:18px;text-align:left;border-top:1px solid white;padding-top:10px;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">'. $section['name'] .' ('.$section['description'].')</font></td></tr>' . "\n";
	$mail['content'] .= '<tr><td style="padding: 0px;padding-left:10px;margin:0px;line-height:18px;text-align:left;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">&bull; '._('Subnet').'				</font></td>	<td style="padding: 0px;padding-left:10px;margin:0px;line-height:18px;text-align:left;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">'. $subnet2 .' ('.$subnet['description'].')</font></td></tr>' . "\n";
	$mail['content'] .= '<tr><td style="padding: 0px;padding-left:10px;margin:0px;line-height:18px;text-align:left;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">&bull; '._('Description').'		 	</font></td>	<td style="padding: 0px;padding-left:10px;margin:0px;line-height:18px;text-align:left;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">'. $request['description'] .'</font></td></tr>' . "\n";
	$mail['content'] .= '<tr><td style="padding: 0px;padding-left:10px;margin:0px;line-height:18px;text-align:left;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">&bull; '._('Hostname').'			</font></td>	<td style="padding: 0px;padding-left:10px;margin:0px;line-height:18px;text-align:left;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">'. $request['dns_name'] .'</font></td></tr>' . "\n";
	$mail['content'] .= '<tr><td style="padding: 0px;padding-left:10px;margin:0px;line-height:18px;text-align:left;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">&bull; '._('Owner').'				</font></td>	<td style="padding: 0px;padding-left:10px;margin:0px;line-height:18px;text-align:left;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">'. $request['owner'] .'</font></td></tr>' . "\n";
	$mail['content'] .= '<tr><td style="padding: 0px;padding-left:10px;margin:0px;line-height:18px;text-align:left;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">&bull; '._('Requested by').'		</font></td>	<td style="padding: 0px;padding-left:10px;margin:0px;line-height:18px;text-align:left;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;"><a href="mailto:'.$request['requester'].'" style="color:#08c;">'. $request['requester'] .'</a></font></td></tr>' . "\n";
	$mail['content'] .= '<tr><td style="padding: 0px;padding-left:10px;margin:0px;line-height:18px;text-align:left;vertical-align:top;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">&bull; '._('Comment').'	</font></td>	<td style="padding: 0px;padding-left:10px;margin:0px;line-height:18px;text-align:left;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">'. $request['comment'] .'</font></td></tr>' . "\n";
	$mail['content'] .= "<tr><td style='padding:5px;padding-left:15px;margin:0px;font-style:italic;padding-bottom:3px;text-align:right;color:#ccc;text-shadow:1px 1px 1px white;border-top:1px solid white;' colspan='2'><font face='Helvetica, Verdana, Arial, sans-serif' style='font-size:11px;'>"._('Sent at')." ".date('Y/m/d H:i')."</font></td></tr>";
	$mail['content'] .= $mail['footer2'];

	# alt content
	$request['comment'] = str_replace("<br>", "\r\n", $request['comment']);

	$mail['contentAlt']  = $mail['headerAlt'];
	$mail['contentAlt'] .= "$subject"."\r\n------------------------------\r\n\r\n";
	$mail['contentAlt'] .= _("Section").": $section[name] ($section[description])\r\n";
	$mail['contentAlt'] .= _("Subnet").": $subnet2 ($subnet[description]\r\n";
	$mail['contentAlt'] .= _("Description").": $request[description]\r\n";
	$mail['contentAlt'] .= _("Hostname").": $request[dns_name]\r\n";
	$mail['contentAlt'] .= _("Owner").": $request[owner]\r\n";
	$mail['contentAlt'] .= _("Requested by").": $request[requester]\r\n";
	$mail['contentAlt'] .= _("Comment").": $request[comment]\r\n";
	$mail['contentAlt'] .= "\r\n"._("Sent at")." ".date('Y/m/d H:i');
	$mail['contentAlt'] .= $mail['footerAlt'];

	# set mail parameters
	try {
		$pmail->SetFrom($mailsettings['mAdminMail'], $mailsettings['mAdminName']);

		// add admins to TO
		$admins = getAllAdminUsers ();
		foreach($admins as $admin) {
			if($admin['mailNotify']=="Yes") {
			$pmail->AddAddress($admin['email']);
		}	}
		$pmail->ClearReplyTos();
		$pmail-> AddReplyTo($mailsettings['mAdminMail'], $mailsettings['mAdminName']);
		// send copy to requester
		$pmail->AddCC($request['requester']);

		// content
		$pmail->Subject = $subject;
		$pmail->AltBody = $mail['contentAlt'];

		$pmail->MsgHTML($mail['content']);

		# pošlji
		$pmail->Send();
	} catch (phpmailerException $e) {
	  	updateLogTable ("New IP request mail sending failed", "Sending notification mail to $mail[recipients] failed!\n".$e->errorMessage(), 2);
	  	return false;
	} catch (Exception $e) {
	  	updateLogTable ("New IP request mail sending failed", "Sending notification mail to $mail[recipients] failed!\n".$e->errorMessage(), 2);
		return false;
	}

	# write log for ok
	updateLogTable ("New IP request mail sent ok", "Sending notification mail to $mail[recipients] succeeded!", $severity = 0);
	return true;
}


/**
 *	Send IP result mail - reject or confirm reservation
 */
function sendIPResultEmail($request)
{
	# get settings
	global $settings;
	global $mailsettings;
	global $mail;
	global $pmail;

	# set subject based on action
	if($request['action'] == "accept")  { $subject	= _("IP address request")." (".Transform2long($request['ip_addr']).") "._("$request[action]ed"); }
	else								{ $subject	= _("IP address request $request[action]ed"); }

	# get active user name */
	$sender = getActiveUserDetails();

	# get subnet details
	$subnet = getSubnetDetailsById($request['subnetId']);
	$subnet2 = Transform2long($subnet['subnet'])."/".$subnet['mask'];

	# get section detaiils
	$section = getSectionDetailsById($subnet['sectionId']);

	# reformat \n to breaks
	$request['comment'] 	 = str_replace("\n", "<br>", $request['comment']);
	$request['adminComment'] = str_replace("\n", "<br>", $request['adminComment']);

	# set html content
	$mail['content']  = $mail['header'];
	$mail['content'] .= "<tr><td style='padding:5px;margin:0px;color:#333;font-size:16px;text-shadow:1px 1px 1px white;border-bottom:1px solid #eeeeee;' colspan='2'><font face='Helvetica, Verdana, Arial, sans-serif' style='font-size:16px;'>$subject</font></td></tr>";
	$mail['content'] .= '<tr><td style="padding: 0px;padding-left:10px;margin:0px;line-height:18px;text-align:left;border-top:1px solid white;padding-top:10px;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">&bull; '._('Section').'   	</font></td><td style="padding: 0px;padding-left:10px;margin:0px;line-height:18px;text-align:left;border-top:1px solid white;padding-top:10px;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">'. $section['name'] .' ('.$section['description'].')</font></td></tr>' . "\n";
	$mail['content'] .= '<tr><td style="padding: 0px;padding-left:10px;margin:0px;line-height:18px;text-align:left;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">&bull; '._('Subnet').'   			</font></td>	<td style="padding: 0px;padding-left:10px;margin:0px;line-height:18px;text-align:left;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">'. $subnet2 .'</font></td></tr>' . "\n";
	if($request['action'] == "accept") {
	$mail['content'] .= '<tr><td style="padding: 0px;padding-left:10px;margin:0px;line-height:18px;text-align:left;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">&bull; '._('assigned IP address').'	</font></td>	<td style="padding: 0px;padding-left:10px;margin:0px;line-height:18px;text-align:left;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">'. Transform2long($request['ip_addr']) .'</font></td></tr>' . "\n";
	}
	$mail['content'] .= '<tr><td style="padding: 0px;padding-left:10px;margin:0px;line-height:18px;text-align:left;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">&bull; '._('Description').'		 	</font></td>	<td style="padding: 0px;padding-left:10px;margin:0px;line-height:18px;text-align:left;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">'. $request['description'] .'</font></td></tr>' . "\n";
	$mail['content'] .= '<tr><td style="padding: 0px;padding-left:10px;margin:0px;line-height:18px;text-align:left;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">&bull; '._('Hostname').'			 </font></td>	<td style="padding: 0px;padding-left:10px;margin:0px;line-height:18px;text-align:left;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">'. $request['dns_name'] .'</font></td></tr>' . "\n";
	$mail['content'] .= '<tr><td style="padding: 0px;padding-left:10px;margin:0px;line-height:18px;text-align:left;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">&bull; '._('Owner').'				</font></td>	<td style="padding: 0px;padding-left:10px;margin:0px;line-height:18px;text-align:left;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">'. $request['owner'] .'</font></td></tr>' . "\n";
	$mail['content'] .= '<tr><td style="padding: 0px;padding-left:10px;margin:0px;line-height:18px;text-align:left;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">&bull; '._('Requested from').'		</font></td>	<td style="padding: 0px;padding-left:10px;margin:0px;line-height:18px;text-align:left;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;"><a href="mailto:'.$request['requester'].'" style="color:#08c;">'. $request['requester'] .'</a></font></td></tr>' . "\n";
	$mail['content'] .= '<tr><td style="padding: 0px;padding-left:10px;margin:0px;line-height:18px;text-align:left;vertical-align:top;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">&bull; '._('Comment (request)').'	</font></td>	<td style="padding: 0px;padding-left:10px;margin:0px;line-height:18px;text-align:left;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">'. $request['comment'] .'</font></td></tr>' . "\n";
	$mail['content'] .= '<tr><td style="padding: 0px;padding-left:10px;margin:0px;line-height:18px;text-align:left;vertical-align:top;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">&bull; '._('Admin accept/reject comment').'	</font></td>	<td style="padding: 0px;padding-left:10px;margin:0px;line-height:18px;text-align:left;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px; font-weight:bold;">'. $request['adminComment'] .'</font></td></tr>' . "\n";
	$mail['content'] .= "<tr><td style='padding:5px;padding-left:15px;margin:0px;font-style:italic;padding-bottom:3px;text-align:right;color:#ccc;text-shadow:1px 1px 1px white;border-top:1px solid white;' colspan='2'><font face='Helvetica, Verdana, Arial, sans-serif' style='font-size:11px;'>"._('Sent by user')." ".$mail['sender']['real_name']." at ".date('Y/m/d H:i')."</font></td></tr>";
	$mail['content'] .= $mail['footer2'];

	# alt content
	$request['comment'] = str_replace("<br>", "\r\n", $request['comment']);
	$request['adminComment'] = str_replace("<br>", "\r\n", $request['adminComment']);

	$mail['contentAlt']  = $mail['headerAlt'];
	$mail['contentAlt'] .= "$subject"."\r\n------------------------------\r\n\r\n";
	$mail['contentAlt'] .= _("Section").":  $section[name] ($section[description])\r\n";
	$mail['contentAlt'] .= _("Subnet").":  $subnet2\r\n";
	if($request['action'] == "accept") {
	$mail['contentAlt'] .= _("Assigned IP address").":  ". Transform2long($request['ip_addr']) ."\r\n";
	}
	$mail['contentAlt'] .= _("Description").":  $request[description]\r\n";
	$mail['contentAlt'] .= _("Hostname").":  $request[dns_name]\r\n";
	$mail['contentAlt'] .= _("Owner").":  $request[owner]\r\n";
	$mail['contentAlt'] .= _("Requested by").":  $request[requester]\r\n";
	$mail['contentAlt'] .= _("Comment (request)").":  $request[comment]\r\n";
	$mail['contentAlt'] .= _("Admin accept/reject comment").":  $request[adminComment]\r\n";
	$mail['contentAlt'] .= "\r\nSent by user ".$mail['sender']['real_name']." at ".date('Y/m/d H:i');
	$mail['contentAlt'] .= $mail['footerAlt'];


	# set mail parameters
	try {
		$pmail->SetFrom($mailsettings['mAdminMail'], $mailsettings['mAdminName']);
		// send to requester
		$pmail->AddAddress($request['requester']);
		// add admins to CC
		$admins = getAllAdminUsers ();
		foreach($admins as $admin) {
			$pmail->AddCC($admin['email']);
		}
		$pmail->ClearReplyTos();
		$pmail-> AddReplyTo($mailsettings['mAdminMail'], $mailsettings['mAdminName']);

		// content
		$pmail->Subject = $subject;
		$pmail->AltBody = $mail['contentAlt'];

		$pmail->MsgHTML($mail['content']);

		# pošlji
		$pmail->Send();
	} catch (phpmailerException $e) {
	  	updateLogTable ("IP request response mail (confirm,reject) sending failed", "Sending notification mail to $mail[recipients] failed!\n".$e->errorMessage(), 2);
	  	return false;
	} catch (Exception $e) {
	  	updateLogTable ("IP request response mail (confirm,reject) sending failed", "Sending notification mail to $mail[recipients] failed!\n".$e->errorMessage(), 2);
		return false;
	}

	# write log for ok
	updateLogTable ("IP request response mail (confirm,reject) sent ok", "Sending notification mail to $mail[recipients] succeeded!", 0);
	return true;
}



/**
 *	Send IP address details mail
 *
 *		type > IP, subnet, vlan, vrf
 *		action
 *		objectOld, objectNew > object details array
 */
function sendObjectUpdateMails($type, $action, $objectOld, $objectNew, $iprange = false)
{
	# get settings
	global $settings;
	global $mailsettings;
	global $mail;
	global $pmail;

	# ip range?
	if($iprange) {
		# subject
		$subject = "New IP range $action notification";
		# set reference object
		$objectSelected = $objectNew;
	}
	# set content based on action
	elseif($action == "add") {
		# subject
		$subject = "New $type notification";
		# unset unneeded variables
		unset($objectOld);
		unset($objectNew['lastSeen'],$objectNew['editDate'],$objectNew['isFolder']);
		# set reference object
		$objectSelected = $objectNew;
	}
	elseif($action == "edit") {
		# subject
		$subject = "$type modification notification";
		# unset unneeded variables
		unset($objectNew['lastSeen'],$objectNew['editDate'],$objectNew['isFolder'],$objectNew['id']);
		unset($objectOld['lastSeen'],$objectOld['editDate'],$objectOld['isFolder'],$objectNew['id'],$objectOld['permissions']);

		# set reference object
		$objectSelected = $objectOld;
	}
	elseif($action == "delete") {
		# subject
		$subject = "$type delete notification";
		# unset unneeded variables
		unset($objectNew);

		# set reference object
		$objectSelected = $objectOld;
	}

	# sec default tdstyle
	$tdstyle = "padding:2px;padding-left:10px;margin:0px;border-top:1px solid #eeeeee;border-bottom:1px solid #eeeeee;padding-top:3px;padding-bottom:3px;";
	$font    = "Helvetica, Verdana, Arial, sans-serif";

	# content
	$content  = "<tr><td colspan='4' style='padding-top:30px;'></td></tr>\n";
	$content .= "<tr><td style='$tdstyle'><strong>Field</strong></td><td style='$tdstyle'><strong>Old</strong></td><td style='$tdstyle'></td><td style='$tdstyle'><strong>New</strong></td></tr>\n";

	$change = 0;
	foreach($objectSelected as $k=>$l) {

		$objectNew[$k] = filter_user_input ($objectNew[$k], false, true, false);
		$objectOld[$k] = filter_user_input ($objectOld[$k], false, true, false);

		// only mail if change
		if($objectOld[$k] != $objectNew[$k]) {

			if(strlen($objectNew[$k])==0) { $objectNew[$k] = " /"; }
			if(strlen($objectOld[$k])==0) { $objectOld[$k] = " /"; }

			$content .= "<tr>";
			$content .= "<td style='$tdstyle'><font face='$font' style='font-size:12px;'>$k</font></td>";
			$content .= "<td style='$tdstyle'><font face='$font' style='font-size:12px;'>$objectOld[$k]</font></td>";
			$content .= "<td style='$tdstyle'><font face='$font' style='font-size:12px;'> => </font></td>";
			$content .= "<td style='$tdstyle'><font face='$font' style='font-size:12px;'>$objectNew[$k]</font></td>";
			$content .= "</tr>\n";

			$change++;
		}
	}


	# set html content
	$mail['content']  = $mail['header'];
	$mail['content'] .= $content;
	$mail['content'] .= $mail['footer4'];

	# Alt content - no html
	$mail['contentAltt']  = str_replace("<br>", "\r\n", $content);
	$mail['contentAltt']  = str_replace("\t", " ", $mail['contentAltt']);
	$mail['contentAltt']  = strip_tags($mail['contentAltt']);

	$mail['contentAlt']  = $mail['headerAlt'];
	$mail['contentAlt'] .= "$subject"."\r\n------------------------------\r\n\r\n";
	$mail['contentAlt'] .= "$mail[contentAltt]";
	$mail['contentAlt'] .= $mail['footerAlt'];

	# send only if change
	if($change>0) {
		# set mail parameters
		try {
			$pmail->SetFrom($mailsettings['mAdminMail'], $mailsettings['mAdminName']);
			// add admins
			$admins = getAllAdminUsers ();
			foreach($admins as $admin) {
				if($admin['mailChangelog']=="Yes") {
				$pmail->AddAddress($admin['email']);
			}	}
			$pmail->ClearReplyTos();
			// content
			$pmail->Subject = $subject;
			$pmail->AltBody = $mail['contentAlt'];

			$pmail->MsgHTML($mail['content']);

			# pošlji
			$pmail->Send();
		} catch (phpmailerException $e) {
		  	updateLogTable ("Sending change notification mail failed!", $e->errorMessage(), 2);
		  	return false;
		} catch (Exception $e) {
		  	updateLogTable ("Sending change notification mail failed!", $e->errorMessage(), 2);
			return false;
		}
	}

	return true;
}




/**
 *	Send status update mail
 */
function sendStatusUpdateMail($content, $subject)
{
	# get settings
	global $settings;
	global $mailsettings;
	global $mail;
	global $pmail;

	# add plain text
	$contentAlt = str_replace("<th style='padding:3px 8px;border:1px solid silver;border-bottom:2px solid gray;'>", " | ", $content);	//replace th
	$contentAlt = str_replace("<td style='padding:3px 8px;border:1px solid silver;'>", " | ", $contentAlt);								//replace td
	$contentAlt = str_replace("</tr>", "\n", $contentAlt);																				//add breaks
	$contentAlt = strip_tags($contentAlt);																								//strip rest of html

	# set mail parameters
	try {
		$pmail->SetFrom($mailsettings['mAdminMail'], $mailsettings['mAdminName']);
		// add admins to CC
		$admins = getAllAdminUsers ();

		foreach($admins as $admin) {
			if($admin['mailNotify']=="Yes") {
			$pmail->AddAddress($admin['email']);
		}	}
		// content
		$pmail->Subject = $subject;
		$pmail->AltBody = $mail['contentAlt'];

		$pmail->MsgHTML($content);

		# pošlji
		$pmail->Send();
	} catch (phpmailerException $e) {
	  	updateLogTable ("Sending notification mail for IP address state change failed!", $e->errorMessage(), 2);
	  	return false;
	} catch (Exception $e) {
	  	updateLogTable ("Sending notification mail for IP address state change failed!", $e->errorMessage(), 2);
		return false;
	}

	# write log for ok
	updateLogTable ("Sending notification mail for IP address state change succeeded!", null, 0);
	return true;
}

?>