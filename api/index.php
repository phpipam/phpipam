<?php

/**
 *	phpIPAM API
 *
 *		please visit http://phpipam.net/api-documentation/ on how to use API
 *
 *		To implement:
 *
 * 		http://www.restapitutorial.com/resources.html
 * 			Querying, Filtering and Pagination
 * 			Limiting Results
 * 			Pagination
 * 			Filtering
 * 			Sorting
 * 			versioning
 *
 */

# include functions
if(!function_exists("create_link"))
    require_once( dirname(__FILE__) . '/../functions/functions.php' );		// functions and objects from phpipam

# include common API controllers
require_once( dirname(__FILE__) . '/controllers/Common.php');			// common methods
require_once( dirname(__FILE__) . '/controllers/Responses.php');			// exception, header and response handling

# settings
$time_response         = true;          // adds [time] to response
$lock_file             = "";            // (optional) file to write lock to

# database and exceptions/result object
$Database = new Database_PDO;
$Tools    = new Tools ($Database);
$User     = new User ($Database);
$Response = new Responses ();

# get phpipam settings
$settings = $Tools->get_settings();

# set empty controller for options
if($_SERVER['REQUEST_METHOD']=="OPTIONS") {
	if( !isset($_GET['controller']) || $_GET['controller']=="")	{ $_GET['controller'] = "Tools"; }
}

/* wrap in a try-catch block to catch exceptions */
try {
	// start measuring
	$start = microtime(true);

	/* Validate application ---------- */

	// verify that API is enabled on server
	if($settings->api!=1) 									{ $Response->throw_exception(503, "API server disabled");}

	// fetch app
	$app = $Tools->fetch_object ("api", "app_id", $_GET['app_id']);

	// verify app_id
	if($app === false) 										{ $Response->throw_exception(400, "Invalid application id"); }
	// check that app is enabled
	if($app->app_permissions==="0") 						{ $Response->throw_exception(503, "Application disabled"); }


	/* Check app security and prepare request parameters ---------- */

	// crypt check
	if($app->app_security=="crypt") {
		$encryption_method = Config::ValueOf('api_crypt_encryption_library', 'openssl-128-cbc');

		// decrypt request - form_encoded
		if(strpos($_SERVER['CONTENT_TYPE'], "application/x-www-form-urlencoded")!==false) {
			$decoded = $User->Crypto->decrypt($_GET['enc_request'], $app->app_code, $encryption_method);
			if ($decoded === false) $Response->throw_exception(503, 'Invalid enc_request');
			$decoded = $decoded[0]=="?" ? substr($decoded, 1) : $decoded;
			parse_str($decoded, $encrypted_params);
			$encrypted_params['app_id'] = $_GET['app_id'];
			$params = (object) $encrypted_params;
		}
		// json_encoded
		else {
			$encrypted_params = $User->Crypto->decrypt($_GET['enc_request'], $app->app_code, $encryption_method);
			if ($encrypted_params === false) $Response->throw_exception(503, 'Invalid enc_request');
			$encrypted_params = json_decode($encrypted_params, true);
			$encrypted_params['app_id'] = $_GET['app_id'];
			$params = (object) $encrypted_params;
		}
	}
	// SSL checks
	elseif($app->app_security=="ssl_token" || $app->app_security=="ssl_code") {
		// verify SSL
		if (!$Tools->isHttps()) {
			$Response->throw_exception(503, _('SSL connection is required for API'));
		}

		// save request parameters
		$params = (object) $_GET;
	}
	// no security
	elseif($app->app_security=="none") {
		// make sure it is permitted in config.php
		if (Config::ValueOf('api_allow_unsafe')!==true) {
			$Response->throw_exception(503, _('SSL connection is required for API'));
		}

		// save request parameters
		$params = (object) $_GET;
	}
	// error, invalid security
	else {
		$Response->throw_exception(503, 'Invalid app security');
	}


	// Append Global API parameters / POST parameters if POST,PATCH or DELETE
	if($_SERVER['REQUEST_METHOD']=="GET" || $_SERVER['REQUEST_METHOD']=="POST" || $_SERVER['REQUEST_METHOD']=="PATCH" || $_SERVER['REQUEST_METHOD']=="DELETE") {
		// if application tupe is JSON (application/json)
		if(strpos($_SERVER['CONTENT_TYPE'], "application/json")!==false){
			$rawPostData = file_get_contents('php://input');
			if (is_string($rawPostData) && strlen($rawPostData)>0) {
				$json = json_decode($rawPostData, true);
				if(!is_array($json)) {
					$Response->throw_exception(400, 'Invalid JSON: '.json_last_error_msg());
				}
				$params = array_merge((array) $params, $json);
			}
			$params = (object) $params;
		}
		// if application tupe is XML (application/json)
		elseif(strpos($_SERVER['CONTENT_TYPE'], "application/xml")!==false){
			$rawPostData = file_get_contents('php://input');
			if (is_string($rawPostData) && strlen($rawPostData)>0) {
				$xml = $Response->xml_to_array($rawPostData);
				if(!is_array($xml)) {
					$Response->throw_exception(400, 'Invalid XML');
				}
				$params = array_merge((array) $params, $xml);
			}
			$params = (object) $params;
		}
		//if application type is default (application/x-www-form-urlencoded)
        elseif(sizeof(@$_POST)>0) {
            $params = array_merge((array) $params, $_POST);
            $params = (object) $params;
        }
        //url encoded input
        else {
            // input
            $input = file_get_contents('php://input');
            if (strlen($input)>0) {
                parse_str($input, $out);
                if(is_array($out)) {
                    $params = array_merge((array) $params, $out);
                    $params = (object) $params;
                }
            }
        }
    }

	/* Sanitise input ---------- (user/User/USER) */
	if (isset($params->controller)) $params->controller = strtolower($params->controller);

	/* Authentication ---------- */

	// authenticate user if required
	if (@$params->controller != "user") {
		if($app->app_security=="ssl_token" || $app->app_security=="none") {
			// start auth class and validate connection
			require_once( dirname(__FILE__) . '/controllers/User.php');				// authentication and token handling
			$Authentication = new User_controller ($Database, $Tools, $params, $Response);
			$Authentication->check_auth ();
		}

		// validate ssl_code
		if($app->app_security=="ssl_code") {
			// start auth class and validate connection
			require_once( dirname(__FILE__) . '/controllers/User.php');				// authentication and token handling
			$Authentication = new User_controller ($Database, $Tools, $params, $Response);
			$Authentication->check_auth_code ($app->app_id);
		}
	}
	// throw token not needed
	else {
		// validate ssl_code
		if($app->app_security=="ssl_code" && $_SERVER['REQUEST_METHOD']!="GET") {
			// start auth class and validate connection
			require_once( dirname(__FILE__) . '/controllers/User.php');				// authentication and token handling
			$Authentication = new User_controller ($Database, $Tools, $params, $Response);
			$Authentication->check_auth_code ($app->app_id);

			// passwd
			$Response->throw_exception(409, 'Authentication not needed');
		}
	}

	/* verify request ---------- */

	// check if the request is valid by checking if it's an array and looking for the controller and action
	if( $params == false || isset($params->controller) == false ) {
		$Response->throw_exception(400, 'Request is not valid');
	}
	// verify permissions for delete/create/edit if controller is not user (needed for auth)
	if (@$params->controller != "user") {
    	if( ($_SERVER['REQUEST_METHOD']=="POST" || $_SERVER['REQUEST_METHOD']=="PATCH"
    	  || $_SERVER['REQUEST_METHOD']=="PUT"  || $_SERVER['REQUEST_METHOD']=="DELETE"
    	  )
    	  && $app->app_permissions<2) {
    		$Response->throw_exception(401, 'invalid permissions');
    	}
	}
	// verify content type
	$Response->validate_content_type ();


	/* Initialize controller ---------- */

	// get the controller and format it correctly
	$controller 	 = ucfirst(strtolower($params->controller))."_controller";
	$controller_file = ucfirst(strtolower($params->controller));

	// check if the controller exists. if not, throw an exception
	if( file_exists( dirname(__FILE__) . "/controllers/$controller_file.php") ) {
		require_once( dirname(__FILE__) . "/controllers/$controller_file.php");
	}
	// check custom controllers
	elseif( file_exists( dirname(__FILE__) . "/controllers/custom/$controller_file.php") ) {
		require_once( dirname(__FILE__) . "/controllers/custom/$controller_file.php");
	}
	else {
		$Response->throw_exception(400, 'Invalid controller');
	}

	// create a new instance of the controller, and pass
	// it the parameters from the request and Database object
	$controller = new $controller($Database, $Tools, $params, $Response);

	// pass app params for links result
	$controller->app = $app;

	// Unmarshal the custom_fields JSON object into the main object for
	// POST and PATCH. This only works for controllers that support custom
	// fields and if the app has nested custom fields enabled, otherwise
	// this is skipped.
	if (strtoupper($_SERVER['REQUEST_METHOD']) == 'POST' || strtoupper($_SERVER['REQUEST_METHOD']) == 'PATCH') {
		$controller->unmarshal_nested_custom_fields();
	}

	// check if the action exists in the controller. if not, throw an exception.
	if( method_exists($controller, strtolower($_SERVER['REQUEST_METHOD'])) === false ) {
		$Response->throw_exception(501, $Response->errors[501]);
	}

	// if lock is enabled wait until it clears
	if( $app->app_lock==1 && strtoupper($_SERVER['REQUEST_METHOD'])=="POST") {
    	// set transaction lock file name
    	$controller->set_transaction_lock_file ($lock_file);

    	// check if locked form previous process
    	while ($controller->is_transaction_locked ()) {
        	// max ?
        	if ((microtime(true) - $start) > $app->app_lock_wait) {
            	$Response->throw_exception(503, "Transaction timed out after $app->app_lock_wait seconds because of transaction lock");
        	}
        	// add random delay
        	usleep(rand(250000,500000));
    	}

    	// add new lock
    	$controller->add_transaction_lock ();
    	// execute the action
    	$result = $controller->{$_SERVER['REQUEST_METHOD']} ();
    }
    else {
    	// execute the action
    	$result = $controller->{$_SERVER['REQUEST_METHOD']} ();
    }

    // remove transaction lock
    if(is_object($controller) && $app->app_lock==1 && strtoupper($_SERVER['REQUEST_METHOD'])=="POST") {
        if($controller->is_transaction_locked ()) {
            $controller->remove_transaction_lock ();
        }
    }
} catch ( Exception $e ) {
	// catch any exceptions and report the problem
	$result = $e->getMessage();

	// set flag if it came from Result, just to be sure
	if($Response->exception!==true) {
		$Response->exception = true;
		$Response->result['success'] = false;
		$Response->result['code'] 	 = 500;
		$Response->result['message'] = $result;
	}

    // remove transaction lock
    if(is_object($controller) && $app->app_lock==1 && strtoupper($_SERVER['REQUEST_METHOD'])=="POST") {
        if($controller->is_transaction_locked ()) {
            $controller->remove_transaction_lock ();
        }
    }
}

// stop measuring
$stop = microtime(true);

// add stop time
if($time_response) {
    $time = $stop - $start;
}

//output result
echo $Response->formulate_result ($result, $time, $app->app_nest_custom_fields, $controller->custom_fields);

// update access time
try { $Database->updateObject("api", ["app_id"=>$app->app_id, "app_last_access"=>date("Y-m-d H:i:s")], 'app_id'); }
catch (Exception $e) {}

// exit
exit();
