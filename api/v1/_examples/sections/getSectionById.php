<?php

/**
 * Sample API php client application
 *
 * In this example we will request section by id
 *
 *	http://phpipam/api/client/getAllSections.php
 */

# config
include_once('../apiConfig.php');

# API caller class
include_once('../apiClient.php');

# commands
$req['controller'] 	= "sections";
$req['action']		= "read";
$req['all']			= false;
$req['id']			= 22;

# wrap in try to catch exceptions
try {
	# initialize API caller
	$apicaller = new ApiCaller($app['id'], $app['enc'], $url, $format);
	# send request
	$response = $apicaller->sendRequest($req);

	print "<pre>";
	print_r($response);
}
catch( Exception $e ) {
	//catch any exceptions and report the problem
	print "<pre>";
	print_r($e->getMessage());
}

?>