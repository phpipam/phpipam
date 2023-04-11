<?php

/**
 * Static Captcha Example Script
 * 2012-04-18 
 *
 * The static captcha exposes an easy to use interface that applications can
 * use to generate captcha challenges and validate them by a unique ID.  A
 * captcha image can be associated with an ID and no PHP sessions are required.
 * The captcha ID can be stored in a SQLite database by Securimage.
 *
 * Tip: To give the user a refresh captcha button, use Ajax to request a new ID,
 * update the hidden form input with the new captcha ID, and update the image source
 * to securimage_show.php providing the captcha ID.
 */

// set debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// defines Securimage class
require_once '../securimage.php';

// get the captcha ID from the url (if supplied)
$captchaId = (isset($_GET['id'])) ? $_GET['id'] : '';

// if the validate option is set
if (isset($_GET['validate'])) {
    // get the user input of the captcha code
    $input = (isset($_GET['input'])) ? $_GET['input'] : '';

    // call Securimage::checkCaptchaId to validate input
    // returns true if the code and id are a valid pair, false if not
    if (Securimage::checkByCaptchaId($captchaId, $input) == true) {
        echo "<h2>Success</h2>"
            ."<span style='color: #33cc00'>The captcha code entered was correct!</span>"
            ."<br /><br />";
    } else {
        echo "<h2>Incorrect Code</h2>"
            ."<span style='color: #f00'>Incorrect captcha code, try again.</span>"
            ."<br /><br />";
    }

} else if (isset($_GET['display'])) {
    // display the captcha with the supplied ID from the URL

    // construct options specifying the existing captcha ID
    // also tell securimage not to start a session
    $options = array('captchaId'  => $captchaId,
                     'no_session' => true);
    $captcha = new Securimage($options);

    // show the image, this sends proper HTTP headers
    $captcha->show();
    exit;
}

// generate a new captcha ID and challenge
$captchaId = Securimage::getCaptchaId();

// output the captcha ID, and a form to validate it
// the form submits to itself and is validated above
echo <<<EOD
    <!DOCTYPE html>
    <html>
    <head>
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8">
    <title>Static Captcha Example</title>
    </head>
    <body>
    <h2>Static Captcha Example</h2>

    <div>
      Synopsis:
      <ul>
        <li>Request new captchaId using <em>Securimage::getCaptchaId()</em></li>
        <li>Display form with hidden field containing captchaId</li>
        <li>Display captcha image passing the captchaId to the image</li>
        <li>Validate captcha input against captchaId using <em>Securimage::checkByCaptchaId()</em></li>
      </ul>
    </div>
    <p>&nbsp;</p>
    <div>
      Captcha ID: $captchaId<br /><br />
      <img src="{$_SERVER['PHP_SELF']}?display&amp;id=$captchaId" alt="Captcha Image" /><br />

      <form method="get" action="{$_SERVER['PHP_SELF']}">
        <input type="hidden" name="validate" value="1" />
        <input type="hidden" name="id" value="$captchaId" />
        Enter Code:
        <input type="text" name="input" value="" />
        <input type="submit" name="submit" value="Check Captcha" />
      </form>
    </div>
    </body>
    </html>
EOD;
