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

$height = '600px';
$title = false;

# fetch widget parameters
$widget = $Tools->fetch_object ("widgets", "wfile", "locations");
# overwrite height from wparams
if(isset($widget->wparams)) {
	parse_str($widget->wparams, $p);
	if (@is_numeric($p['height'])) {
		$height = strval(intval($p['height'])) . "px";
	}
	unset($p);
}

# open maps
include(dirname(__FILE__)."/../../tools/locations/all-locations-map.php");
?>
