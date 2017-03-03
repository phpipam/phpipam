<?php

/**
 * This script will remove offline addresses after they have been down for
 * predefined number of hours.
 *
 * Subnets with "Ping check" enabled will be used for checking offline addresses.
 *
 */

# script can only be run from cli
if(php_sapi_name()!="cli") 						{ die("This script can only be run from cli!"); }

# include required scripts
require( dirname(__FILE__) . '/../functions.php' );

# initialize objects
$Database 	= new Database_PDO;
$Addresses	= new Addresses ($Database);
$Subnets	= new Subnets ($Database);
$Result		= new Result();


// response for mailing
$removed_addresses = array();			// Array with differences, can be used to email to admins

// if config is not set die
if(!isset($config['removed_addresses_timelimit'])) { die("Please set timelimit for address removal!"); }

// set now for whole script
$now     = time();
$nowdate = date ("Y-m-d H:i:s");
$beforetime = date ("Y-m-d H:i:s", (time()-$config['removed_addresses_timelimit']));

// set query to fetch addresses and belongign subnets
$query = "select
			`ip`.`id`,`ip`.`ip_addr`,`ip`.`lastSeen`,`ip`.`subnetId`,`ip`.`description`,`ip`.`dns_name`,`ip`.`lastSeen`,
			`su`.`subnet`,`su`.`mask`,`su`.`sectionId`,`su`.`description`,
			'delete' as `action`
		 from
		 	`ipaddresses` as `ip`, `subnets` as `su`
		 where
			`ip`.`subnetId` = `su`.`id`
			and `su`.`pingSubnet` = 1
			and `ip`.`excludePing` != 1
			and
			(`ip`.`lastSeen` < '$beforetime' and `ip`.`lastSeen` != '0000-00-00 00:00:00' and `ip`.`lastSeen` is not NULL);";

# fetch
try { $offline_addresses = $Database->getObjectsQuery($query); }
catch (Exception $e) {
	$Result->show("danger", _("Error: ").$e->getMessage());
	die();
}

# if none die, none to remove
if (sizeof($offline_addresses)==0) {
	die();
}
# remove
else {
	foreach ($offline_addresses as $a) {
		// save
		$removed_addresses[] = $a;
		// remove
		$Addresses->modify_address ($a);
	}
}


# all done, mail diff?
if(sizeof($removed_addresses)>0 && $config['removed_addresses_send_mail']) {
	# settings
	$Subnets->get_settings ();
	# check for recipients
	foreach($Subnets->fetch_multiple_objects ("users", "role", "Administrator") as $admin) {
		if($admin->mailNotify=="Yes") {
			$recepients[] = array("name"=>$admin->real_name, "email"=>$admin->email);
		}
	}
	# none?
	if(!isset($recepients))	{ die(); }

	# fetch mailer settings
	$mail_settings = $Subnets->fetch_object("settingsMail", "id", 1);
	# fake user object, needed for create_link
	$User = new StdClass();

	# initialize mailer
	$phpipam_mail = new phpipam_mail($Subnets->settings, $mail_settings);
	$phpipam_mail->initialize_mailer();

	// set subject
	$subject	= "phpipam deleted offline addresses at ".$nowdate;

	//html
	$content[] = "<p style='margin-left:10px;'>$Subnets->mail_font_style <font style='font-size:16px;size:16px;'>phpipam removed inactive addresses at ".$nowdate."</font></font></p><br>";

	$content[] = "<table style='margin-left:10px;margin-top:5px;width:auto;padding:0px;border-collapse:collapse;border:1px solid #ccc;'>";
	$content[] = "<tr>";
	$content[] = "	<th style='padding:3px 8px;border:1px solid #ccc;border-bottom:2px solid gray;white-space:nowrap;'>$Subnets->mail_font_style IP</font></th>";
	$content[] = "	<th style='padding:3px 8px;border:1px solid #ccc;border-bottom:2px solid gray;'>$Subnets->mail_font_style Description</font></th>";
	$content[] = "	<th style='padding:3px 8px;border:1px solid #ccc;border-bottom:2px solid gray;'>$Subnets->mail_font_style Hostname</font></th>";
	$content[] = "	<th style='padding:3px 8px;border:1px solid #ccc;border-bottom:2px solid gray;'>$Subnets->mail_font_style Subnet</font></th>";
	$content[] = "	<th style='padding:3px 8px;border:1px solid #ccc;border-bottom:2px solid gray;'>$Subnets->mail_font_style Last seen</font></th>";
	$content[] = "</tr>";

	//plain
	$content_plain[] = "phpipam deleted offline addresses at ".$nowdate."\r\n------------------------------";

	//Changes
	foreach($removed_addresses as $change) {
		// to array
		$change = (array) $change;
		//set subnet
		$subnet = $Subnets->fetch_subnet(null, $change['subnetId']);

        // desc
		$change['description'] = strlen($change['description'])>0 ? "$Subnets->mail_font_style $change[description]</font>" : "$Subnets->mail_font_style / </font>";
		// subnet desc
		$subnet->description = strlen($subnet->description)>0 ? "$Subnets->mail_font_style $subnet->description</font>" : "$Subnets->mail_font_style / </font>";

		//content
		$content[] = "<tr>";
		$content[] = "	<td style='padding:3px 8px;border:1px solid #ccc;'>$Subnets->mail_font_style ".$Subnets->transform_to_dotted($change['ip_addr'])."</font></td>";
		$content[] = "	<td style='padding:3px 8px;border:1px solid #ccc;'>$Subnets->mail_font_style $change[description]</font></td>";
		$content[] = "	<td style='padding:3px 8px;border:1px solid #ccc;'>$Subnets->mail_font_style_href $change[dns_name]</font></td>";
		$content[] = "	<td style='padding:3px 8px;border:1px solid #ccc;'><a href='".rtrim(str_replace(BASE, "",$Subnets->settings->siteURL), "/")."".create_link("subnets",$subnet->sectionId,$subnet->id)."'>$Subnets->mail_font_style_href ".$Subnets->transform_to_dotted($subnet->subnet)."/".$subnet->mask."</font></a>".$subnet->description."</td>";
		$content[] = "	<td style='padding:3px 8px;border:1px solid #ccc;'>$Subnets->mail_font_style $change[lastSeen]</td>";
		$content[] = "</tr>";

		//plain content
		$content_plain[] = "\t * ".$Subnets->transform_to_dotted($change['ip_addr'])." (".$Subnets->transform_to_dotted($subnet->subnet)."/".$subnet->mask.")\r\n";
	}
	$content[] = "</table>";

	# set content
	$content 		= $phpipam_mail->generate_message (implode("\r\n", $content));
	$content_plain 	= implode("\r\n",$content_plain);

	# try to send
	try {
		$phpipam_mail->Php_mailer->setFrom($mail_settings->mAdminMail, $mail_settings->mAdminName);
		//add all admins to CC
		foreach($recepients as $admin) {
			$phpipam_mail->Php_mailer->addAddress(addslashes($admin['email']), addslashes($admin['name']));
		}
		$phpipam_mail->Php_mailer->Subject = $subject;
		$phpipam_mail->Php_mailer->msgHTML($content);
		$phpipam_mail->Php_mailer->AltBody = $content_plain;
		//send
		$phpipam_mail->Php_mailer->send();
	} catch (phpmailerException $e) {
		$Result->show_cli("Mailer Error: ".$e->errorMessage(), true);
	} catch (Exception $e) {
		$Result->show_cli("Mailer Error: ".$e->errorMessage(), true);
	}
}

?>
