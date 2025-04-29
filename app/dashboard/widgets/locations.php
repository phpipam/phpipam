<?php
/*
 * Print list of inactive hosts
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
    header("Location: ".create_link('tools', 'locations', 'map'));
}

# user must be authenticated
$User->check_user_session ();

# no errors!
//ini_set('display_errors', 0);

$title = false;

# fetch widget parameters
$wparam = $Tools->get_widget_params("locations");
$height = filter_var($wparam->height, FILTER_VALIDATE_INT, ['options' => ['default' => 600, 'min_range' => 1, 'max_range' => 800]]) . "px";

# open maps
include(dirname(__FILE__)."/../../tools/locations/all-locations-map.php");

