<?php

/**
 * Show captcha
 */

// defines Securimage class
require_once __DIR__.'/../../../functions/functions.php';

// Don't corrupt output with php errors!
disable_php_errors();

// Create a user $_SESSION to store captcha code
$Database = new Database_PDO;
$User = new User($Database);

// Delete previous captcha data for this session.
unset($_SESSION['securimage_data']);
$_SESSION['captchaId'] = $User->Crypto->generate_html_safe_token();

// options
$options = [
    'image_width' => 425,       // width of captcha image in pixels
    'captchaId' => $_SESSION['captchaId'],
    'image_height' => 50,        // height of captcha image in pixels
    'code_length' => 6,         // # of characters for captcha code
    'image_bg_color' => '#ffffff', // hex color for image background
    'text_color' => '#707070', // hex color for captcha text
    'line_color' => '#202020', // hex color for lines over text
    'num_lines' => 5,         // # of lines to draw over text
    'wordlist_file' => __DIR__.'/../../../vendor/dapphp/securimage/words/words.txt', // text file for word captcha
    'use_wordlist' => false,             // true to use word list
    'wordlist_file_encoding' => null,        // character encoding of word file if other than ASCII (e.g. UTF-8, GB2312)
    'ttf_file' => __DIR__.'/../../../vendor/dapphp/securimage/AHGBold.ttf',   // TTF file for captcha text
    'no_session' => false,
    'session_name' => Config::ValueOf('phpsessname', 'phpipam'),
    'use_database' => false,
];

$captcha = new SecurimagePHP8($options);

// show the image, this sends proper HTTP headers
$captcha->show();
