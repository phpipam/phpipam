<?php

/**
 * Static Captcha Example Script
 * 2013-03-28 
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

// define options for securimage
$options = array('database_driver' => Securimage::SI_DRIVER_MYSQL,
                 'database_host'   => 'localhost',
                 'database_user'   => 'securimage',
                 'database_pass'   => 'password1234',
                 'database_name'   => 'test',
                 'no_session'      => true);

// get the captcha ID from the url (if supplied)
$captchaId = (isset($_GET['id'])) ? $_GET['id'] : '';

// if the validate option is set
if (isset($_GET['validate'])) { // $_GET['id'] should also be set
    // get the user input of the captcha code
    $input = (isset($_GET['input'])) ? $_GET['input'] : '';

    // call Securimage::checkCaptchaId to validate input
    // returns true if the code and id are a valid pair, false if not
    if (Securimage::checkByCaptchaId($captchaId, $input, $options) == true) {
        echo "<h2>Success</h2>"
            ."<span style='color: #33cc00'>The captcha code entered was correct!</span>"
            ."<br /><br />";
    } else {
        echo "<h2>Incorrect Code</h2>"
            ."<span style='color: #f00'>Incorrect captcha code, try again.</span>"
            ."<br /><br />";
    }
} else if (isset($_GET['refresh'])) {
    $captcha = Securimage::getCaptchaId(true, $options);
    $data    = array('captchaId' => $captcha);
    
    echo json_encode($data);
    exit;
    
} else if (isset($_GET['display'])) {
    // display the captcha with the supplied ID from the URL

    // construct options specifying the existing captcha ID
    // also tell securimage not to start a session
    $options['captchaId']  = $captchaId;
    
    $captcha = new Securimage($options);

    // show the image, this sends proper HTTP headers
    $captcha->show();
    exit;
}

// generate a new captcha ID and challenge
$captchaId = Securimage::getCaptchaId(true, $options);

// output the captcha ID, and a form to validate it
// the form submits to itself and is validated above ?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8">
<title>Static Captcha Example</title>
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
 
    <script type="text/javascript">
    /* jQuery ajax example of refreshing a static captcha */
    function refreshCaptcha() {
        $.ajax({ url: '?refresh=1',
            dataType: 'json', 
        }).done(function(data) {
            var src = $(location).attr('href').split('?')[0] + '?display&id=' + data.captchaId + '&rand=' + Math.random();
            $('#span_captchaId').html(data.captchaId); // update span element
            $('#siimage').attr('src', src); // replace image with new captcha
            $('#captchaId').attr('value', data.captchaId); // update hidden form field
        });
    }
    </script>
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
        Captcha ID: <span id="span_captchaId"><?php echo $captchaId ?></span><br /><br />
        <img id="siimage" src="<?php echo $_SERVER['PHP_SELF'] ?>?display=1&amp;id=<?php echo $captchaId ?>" alt="Captcha Image" />
        [ <a href="#" onclick="refreshCaptcha(); return false">Refresh Image</a> ]
        <br />

        <form method="get" action="<?php echo $_SERVER['PHP_SELF'] ?>">
            <input type="hidden" name="validate" value="1" />
            <input id="captchaId" type="hidden" name="id" value="<?php echo $captchaId ?>" />
            Enter Code:
            <input type="text" name="input" value="" />
            <input type="submit" name="submit" value="Check Captcha" />
        </form>
    </div>
</body>
</html>
