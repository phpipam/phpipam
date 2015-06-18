<?php

/**
 *	phpIPAM API
 *
 *		please read README on how to use API
 */


/* include funtions */
include_once 'functions/functions.php';

/* get server settings */
$settings = getAllSettings();

/* include models */
include_once 'models/common.php';						//common functions
include_once 'models/address.php';						//address actions
include_once 'models/subnet.php';						//subnet actions
include_once 'models/section.php';						//section actions
include_once 'models/vlan.php';							//vlan actions
include_once 'models/vrf.php';							//vrf actions

/* wrap in a try-catch block to catch exceptions */
try {

	/* Do some checks ---------- */

	// verify php extensions
	$requiredExt  = array("mcrypt", "curl");
	$availableExt = get_loaded_extensions();
	# check for missing ext
	foreach ($requiredExt as $extension) {
    	if (!in_array($extension, $availableExt)) {
        	throw new Exception('php extension '.$extension.' missing');
		}
	}

	//verify that API is enabled on server!
	if($settings['api']!=1) {
		throw new Exception('API server disabled');
	}

	//verify API key
	$app 	 = getAPIkeyByName($_REQUEST['app_id'], true);					//true reformats
	$appFull = getAPIkeyByName($_REQUEST['app_id'], false);

	//check first if the app id exists in the list of applications
	if( !isset($app[$_REQUEST['app_id']]) ) {
		throw new Exception('Application does not exist');
	}

	//check that app is enabled
	if($appFull['app_permissions']==0) {
		throw new Exception('Application disabled');
	}


	/* Get request ---------- */

	//decrypt the request
	$params = json_decode(trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $app[$_REQUEST['app_id']], base64_decode($_REQUEST['enc_request']), MCRYPT_MODE_ECB)));

	//check if the request is valid by checking if it's an array and looking for the controller and action
	if( $params == false || isset($params->controller) == false || isset($params->action) == false ) {
		throw new Exception('Request is not valid');
	}

	//transorm it into an array
	$params = (array) $params;


	/* Initialize controllers ---------- */

	//verify permissions
	if(strtolower($params['action'])=="admin") {
		if($appFull['app_permissions']!=3) {
			throw new Exception('Invalid permissions');
		}
	}
	if(strtolower($params['action'])=="delete" || strtolower($params['action'])=="create" || strtolower($params['action'])=="update") {
		if($appFull['app_permissions']!=2) {
			throw new Exception('Invalid permissions');
		}
	}

	//get the controller and format it correctly
	$controller = ucfirst(strtolower($params['controller']));

	//get the action and format it correctly
	$action = strtolower($params['action']).$controller;

	//check if the controller exists. if not, throw an exception
	if( file_exists("controllers/$controller.php") ) {
		include_once "controllers/$controller.php";										//preveri, ce obstaja controller
	} else {
		throw new Exception('Controller is invalid');
	}

	//create a new instance of the controller, and pass
	//it the parameters from the request
	$controller = new $controller($params);

	//check if the action exists in the controller. if not, throw an exception.
	if( method_exists($controller, $action) === false ) {
		throw new Exception('Invalid action');
	}

	//execute the action
	$result['success'] = true;
	$result['data'] = $controller->$action();

} catch( Exception $e ) {
	//catch any exceptions and report the problem
	$result = array();
	$result['success'] = false;
	$result['errormsg'] = $e->getMessage();
}

//echo the result of the API call
echo json_encode($result);
exit();

?>