<?php

/**
 * Multiple words captcha example
 * 2014-02-15 
 *
 * This example shows how to use the wordlist to generate captchas containing 2 words in one image.
 * It also scales the font down so longer text strings fit in the image bounds.
 *
 */

// set debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// defines Securimage class
require_once '../securimage.php';

$img = new Securimage();

// set captcha type to multiple word captcha
$img->captcha_type = Securimage::SI_CAPTCHA_WORDS;

// adjust font ratio
$img->font_ratio = 0.25;

// increase image size
$img->image_height = 100;
$img->image_width  = 95 * M_E;


$img->show();  // outputs the image and content headers to the browser
