<?php

require_once '../securimage.php';

$securimage = new Securimage(array('no_exit' => true));
$securimage->use_database = true;
$securimage->database_driver = Securimage::SI_DRIVER_PGSQL;
$securimage->database_host   = 'localhost';
$securimage->database_user   = 'securimage';
$securimage->database_pass   = 'password1234';
$securimage->database_name   = 'test';

$securimage->show();
