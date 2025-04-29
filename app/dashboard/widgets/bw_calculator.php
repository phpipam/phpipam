<?php


/*
 *IP calculator
 *********************************************/

# required functions if requested via AJAX
if(!isset($User)) {
	require_once( dirname(__FILE__) . '/../../../functions/functions.php' );
	# classes
	$Database	= new Database_PDO;
	$User 		= new User ($Database);
	$Result 	= new Result ();
}

# user must be authenticated
$User->check_user_session ();

# set widget flag
$widget = true;

# overlay
print "<div style='padding:10px;position:relative;'>";

# include ipcalc
include (dirname(__FILE__)."/../../../app/tools/ip-calculator/bw-calculator.php");

print "</div>";