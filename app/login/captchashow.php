<?php

/**
 *
 * Show captcha
 *
 */

// defines Securimage class
require_once '../../functions/functions.php';
require_once 'captcha/securimage.php';

// Create a user $_SESSION to store captcha code
$Database = new Database_PDO;
$User     = new User ($Database);

// options
$options = array (
			'image_width'            => 425,       // width of captcha image in pixels
			'image_height'           => 50,        // height of captcha image in pixels
			'code_length'            => 6,         // # of characters for captcha code
			'image_bg_color'         => '#ffffff', // hex color for image background
			'text_color'             => '#707070', // hex color for captcha text
			'line_color'             => '#202020', // hex color for lines over text
			'num_lines'              => 5,         // # of lines to draw over text
			'wordlist_file'          => 'words/words.txt', // text file for word captcha
			'use_wordlist'           => false,             // true to use word list
			'wordlist_file_encoding' => null,        // character encoding of word file if other than ASCII (e.g. UTF-8, GB2312)
			'ttf_file'               => './captcha/AHGBold.ttf',   // TTF file for captcha text
			'no_session'             => false,
			'session_name'           => Config::ValueOf('phpsessname', 'phpipam'),
			'use_database'           => false
			);

// construct
$captcha = new Securimage($options);

// show the image, this sends proper HTTP headers
$captcha->show();