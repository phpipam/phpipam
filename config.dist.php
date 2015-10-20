<?php

/*	database connection details
 ******************************/
$db['host'] = "localhost";
$db['user'] = "phpipam";
$db['pass'] = "phpipamadmin";
$db['name'] = "phpipam";
$db['port'] = 3306;

// SSL options
$db['ssl']  	= false;
$db['ssl_key']	= "";
$db['ssl_cert']	= "";
$db['ssl_ca']	= "";

/**
 * php debugging on/off
 *
 * true  = SHOW all php errors
 * false = HIDE all php errors
 ******************************/
$debugging = false;

/**
 *	manual set session name for auth
 *	increases security
 *	optional
 */
$phpsessname = "phpipam";

?>
