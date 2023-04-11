<?php

require_once '../securimage.php';

$t0         = microtime(true);

$securimage = new Securimage(array('no_exit' => true));
$securimage->use_database = true;
$securimage->database_driver = Securimage::SI_DRIVER_SQLITE3;

$securimage->show();

$t1         = microtime(true);
