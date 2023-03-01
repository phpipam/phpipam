<?php

/**
 *	phpIPAM API class to authneticate users
 *
 *
 */

class User_controller extends Common_api_functions {

	/**
	 * users token
	 *
	 * @var mixed
	 * @access public
	 */
	public $token;

	/**
	 * time when token expires
	 *
	 * @var mixed
	 * @access public
	 */
	public $token_expires;

	/**
	 * for how many seconds token is valid
	 *
	 * @var mixed
	 * @access private
	 */
	private $token_valid_time;

	/**
	 * number of chars for token
	 *
	 * @var mixed
	 * @access private
	 */
	private $token_length;

	/**
	 * max number of failures before IP is blocked
	 *
	 * @var mixed
	 * @access private
	 */
	private $max_failures;

	/**
	 * controls if IP should be blocked for 5 minutes on invalid requests
	 *
	 * (default value: true)
	 *
	 * @var bool
	 * @access private
	 */
	private $block_ip = true;


	/**
	 * __construct function
	 *
	 * @access public
	 * @param PDO_Database $Database
	 * @param Tools $Tools
	 * @param API_params $params
	 * @param Response $response
	 */
	public function __construct ($Database, $Tools, $params, $Response) {
		$this->Database = $Database;
		$this->Tools = $Tools;
		$this->Response = $Response;
		$this->_params = $params;
		// init required objects
		$this->init_object ("Admin", $Database);
		$this->init_object ("User", $Database);
		// set default for tokens
		$this->set_token_valid_time ();
		$this->set_max_failures ();
		$this->set_token_length ();

		// if HTTP_TOKEN is set change it to HTTP_PHPIPAM_TOKEN
		if (isset($_SERVER['HTTP_TOKEN'])&&!isset($_SERVER['HTTP_PHPIPAM_TOKEN'])) 		{ $_SERVER['HTTP_PHPIPAM_TOKEN'] = $_SERVER['HTTP_TOKEN']; }
	}










	/**
	 * returns general Controllers and supported methods
	 *
	 * @access public
	 * @return void
	 */
	public function OPTIONS () {
		// validate
		$this->validate_options_request ();

		// methods
		$result = array();
		$result['methods'] = array(
								array("href"=>"/api/".$this->_params->app_id."/user/", 	"methods"=>array(array("rel"=>"read", 	"method"=>"GET"),
																										 array("rel"=>"create", "method"=>"POST"),
																										 array("rel"=>"update", "method"=>"PATCH"),
																										 array("rel"=>"delete", "method"=>"DELETE"))),
							);
		# result
		return array("code"=>200, "data"=>$result);
	}








	/**
	 * Authenticates user and returns token
	 *
	 *	Identifier can be:
	 *		- /token_expires/				// returns token expiration date
	 *		- /expires/						// returns token expiration date
	 *		- /all/							// returns all phpipam users
	 *		- /admins/						// returns ipam admins
	 */
	public function GET () {
		// token_expires
		if ($this->_params->id=="token_expires" || $this->_params->id=="token" || !isset($this->_params->id) || $this->_params->id=="all" || $this->_params->id=="admins") {
			// block IP
			$this->validate_block ();
			// validate token
			$this->validate_requested_token ();
			// users fetch
			if ($this->_params->id=="admins" || $this->_params->id=="all") {
				// fetch details
				$app_details = $this->fetch_app_details ();
				// permissions check - RWA required
				if ($app_details->app_permissions != 3) {
					$this->Response->throw_exception(503, 'Invalid app permissions');
				}
				// ok
				else {
					// admins or all
					if ($this->_params->id=="admins") {
						return array("code"=>200, "data"=>$this->User->fetch_multiple_objects ("users", "role", "Administrator", "id", true, false, "*"));
					}
					else {
						return array("code"=>200, "data"=>$this->User->fetch_all_objects ("users", "id", true));
					}
				}
			}
			else {
				return array("code"=>200, "data"=>array("expires"=>$this->token_expires));
			}
		}
		// return success for backwards compatibility
		else {
			$this->Response->throw_exception(400, 'Invalid identifier');
		}
	}





	/**
	 * Refreshes token and returns status
	 *
	 * @access public
	 * @return void
	 */
	public function POST () {
		// block IP
		$this->validate_block ();
		// authenticate user and provide token
		return $this->authenticate ();
	}





	/**
	 * Extends token validity
	 *
	 * @access public
	 * @return void
	 */
	public function PATCH () {
		// block IP
		$this->validate_block ();
		// validate token
		$this->validate_requested_token ();
		// refresh
		$this->refresh_token_expiration ();
		// ok
		return array("code"=>200, "data"=>array("expires"=>$this->token_expires));
	}






	/**
	 * Deletes token
	 *
	 * @access public
	 * @return void
	 */
	public function DELETE () {
		// block IP
		$this->validate_block ();
		// validate token
		$this->validate_requested_token ();
		// remove token
		$this->remove_token ();
		// result
		return array("code"=>200, "data"=>array("Token removed"));
	}





	/**
	 * Checks authentication token and refresh expiration
	 *
	 * @access public
	 * @return void
	 */
	public function check_auth () {
		// block IP
		$this->validate_block ();
		// validate token
		$this->validate_requested_token ();
		// refresh
		$this->refresh_token_expiration ();
	}





	/**
	 * Checks authentication token (app_code) from ssl_code method
	 *
	 * @method check_auth_code
	 * @param  string $app_id
	 * @return void
	 */
	public function check_auth_code ($app_id = "") {
		// block IP
		$this->validate_block ();
		// validate token
		$this->validate_requested_token_code ($app_id);
	}







	/* @blocks -------------------- */


	/**
	 * Checks if IP should be blocked form access
	 *
	 * @access private
	 * @return void
	 */
	private function validate_block () {
		// check if block is permitted
		if ($this->block_ip === true) {
			// get count
			$cnt = $this->User->block_check_ip ();
			// failure
			if ($cnt > $this->max_failures) 		{ $this->Response->throw_exception(500, "Your IP has been blocked for 5 minutes because of excesive login failures"); }
		}
	}






	/* @authentication -------------------- */

	/**
	 * Authenticates user and returns token and validity
	 *
	 * @access private
	 * @return void
	 */
	private function authenticate () {
		# if no user/pass are provided die with error
		if(!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])) {
			$this->Response->throw_exception(400, "Please provide username and password");
		}
		# try to authenticate user, it it fails it will fail by itself
		$this->User->authenticate ($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);

		# if token is valid and set extend it, otherwise generate new
		if ($this->validate_user_token ()) {
			// extend
			$this->refresh_token_expiration ();
		}
		else {
			// generate new token
			$this->generate_token ();
			// save to user
		    $this->save_user_token ();
		}

	    # result
	    return array("code"=>200, "data"=>array("token"=>$this->token, "expires"=>$this->token_expires));
	}

	/**
	 * Returns app details for validations
	 *
	 * @method fetch_app_details
	 * @return object]            app details
	 */
	private function fetch_app_details () {
		return $this->User->fetch_object ("api", "app_id", $_GET['app_id']);
	}











	/* @tokens -------------------- */


	/**
	 * Sets default validiy for token (default 12 hours)
	 *
	 * @access public
	 * @param int $token_valid_time (default: null)
	 * @return void
	 */
	public function set_token_valid_time ($token_valid_time = null) {
		// validate integer
		if (!is_null($token_valid_time)) {
			if (!is_numeric($token_valid_time))	{ $this->Response->throw_exception(500, "Token valid time must be an integer"); }
		}
		// save
		$this->token_valid_time = is_null($token_valid_time) ? 21600 : $token_valid_time;
	}

	/**
	 * Sets max number of failures before IP is blocked.
	 *
	 * @access public
	 * @param mixed $failures (default: null)
	 * @return void
	 */
	public function set_max_failures ($failures = null) {
		// validate integer
		if (!is_null($failures)) {
			if (!is_numeric($failures))	{ $this->Response->throw_exception(500, "Max failures must be an integer"); }
		}
		// save
		$this->max_failures = $failures==null ? 10 : $failures;
	}

	/**
	 * Block IP address.
	 *
	 * @access public
	 * @param bool $block (default: true)
	 * @return void
	 */
	public function block_ip ($block = true) {
		// validate boolean
		if (!is_bool($block))	{ $this->Response->throw_exception(500, "Block IP must be a boolean"); }
		// save
		$this->block_ip = $block;
	}

	/**
	 * Sets length of token
	 *
	 * @access public
	 * @param mixed $length (default: null)
	 * @return void
	 */
	public function set_token_length ($length = null) {
		// validate number
		if ($length!=null) {
			if (!is_numeric($length))				{ $this->Response->throw_exception(500, "token length must be an integer"); }
			elseif ($length>24)						{ $this->Response->throw_exception(500, "Maximum token length is 24 characters"); }
		}
		// save
		$this->token_length = is_null($length) ? 24 : $length;
	}

	/**
	 * Saves new token to database
	 *
	 * @access private
	 * @return void
	 */
	private function save_user_token () {
		# set token values
		$values = array(
					"id"=>$this->User->user->id,
					"token"=>$this->token,
					"token_valid_until"=>$this->token_expires
					);
		# save token to database
		if(!$this->Admin->object_modify ("users", "edit",  "id", $values ))
													{ $this->Response->throw_exception(500, "Failed to update token"); }
	}

	/**
	 * Validates users token from database
	 *
	 * @access private
	 * @return void
	 */
	private function validate_user_token () {
		// is set
		if (is_blank($this->User->user->token))							{ return false; }
		// date not set
		elseif (is_blank($this->User->user->token_valid_until))			{ return false; }
		// expired
		elseif ($this->User->user->token_valid_until < date("Y-m-d H:is:"))	{ return false; }
		// ok
		else																{ return true; }

	}

	/**
	 * Validates requested token and saves it to $this->token
	 *
	 * @access private
	 * @return void
	 */
	private function validate_requested_token () {
		$this->_params->controller=="user" ? $this->validate_requested_token_user () : $this->validate_requested_token_general ();
	}

	/**
	 * Validates token if User controller is requested - different response
	 *
	 * @access private
	 * @return void
	 */
	private function validate_requested_token_user () {
		// check that token is present
		if(!isset($_SERVER['HTTP_PHPIPAM_TOKEN']))	{ $this->Response->throw_exception(403, "Please provide token"); }
		// validate and remove token
		else {
			// fetch token - for SSL with APP code differently
			if($this->app->app_security=="ssl_code") {
				if($_SERVER['HTTP_PHPIPAM_TOKEN']!=$this->app->app_code)
													{ $this->Response->throw_exception(403, "Invalid token"); }
			}
			else {
				if(($token = $this->Admin->fetch_object ("users", "token", $_SERVER['HTTP_PHPIPAM_TOKEN'])) === false)
													{ $this->Response->throw_exception(403, "Invalid token"); }

				// save token
				$this->User->user    = $token;
				$this->token         = $token->token;
				$this->token_expires = $token->token_valid_until;

				// expired
				if($this->validate_token_expiration () === true)
													{  $this->Response->throw_exception(403, "Token expired");  }
			}
		}
	}

	/**
	 * Validates token if general controller is requested - different response
	 *
	 * @access private
	 * @return void
	 */
	private function validate_requested_token_general () {
		// check that token is present
		if(!isset($_SERVER['HTTP_PHPIPAM_TOKEN']))	{ $this->Response->throw_exception(401, $this->Response->errors[401]); }
		// validate and remove token
		else {
			// fetch token
			if(($token = $this->Admin->fetch_object ("users", "token", $_SERVER['HTTP_PHPIPAM_TOKEN'])) === false)
													{ $this->Response->throw_exception(401, $this->Response->errors[401]); }
			// save token
			$this->User->user    = $token;
			$this->token         = $token->token;
			$this->token_expires = $token->token_valid_until;

			// expired
			if($this->validate_token_expiration () === true)
													{  $this->Response->throw_exception(401, $this->Response->errors[401]);  }
			// refresh
			$this->refresh_token_expiration ();
		}
	}

	/**
	 * Validates token for ssl_code method
	 *
	 * @method validate_requested_token_code
	 * @param  string $app_id
	 * @return void
	 */
	private function validate_requested_token_code ($app_id) {
		// check that token is present
		if(!isset($_SERVER['HTTP_PHPIPAM_TOKEN']))	{ $this->Response->throw_exception(401, $this->Response->errors[401]); }
		// validate and remove token
		else {
			// fetch app_id from token
			if(($app_temp = $this->Admin->fetch_object ("api", "app_code", $_SERVER['HTTP_PHPIPAM_TOKEN'])) === false)
													{ $this->Response->throw_exception(401, $this->Response->errors[401]); }

			// if they dont match die
			if ($app_id != $app_temp->app_id)		{ $this->Response->throw_exception(403, "Invalid token"); }
		}
	}

	/**
	 * Checks if token has expired
	 *
	 * @access private
	 * @return void
	 */
	private function validate_token_expiration () {
		return strtotime($this->token_expires) < time() ? true : false;
	}

	/**
	 * Refreshes token expireation date in database
	 *
	 * @access private
	 * @return void
	 */
	private function refresh_token_expiration () {
		# reset values
		$this->token = $this->User->user->token;

		// convert existing expiry date string to a timestamp
		$expire_time = strtotime($this->token_expires);

		// Write Throttling from token updates
		// In order to keep the DB writes from token updates to a minimum, only update the expire time
		// if the expire time was set more than 60 seconds ago.
		if ( ((time()+$this->token_valid_time) - $expire_time) < 60) {
				return;
		}

		$this->token_expires = date("Y-m-d H:i:s", time()+$this->token_valid_time);
		# set token values
		$values = array(
					"id"=>$this->User->user->id,
					"token_valid_until"=>$this->token_expires
					);
		# save token to database
		if(!$this->Admin->object_modify ("users", "edit",  "id", $values ))
													{ $this->Response->throw_exception(500, "Failed to update token expiration date"); }
	}

	/**
	 * Removes users token
	 *
	 * @access private
	 * @return void
	 */
	private function remove_token () {
		# set token values
		$values = array(
					"id"=>$this->User->user->id,
					"token"=>null,
					"token_valid_until"=>null
					);
		# save token to database
		if(!$this->Admin->object_modify ("users", "edit",  "id", $values ))
													{ $this->Response->throw_exception(500, "Failed to remove token"); }

	}

	/**
	 * Generates new token for user and writes it to database
	 *
	 * @access private
	 * @return void
	 */
	private function generate_token () {
		// save token and valid time
		$this->token = $this->User->Crypto->generate_html_safe_token($this->token_length);
		$this->token_expires = date("Y-m-d H:i:s", time()+$this->token_valid_time);
	}

}
