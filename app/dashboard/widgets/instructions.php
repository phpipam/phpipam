<?php
/*
 * Prints edit instructions
 **********************************************/

# required functions
if(!isset($User)) {
	require_once( dirname(__FILE__) . '/../../../functions/functions.php' );
	# classes
	$Database	= new Database_PDO;
	$User 		= new User ($Database);
	$Tools 		= new Tools ($Database);
	$Subnets 	= new Subnets ($Database);
	$Addresses 	= new Addresses ($Database);
	$Result		= new Result ();
}
else {
    header("Location: ".create_link('tools', 'instructions'));
}

# user must be authenticated
$User->check_user_session ();

# no errors!
//ini_set('display_errors', 0);

# set size parameters
$height = 200;
$slimit = 5;			//we don't need this, we will recalculate

# count
$m = 0;

// fetch widget
$widget = $Tools->fetch_object ("widgets", "wfile", "instructions");

# if direct request include plot JS
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != "XMLHttpRequest")	{
	# get widget details
	if(!$widget = $Tools->fetch_object ("widgets", "wfile", $GET->section)) { $Result->show("danger", _("Invalid widget"), true); }
	# reset size and limit
	$height = 350;
	$slimit = 100;
	# and print title
	print "<div class='container'>";
	print "<h4 style='margin-top:40px;'>$widget->wtitle</h4><hr>";
	print "</div>";
}

$i_am_a_widget = true;
require( dirname(__FILE__) . '/../../tools/instructions/index.php' );
