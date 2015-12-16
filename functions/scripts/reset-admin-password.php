<?php

/**
 * This script resets the admin password, provided via CLI
 *
 *	either provide it by first argument or via readline
 *
 */

# include required scripts
require( dirname(__FILE__) . '/../functions.php' );

# set debugging
$debugging = false;
$fail	   = false;

# initialize objects
$Database 	= new Database_PDO;
$Admin		= new Admin ($Database, false);
$User		= new User ($Database, true);
$Result		= new Result();


// script can only be run from cli
if(php_sapi_name()!="cli") 									{ $Result->show_cli("This script can only be run from cli", true); }

// check if argv[1] provided, if not check readline support and wait for user pass
if (isset($argv[1]))	{ $password = $argv[1]; }
else {
	// get available extensions
	$available_extensions = get_loaded_extensions();
	// not in array
	if (!in_array("readline", $available_extensions)) 		{ $Result->show_cli("readline php extension is required.\nOr provide password as first argument", true); }
	else {
		// read password
		$line = readline("Enter password: ");
		readline_add_history($line);
		// save
		$password = array_pop(readline_list_history());
	}
}

// validate password
if(strlen($password)<8)										{ $Result->show_cli("Password must be at least 8 characters long", true); }

// hash passowrd
$password_crypted = $User->crypt_user_pass ($password);
// save type
$crypt_type = $User->return_crypt_type ();

// set update array
$values = array("id"=>1,
				"password"=>$password_crypted
				);

// update password
if(!$Admin->object_modify("users", "edit", "id", $values))	{ $Result->show_cli("Failed to update Admin password", false); }
else														{ $Result->show_cli("Admin password updated", false); }


// debug ?
if ($debugging || $fail) {
	$Result->show_cli("---------");
	$Result->show_cli("Crypt type: ".$crypt_type);
	$Result->show_cli("Password: ".$password_crypted);
	$Result->show_cli("---------");
}

// fail
if ($fail) { die(); }

# send mail

# check for recipients
foreach($Admin->fetch_multiple_objects ("users", "role", "Administrator") as $admin) {
	$recepients[] = array("name"=>$admin->real_name, "email"=>$admin->email);
}
# none?
if(!isset($recepients))	{ die(); }

// fetch settings
$settings = $Admin->fetch_object("settings", "id", 1);
// fetch mailer settings
$mail_settings = $Admin->fetch_object("settingsMail", "id", 1);

# initialize mailer
$phpipam_mail = new phpipam_mail($settings, $mail_settings);
$phpipam_mail->initialize_mailer();

// set subject
$subject	= "phpIPAM Administrator password updated";
//html
$content[] = "<h3>phpIPAM Administrator password updated</h3>";
$content[] = "<hr>";
$content[] = "Administrator password was updated via cli script";
//plain
$content_plain[] = "phpIPAM Administrator password updated \r\n------------------------------";
$content_plain[] = "Administrator password was updated via cli script";


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

?>