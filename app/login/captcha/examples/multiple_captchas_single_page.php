<?php

/**
 * Quick and dirty example of multiple captchas on one page using namespaces.
 *
 * -- Synopsis --
 * In order to add a captcha to multiple forms on a page, each image needs to
 * have unique ID's for each <img> tag for the refresh button to work; they
 * also need distinct names so the audio generation works properly.
 *
 * Most importantly, they must be assigned a namespace so the value of one
 * image doesn't overwrite the other.  With namespaces, the captcha values
 * stored in the session or database are separated from eachother allowing
 * multiple to exist on one page and be displayed simultaneously.
 *
 */

// IMPORTANT!!!
// securimage.php must be included in order to use Securimage::getCaptchaHtml()
// Don't forget this!
require_once __DIR__ . '/../securimage.php';
?>
<!doctype html>
<title>Captcha Namespaces - Multiple captchas on one page</title>
<style>
  body { text-align: center; padding: 150px; }
  h3 { font-size: 20px; }
  body { font: 20px Helvetica, sans-serif; color: #333; }
  article { display: block; text-align: left; width: 650px; margin: 0 auto; }
  a { color: #dc8100; text-decoration: none; }
  a:hover { color: #333; text-decoration: none; }
  label { display: block; }
  em.valid { color: #00ccff; }
  em.invalid { color: #f00; font-weight: bold; }
</style>

<h3>Multiple Captchas on one page using 'namespaces'</h3>

<article>
    <fieldset>
        <legend>"Contact Form" captcha...</legend>
        <form method="POST" action="">
        <input type="hidden" name="action" value="contact_form">
        <?php

        $error_html1 = null;

        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'contact_form') {
            // submitted "contact" form

            // create new securimage object, indicating the namespace of the
            // code we want to check based on what form was submitted.
            $securimage = new Securimage(array('namespace' => 'contact'));

            // validate user input
            $valid = $securimage->check(@$_POST['captcha_code']);

            if ($valid) {
                // code was correct, tell them so
                $error_html1 = "<em class='valid'>Code entered correctly!</em><br>";
            } else {
                // incorrect code entered
                $error_html1 = "<em class='invalid'>The code entered was incorrect.</em><br>";
            }
        }

        // options controlling output of getCaptchaHtml()
        $options1 = array(
            'input_id'   => 'contact_captcha',     // ID of the text input field
            'input_name' => 'captcha_code',        // name of the captcha text field for POST
            'image_id'   => 'contact_captcha_img', // ID of the captcha image
            'namespace'  => 'contact',             // namespace for storing and validating
            'error_html' => $error_html1,          // error (or success) to display to the user above text input
        );

        echo Securimage::getCaptchaHtml($options1);
        ?>
        <br>
        <input type="submit" value="Submit Form">
        </form>
    </fieldset>

    <p>
    Other page content here.....
    </p>

    <fieldset>
        <legend>"Comments form" captcha...</legend>
        <form method="POST" action="">
        <input type="hidden" name="action" value="comment">
        <?php

        $error_html2 = null;

        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'comment') {
            // submitted "contact" form

            $securimage = new Securimage(array('namespace' => 'comments'));
            $valid = $securimage->check($_POST['captcha_code']);

            if ($valid) {
                $error_html2 = "<em class='valid'>Code entered correctly!</em><br>";
            } else {
                $error_html2 = "<em class='invalid'>The code entered was incorrect.</em><br>";
            }
        }

        $options2 = array(
            'input_id'   => 'comments_captcha',
            'input_name' => 'captcha_code',
            'image_id'   => 'comments_captcha_img',
            'namespace'  => 'comments',
            'error_html' => $error_html2,
        );

        echo Securimage::getCaptchaHtml($options2);
        ?>
        <br>
        <input type="submit" value="Submit Form">
        </form>
    </fieldset>
</article>
