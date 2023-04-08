<?php

require_once '../securimage.php';

$t0         = microtime(true);

$securimage = new Securimage(array('no_exit' => true));
$securimage->use_database = true;
$securimage->database_driver = Securimage::SI_DRIVER_MYSQL;
$securimage->database_host   = 'localhost';
$securimage->database_user   = 'securimage';
$securimage->database_pass   = 'password1234';
$securimage->database_name   = 'test';

$securimage->show();

$t1         = microtime(true);
