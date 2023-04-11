<?php
session_start(); // this MUST be called prior to any output including whitespaces and line breaks!

$GLOBALS['ct_recipient']   = 'YOU@EXAMPLE.COM'; // Change to your email address!
$GLOBALS['ct_msg_subject'] = 'Securimage Test Contact Form';

$GLOBALS['DEBUG_MODE'] = 1;
// CHANGE TO 0 TO TURN OFF DEBUG MODE
// IN DEBUG MODE, ONLY THE CAPTCHA CODE IS VALIDATED, AND NO EMAIL IS SENT


// Process the form, if it was submitted
process_si_contact_form();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-type" content="text/html;charset=UTF-8" />
    <title>Securimage Example Form</title>
    <style type="text/css">
    <!--
        #success_message { border: 1px solid #000; width: 550px; text-align: left; padding: 10px 7px; background: #33ff33; color: #000; font-weight: bold; font-size: 1.2em; border-radius: 4px; -moz-border-radius: 4px; -webkit-border-radius: 4px; }
        fieldset { width: 90%; }
        legend { font-size: 24px; }
        .note { font-size: 18px; }
        form label { display: block; font-weight: bold; }
    -->
    </style>
    </head>
<body>

<fieldset>
<legend>Example Form</legend>

<p class="note">
  This is an example PHP form that processes user information, checks for errors, and validates the captcha code.<br />
  This example form also demonstrates how to submit a form to itself to display error messages.
</p>

<div id="success_message" style="display: none">Your message has been sent!<br />We will contact you as soon as possible.</div>

<form method="post" action="" id="contact_form" onsubmit="return processForm()">
  <input type="hidden" name="do" value="contact" />

  <p>
    <strong>Name*:</strong><br />
    <input type="text" name="ct_name" size="35" value="" />
  </p>

  <p>
    <strong>Email*:</strong><br />
    <input type="text" name="ct_email" size="35" value="" />
  </p>

  <p>
    <strong>URL:</strong><br />
    <input type="text" name="ct_URL" size="35" value="" />
  </p>

  <p>
    <strong>Message*:</strong><br />
    <textarea name="ct_message" rows="12" cols="60"></textarea>
  </p>

  <p>
    <?php require_once 'securimage.php'; echo Securimage::getCaptchaHtml(array('input_name' => 'ct_captcha')); ?>
  </p>

  <p>
    <br />
    <input type="submit" value="Submit Message" />
  </p>

</form>
</fieldset>

<script src="http://code.jquery.com/jquery-1.10.1.min.js"></script>
<script type="text/javascript">
    $.noConflict();

    function reloadCaptcha()
    {
        jQuery('#siimage').prop('src', './securimage_show.php?sid=' + Math.random());
    }

    function processForm()
    {
        jQuery.ajax({
            url: '<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES) ?>',
            type: 'POST',
            data: jQuery('#contact_form').serialize(),
            dataType: 'json'
        }).done(function(data) {
            if (data.error === 0) {
                jQuery('#success_message').show();
                jQuery('#contact_form')[0].reset();
                reloadCaptcha();
                setTimeout("jQuery('#success_message').fadeOut()", 12000);
            } else {
                alert("There was an error with your submission.\n\n" + data.message);

                if (data.message.indexOf('Incorrect security code') >= 0) {
                    jQuery('#captcha_code').val('');
                }
            }
        });

        return false;
    }
</script>

</body>
</html>

<?php

// The form processor PHP code
function process_si_contact_form()
{
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && @$_POST['do'] == 'contact') {
        // if the form has been submitted

        foreach($_POST as $key => $value) {
            if (!is_array($key)) {
                // sanitize the input data
                if ($key != 'ct_message') $value = strip_tags($value);
                $_POST[$key] = htmlspecialchars(stripslashes(trim($value)));
            }
        }

        $name    = @$_POST['ct_name'];    // name from the form
        $email   = @$_POST['ct_email'];   // email from the form
        $URL     = @$_POST['ct_URL'];     // url from the form
        $message = @$_POST['ct_message']; // the message from the form
        $captcha = @$_POST['ct_captcha']; // the user's entry for the captcha code
        $name    = substr($name, 0, 64);  // limit name to 64 characters

        $errors = array();  // initialize empty error array

        if (isset($GLOBALS['DEBUG_MODE']) && $GLOBALS['DEBUG_MODE'] == false) {
            // only check for errors if the form is not in debug mode

            if (strlen($name) < 3) {
                // name too short, add error
                $errors['name_error'] = 'Your name is required';
            }

            if (strlen($email) == 0) {
                // no email address given
                $errors['email_error'] = 'Email address is required';
            } else if ( !preg_match('/^(?:[\w\d-]+\.?)+@(?:(?:[\w\d]\-?)+\.)+\w{2,4}$/i', $email)) {
                // invalid email format
                $errors['email_error'] = 'Email address entered is invalid';
            }

            if (strlen($message) < 20) {
                // message length too short
                $errors['message_error'] = 'Please enter a message';
            }
        }

        // Only try to validate the captcha if the form has no errors
        // This is especially important for ajax calls
        if (sizeof($errors) == 0) {
            require_once dirname(__FILE__) . '/securimage.php';
            $securimage = new Securimage();

            if ($securimage->check($captcha) == false) {
                $errors['captcha_error'] = 'Incorrect security code entered';
            }
        }

        if (sizeof($errors) == 0) {
            // no errors, send the form
            $time       = date('r');
            $message = "A message was submitted from the contact form.  The following information was provided.<br /><br />"
                     . "Name: $name<br />"
                     . "Email: $email<br />"
                     . "URL: $URL<br />"
                     . "Message:<br />"
                     . "<pre>$message</pre>"
                     . "<br /><br />IP Address: {$_SERVER['REMOTE_ADDR']}<br />"
                     . "Time: $time<br />"
                     . "Browser: " . htmlspecialchars($_SERVER['HTTP_USER_AGENT']) . "<br />";

            if (isset($GLOBALS['DEBUG_MODE']) && $GLOBALS['DEBUG_MODE'] == false) {
                // send the message with mail()
                mail($GLOBALS['ct_recipient'], $GLOBALS['ct_msg_subject'], $message, "From: {$GLOBALS['ct_recipient']}\r\nReply-To: {$email}\r\nContent-type: text/html; charset=ISO-8859-1\r\nMIME-Version: 1.0");
            }

            $return = array('error' => 0, 'message' => 'OK');
            die(json_encode($return));
        } else {
            $errmsg = '';
            foreach($errors as $key => $error) {
                // set up error messages to display with each field
                $errmsg .= " - {$error}\n";
            }

            $return = array('error' => 1, 'message' => $errmsg);
            die(json_encode($return));
        }
    } // POST
} // function process_si_contact_form()
