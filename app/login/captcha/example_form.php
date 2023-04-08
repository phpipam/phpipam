<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start(); // this MUST be called prior to any output including whitespaces and line breaks!

$GLOBALS['DEBUG_MODE'] = 1;
// CHANGE TO 0 TO TURN OFF DEBUG MODE
// IN DEBUG MODE, ONLY THE CAPTCHA CODE IS VALIDATED, AND NO EMAIL IS SENT

$GLOBALS['ct_recipient']   = 'YOU@EXAMPLE.COM'; // Change to your email address!  Make sure DEBUG_MODE above is 0 for mail to send!
$GLOBALS['ct_msg_subject'] = 'Securimage Test Contact Form';

?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="Content-type" content="text/html;charset=UTF-8">
  <title>Securimage Example Form</title>
  <link rel="stylesheet" href="securimage.css" media="screen">
  <style type="text/css">
  <!--
  div.error { display: block; color: #f00; font-weight: bold; font-size: 1.2em; }
  span.error { display: block; color: #f00; font-style: italic; }
  .success { color: #00f; font-weight: bold; font-size: 1.2em; }
  form label { display: block; font-weight: bold; }
  fieldset { width: 90%; }
  legend { font-size: 24px; }
  .note { font-size: 18px;
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

<?php

process_si_contact_form(); // Process the form, if it was submitted

if (isset($_SESSION['ctform']['error']) &&  $_SESSION['ctform']['error'] == true): /* The last form submission had 1 or more errors */ ?>
<div class="error">There was a problem with your submission.  Errors are displayed below in red.</div><br>
<?php elseif (isset($_SESSION['ctform']['success']) && $_SESSION['ctform']['success'] == true): /* form was processed successfully */ ?>
<div class="success">The captcha was correct and the message has been sent!  The captcha was solved in <?php echo $_SESSION['ctform']['timetosolve'] ?> seconds.</div><br />
<?php endif; ?>

<form method="post" action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI'] . $_SERVER['QUERY_STRING']) ?>" id="contact_form">
  <input type="hidden" name="do" value="contact">

  <p>
    <label for="ct_name">Name*:</label>
    <?php echo @$_SESSION['ctform']['name_error'] ?>
    <input type="text" id="ct_name" name="ct_name" size="35" value="<?php echo htmlspecialchars(@$_SESSION['ctform']['ct_name']) ?>">
  </p>

  <p>
    <label for="ct_email">Email*:</label>
    <?php echo @$_SESSION['ctform']['email_error'] ?>
    <input type="text" id="ct_email" name="ct_email" size="35" value="<?php echo htmlspecialchars(@$_SESSION['ctform']['ct_email']) ?>">
  </p>

  <p>
    <label for="ct_URL">URL:</label>
    <?php echo @$_SESSION['ctform']['URL_error'] ?>
    <input type="text" id="ct_URL" name="ct_URL" size="35" value="<?php echo htmlspecialchars(@$_SESSION['ctform']['ct_URL']) ?>">
  </p>

  <p>
    <label for="ct_message">Message*:</label>
    <?php echo @$_SESSION['ctform']['message_error'] ?>
    <textarea id="ct_message" name="ct_message" rows="12" cols="60"><?php echo htmlspecialchars(@$_SESSION['ctform']['ct_message']) ?></textarea>
  </p>

  <div>
    <?php
      // show captcha HTML using Securimage::getCaptchaHtml()
      require_once 'securimage.php';
      $options = array();
      $options['input_name']             = 'ct_captcha'; // change name of input element for form post
      $options['disable_flash_fallback'] = false; // allow flash fallback

      if (!empty($_SESSION['ctform']['captcha_error'])) {
        // error html to show in captcha output
        $options['error_html'] = $_SESSION['ctform']['captcha_error'];
      }

      echo "<div id='captcha_container_1'>\n";
      echo Securimage::getCaptchaHtml($options);
      echo "\n</div>\n";

      /*
      // To render some or all captcha components individually
      $options['input_name'] = 'ct_captcha_2';
      $options['image_id']   = 'ct_captcha_2';
      $options['input_id']   = 'ct_captcha_2';
      $options['namespace']  = 'captcha2';

      echo "<br>\n<div id='captcha_container_2'>\n";
      echo Securimage::getCaptchaHtml($options, Securimage::HTML_IMG);

      echo Securimage::getCaptchaHtml($options, Securimage::HTML_ICON_REFRESH);
      echo Securimage::getCaptchaHtml($options, Securimage::HTML_AUDIO);

      echo '<div style="clear: both"></div>';

      echo Securimage::getCaptchaHtml($options, Securimage::HTML_INPUT_LABEL);
      echo Securimage::getCaptchaHtml($options, Securimage::HTML_INPUT);
      echo "\n</div>";
      */
    ?>
  </div>

  <p>
    <br>
    <input type="submit" value="Submit Message">
  </p>

</form>
</fieldset>

</body>
</html>

<?php

// The form processor PHP code
function process_si_contact_form()
{
  $_SESSION['ctform'] = array(); // re-initialize the form session data

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
      } else if ( !preg_match('/^(?:[\w\d-]+\.?)+@(?:(?:[\w\d]\-?)+\.)+\w{2,63}$/i', $email)) {
        // invalid email format
        $errors['email_error'] = 'Email address entered is invalid';
      }

      if (strlen($message) < 20) {
        // message length too short
        $errors['message_error'] = 'Your message must be longer than 20 characters';
      }
    }

    // Only try to validate the captcha if the form has no errors
    // This is especially important for ajax calls
    if (sizeof($errors) == 0) {
      require_once dirname(__FILE__) . '/securimage.php';
      $securimage = new Securimage();

      if ($securimage->check($captcha) == false) {
        $errors['captcha_error'] = 'Incorrect security code entered<br />';
      }
    }

    if (sizeof($errors) == 0) {
      // no errors, send the form
      $time       = date('r');
      $message = "A message was submitted from the contact form.  The following information was provided.<br /><br />"
                    . "<em>Name: $name</em><br />"
                    . "<em>Email: $email</em><br />"
                    . "<em>URL: $URL</em><br />"
                    . "<em>Message:</em><br />"
                    . "<pre>$message</pre>"
                    . "<br /><br /><em>IP Address:</em> {$_SERVER['REMOTE_ADDR']}<br />"
                    . "<em>Time:</em> $time<br />"
                    . "<em>Browser:</em> " . htmlspecialchars($_SERVER['HTTP_USER_AGENT']) . "<br />";

      $message = wordwrap($message, 70);

      if (isset($GLOBALS['DEBUG_MODE']) && $GLOBALS['DEBUG_MODE'] == false) {
      	// send the message with mail()
        mail($GLOBALS['ct_recipient'], $GLOBALS['ct_msg_subject'], $message, "From: {$GLOBALS['ct_recipient']}\r\nReply-To: {$email}\r\nContent-type: text/html; charset=UTF-8\r\nMIME-Version: 1.0");
      }

      $_SESSION['ctform']['timetosolve'] = $securimage->getTimeToSolve();
      $_SESSION['ctform']['error'] = false;  // no error with form
      $_SESSION['ctform']['success'] = true; // message sent
    } else {
      // save the entries, this is to re-populate the form
      $_SESSION['ctform']['ct_name'] = $name;       // save name from the form submission
      $_SESSION['ctform']['ct_email'] = $email;     // save email
      $_SESSION['ctform']['ct_URL'] = $URL;         // save URL
      $_SESSION['ctform']['ct_message'] = $message; // save message

      foreach($errors as $key => $error) {
      	// set up error messages to display with each field
        $_SESSION['ctform'][$key] = "<span class=\"error\">$error</span>";
      }

      $_SESSION['ctform']['error'] = true; // set error floag
    }
  } // POST
}

$_SESSION['ctform']['success'] = false; // clear success value after running
