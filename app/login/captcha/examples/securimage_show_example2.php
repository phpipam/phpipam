<?php

/**
 * Project:     Securimage: A PHP class for creating and managing form CAPTCHA images<br />
 * File:        securimage_show_example2.php<br />
 *
 * Copyright (c) 2012, Drew Phillips
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *
 *  - Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 *  - Redistributions in binary form must reproduce the above copyright notice,
 *    this list of conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * Any modifications to the library should be indicated clearly in the source code
 * to inform users that the changes are not a part of the original software.<br /><br />
 *
 * If you found this script useful, please take a quick moment to rate it.<br />
 * http://www.hotscripts.com/rate/49400.html  Thanks.
 *
 * @link http://www.phpcaptcha.org Securimage PHP CAPTCHA
 * @link http://www.phpcaptcha.org/latest.zip Download Latest Version
 * @link http://www.phpcaptcha.org/Securimage_Docs/ Online Documentation
 * @copyright 2012 Drew Phillips
 * @author Drew Phillips <drew@drew-phillips.com>
* @package Securimage
 *
 */

require_once '../securimage.php';

$img = new Securimage();

//Change some settings
$img->image_width     = 280;
$img->image_height    = 100;
$img->perturbation    = 0.9;      // high level of distortion
$img->code_length     = rand(5,6); // random code length
$img->image_bg_color  = new Securimage_Color("#ffffff");
$img->num_lines       = 12;
$img->noise_level     = 5;
$img->text_color      = new Securimage_Color("#000000");
$img->noise_color     = $img->text_color;
$img->line_color      = new Securimage_Color("#cccccc");

$img->show();
