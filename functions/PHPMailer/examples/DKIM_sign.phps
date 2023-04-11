<?php
/**
 * This example shows sending a DKIM-signed message with PHPMailer.
 * More info about DKIM can be found here: http://www.dkim.org/info/dkim-faq.html
 * There's more to using DKIM than just this code - check out this article:
 * @see https://yomotherboard.com/how-to-setup-email-server-dkim-keys/
 * See also the DKIM_gen_keys example code in the examples folder,
 * which shows how to make a key pair from PHP.
 */

//Import the PHPMailer class into the global namespace
use PHPMailer\PHPMailer\PHPMailer;

require '../vendor/autoload.php';

//Usual setup
$mail = new PHPMailer;
$mail->setFrom('from@example.com', 'First Last');
$mail->addAddress('whoto@example.com', 'John Doe');
$mail->Subject = 'PHPMailer mail() test';
$mail->msgHTML(file_get_contents('contents.html'), __DIR__);

//This should be the same as the domain of your From address
$mail->DKIM_domain = 'example.com';
//See the DKIM_gen_keys.phps script for making a key pair -
//here we assume you've already done that.
//Path to your private key:
$mail->DKIM_private = 'dkim_private.pem';
//Set this to your own selector
$mail->DKIM_selector = 'phpmailer';
//Put your private key's passphrase in here if it has one
$mail->DKIM_passphrase = '';
//The identity you're signing as - usually your From address
$mail->DKIM_identity = $mail->From;
//Suppress listing signed header fields in signature, defaults to true for debugging purpose
$this->mailer->DKIM_copyHeaderFields = false;
//Optionally you can add extra headers for signing to meet special requirements
$this->mailer->DKIM_extraHeaders = ['List-Unsubscribe', 'List-Help'];

//When you send, the DKIM settings will be used to sign the message
if (!$mail->send()) {
    echo "Mailer Error: " . $mail->ErrorInfo;
} else {
    echo "Message sent!";
}
