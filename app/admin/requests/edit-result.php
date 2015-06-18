<?php

/**
 * Script to confirm / reject IP address request
 ***********************************************/

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Addresses	= new Addresses ($Database);
$Tools		= new Tools ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();


# fetch custom fields
$custom = $Tools->fetch_custom_fields('ipaddresses');
if(sizeof($custom) > 0) {
	foreach($custom as $myField) {
		if(isset($_POST[$myField['name']])) { $_POST[$myField['name']] = $_POST[$myField['name']];}
	}
}

# fetch subnet
$subnet = (array) $Admin->fetch_object("subnets", "id", $_POST['subnetId']);

/* if action is reject set processed and accepted to 1 and 0 */
if($_POST['action'] == "reject") {
	//set reject values
	$values = array("id"=>$_POST['requestId'],
					"processed"=>1,
					"accepted"=>0,
					"adminComment"=>@$_POST['adminComment']
					);
	if(!$Admin->object_modify("requests", "edit", "id", $values))		{ $Result->show("danger",  _("Failed to reject IP request"), true); }
	else																{ $Result->show("success", _("Request has beed rejected"), false); }
}
/* accept */
else {
	// fetch subnet
	$subnet_temp = $Addresses->transform_to_dotted ($subnet['subnet'])."/".$subnet['mask'];

	//verify IP and subnet
	$Addresses->verify_address( $Addresses->transform_address($_POST['ip_addr'], "dotted"), $subnet_temp, false, true);

	//check if already existing and die
	if ($Addresses->address_exists($Addresses->transform_address($_POST['ip_addr'], "decimal"), $subnet['id'])) { $Result->show("danger", _('IP address already exists'), true); }

	//insert to ipaddresses table
	$values = array("action"=>"add",
					"ip_addr"=>$Addresses->transform_address($_POST['ip_addr'],"decimal"),
					"subnetId"=>$_POST['subnetId'],
					"description"=>@$_POST['description'],
					"dns_name"=>@$_POST['dns_name'],
					"mac"=>@$_POST['mac'],
					"owner"=>@$_POST['owner'],
					"state"=>@$_POST['state'],
					"switch"=>@$_POST['switch'],
					"port"=>@$_POST['port'],
					"note"=>@$_POST['note']
					);
	if(!$Addresses->modify_address($values))	{ $Result->show("danger",  _("Failed to create IP address"), true); }

	//accept message
	$values = array("id"=>$_POST['requestId'],
					"processed"=>1,
					"accepted"=>1,
					"adminComment"=>$comment
					);
	if(!$Admin->object_modify("requests", "edit", "id", $values))		{ $Result->show("danger",  _("Cannot confirm IP address"), true); }
	else																{ $Result->show("success", _("IP request accepted/rejected"), false); }
}


/* send email, all is ok */


# fetch mailer settings
$mail_settings = $Admin->fetch_object("settingsMail", "id", 1);

# initialize mailer
require( dirname(__FILE__) . '/../../../functions/classes/class.Mail.php');
$phpipam_mail = new phpipam_mail($User->settings, $mail_settings);
//create object
$phpipam_mail->initialize_mailer();


# subject
if($_POST['action'] == "accept")  	{ $subject = _("IP address request")." $_POST[ip_addr] "._("$_POST[action]ed"); }
else								{ $subject = _("IP address request $_POST[action]ed"); }

# set HTML content
$content[] = "<table style='margin-left:10px;margin-top:5px;width:auto;padding:0px;border-collapse:collapse;'>";
$content[] = "<tr><td style='padding:5px;margin:0px;color:#333;font-size:16px;text-shadow:1px 1px 1px white;border-bottom:1px solid #eeeeee;' colspan='2'><font face='Helvetica, Verdana, Arial, sans-serif' style='font-size:16px;'>$subject</font></td></tr>";
$content[] = '<tr><td style="padding: 0px;padding-left:10px;margin:0px;line-height:18px;text-align:left;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">&bull; '._('Subnet').'   			</font></td>	<td style="padding: 0px;padding-left:10px;margin:0px;line-height:18px;text-align:left;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">'. $Addresses->transform_to_dotted($subnet['subnet'])."/".$subnet['mask'] .'</font></td></tr>' . "\n";
if($_POST['action'] == "accept")
$content[] = '<tr><td style="padding: 0px;padding-left:10px;margin:0px;line-height:18px;text-align:left;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">&bull; '._('assigned IP address').'</font></td>	<td style="padding: 0px;padding-left:10px;margin:0px;line-height:18px;text-align:left;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">'. $Addresses->transform_address($_POST['ip_addr'], "dotted") .'</font></td></tr>' . "\n";
$content[] = '<tr><td style="padding: 0px;padding-left:10px;margin:0px;line-height:18px;text-align:left;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">&bull; '._('Description').'		</font></td>	<td style="padding: 0px;padding-left:10px;margin:0px;line-height:18px;text-align:left;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">'. @$_POST['description'] .'</font></td></tr>' . "\n";
$content[] = '<tr><td style="padding: 0px;padding-left:10px;margin:0px;line-height:18px;text-align:left;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">&bull; '._('Hostname').'			</font></td>	<td style="padding: 0px;padding-left:10px;margin:0px;line-height:18px;text-align:left;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">'. @$_POST['dns_name'] .'</font></td></tr>' . "\n";
$content[] = '<tr><td style="padding: 0px;padding-left:10px;margin:0px;line-height:18px;text-align:left;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">&bull; '._('Owner').'				</font></td>	<td style="padding: 0px;padding-left:10px;margin:0px;line-height:18px;text-align:left;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">'. @$_POST['owner'] .'</font></td></tr>' . "\n";
$content[] = '<tr><td style="padding: 0px;padding-left:10px;margin:0px;line-height:18px;text-align:left;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">&bull; '._('Requested from').'		</font></td>	<td style="padding: 0px;padding-left:10px;margin:0px;line-height:18px;text-align:left;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;"><a href="mailto:'.@$_POST['requester'].'" style="color:#08c;">'. @$_POST['requester'] .'</a></font></td></tr>' . "\n";
$content[] = '<tr><td style="padding: 0px;padding-left:10px;margin:0px;line-height:18px;text-align:left;vertical-align:top;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">&bull; '._('Comment (request)').'	</font></td>	<td style="padding: 0px;padding-left:10px;margin:0px;line-height:18px;text-align:left;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">'. @$_POST['comment'] .'</font></td></tr>' . "\n";
$content[] = '<tr><td style="padding: 0px;padding-left:10px;margin:0px;line-height:18px;text-align:left;vertical-align:top;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px;">&bull; '._('Admin accept/reject comment').'	</font></td>	<td style="padding: 0px;padding-left:10px;margin:0px;line-height:18px;text-align:left;"><font face="Helvetica, Verdana, Arial, sans-serif" style="font-size:13px; font-weight:bold;">'. @$_POST['adminComment'] .'</font></td></tr>' . "\n";
$content[] = "<tr><td style='padding:5px;padding-left:15px;margin:0px;font-style:italic;padding-bottom:3px;text-align:right;color:#ccc;text-shadow:1px 1px 1px white;border-top:1px solid white;' colspan='2'><font face='Helvetica, Verdana, Arial, sans-serif' style='font-size:11px;'>"._('Sent by user')." ".$User->user->real_name." at ".date('Y/m/d H:i')."</font></td></tr>";
$content[] = "</table>";

# alt content
$content_plain[] = "$subject"."\r\n------------------------------";
$content_plain[] = _("Subnet").": ".$Addresses->transform_to_dotted($subnet['subnet'])."/".$subnet['mask'] ;
if($_POST['action'] == "accept")
$content_plain[] = _("Assigned IP address").":  ".$Addresses->transform_address($_POST['ip_addr'], "dotted");
$content_plain[] = _("Description").":  $_POST[description]";
$content_plain[] = _("Hostname").":  $_POST[dns_name]";
$content_plain[] = _("Owner").":  $_POST[owner]";
$content_plain[] = _("Requested by").":  $_POST[requester]";
$content_plain[] = _("Comment (request)").":  ".str_replace("<br>", "\r\n", $_POST['comment'])."";
$content_plain[] = _("Admin accept/reject comment").":  ".str_replace("<br>", "\r\n", $_POST['adminComment']);


# get mail content
$content 		= $phpipam_mail->generate_message (implode("\n", $content));
$content_plain 	= $phpipam_mail->generate_message_plain (implode("\r\n", $content_plain));


# try to send
try {
	$phpipam_mail->Php_mailer->setFrom($User->user->email, $User->user->real_name);
	$phpipam_mail->Php_mailer->addAddress($_POST['requester']);
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

//print ok
$Result->show("success", _("Sending mail for IP request succeeded"), true);

?>