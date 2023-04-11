<?php

/**
 * Display Value Captcha Example
 * 2012-04-18
*
 * This example shows how to use the "display_value" option in Securimage which
 * allows the application to define the code that will be displayed on the
 * captcha image.
 *
 * Note: This value is not stored in the session or database!  The display_value
 * parameter would be used by a 3rd party application that uses Securimage only
 * to display captcha images, but generates and manages the codes independently.
 *
 */

// Set debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Defines Securimage class
require_once '../securimage.php';

// Create an array of options to give to Securimage
// This example sets the captcha text to the current time
// In order to use the display_value, a captchaId must be supplied so a random one is created
// Next we set turn off some unnecessary options and set properties of captcha
// image_width makes the image wide enough to hold the time
// no_session tells Securimage not to start or use a session to store codes
// no_exit tells Securimage not to terminate after calling Securimage::show()
// use_sqlite_db tells Securimage not to use SQLite
// send_headers tells Securimage not to send HTTP headers for the image; by not
// sending headers, you can capture the output and save it to file or serve it
// to the browser

$options = array('display_value' => date('h:i:s a'),
                 'captchaId'     => sha1(uniqid($_SERVER['REMOTE_ADDR'] . $_SERVER['REMOTE_PORT'])),
                 'image_width'   => 270,
                 'image_height'  => 80,
                 'no_session'    => true,
                 'no_exit'       => true,
                 'use_database'  => false,
                 'send_headers'  => false);

// construct new Securimage object with the given options
$img = new Securimage($options);

// show the image using the supplied display_value.
// This demonstrates how to use output buffering to capture the output.

// Note: It isn't required to use 'no_exit' and 'send_headers' or output buffering
// in conjunction with display value.  Doing so is a common use case and serves to show
// 2 examples in 1.

ob_start();   // start the output buffer
$img->show(); // output the image so it is captured by the buffer
$imgBinary = ob_get_contents(); // get contents of the buffer
ob_end_clean(); // turn off buffering and clear the buffer

header('Content-Type: image/png');
header('Content-Length: ' . strlen($imgBinary));

echo $imgBinary;

