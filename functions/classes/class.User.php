<?php

/**
*
*	User class to work with current user, authentication etc
*
*/

class User extends Common_functions {

	/**
	 * public variables
	 */
	public $username;						// (char) username
	public $api = false;					// from api
	public $authenticated = false;			// (bin) flag if user is authenticated
	public $timeout = false;				// (bin) timeout flag
	public $user = null;					// (obj) user details
	public $isadmin = false;				// (bin) flag if user is admin
	public $blocklimit = 5;					// (int) limit for IP block

	/**
	 * private variables
	 */
	private $authmethodid = 1;				// (int) authentication method id for user
	private $authmethodtype = "local";		// (char) authentication method type
	private $ldap = false;					// (bin) LDAP flag
	private $ip;							// (char) Users IP address

	/**
	 * protected variables
	 */
	protected $sessname = "phpipam";		// session name - default is phpipam
	protected $authmethodparams;			// (json) parameters for authentication
	protected $debugging = false;			// (bool) debugging flag

	/**
	 * object holders
	 */
	public $Result;							// for Result printing
	protected $Database;					// for Database connection
	public $Log;							// for Logging connection






	/**
	 * __construct function
	 *
	 * @access public
	 */
	public function __construct (Database_PDO $database, $api = false) {

		# Save database object
		$this->Database = $database;
		# set api
		$this->api = $api;
		# initialize Result
		$this->Result = new Result ();

		# get settings
		$this->get_settings ();

		# Log object
		$this->Log = new Logging ($this->Database, $this->settings);

		# register new session
		$this->register_session ();
		# check timeut
		$this->check_timeout ();
		# set authenticated flag
		$this->is_authenticated ();
		# get users IP address
		$this->block_get_ip ();
	}










	/**
	 * @session management functions
	 * ------------------------------
	 */

	/**
	 * registers new session
	 *
	 * @access private
	 * @return void
	 */
	private function register_session () {
		// not for api
		if ($this->api !== true) {
			//set session name
			$this->set_session_name();
			//set debugging
			$this->set_debugging();
			//register session
			session_name($this->sessname);
			if(@$_SESSION===NULL) {
			session_start();
			}
		}
	}

	/**
	 * destroys session
	 *
	 * @access public
	 * @return void
	 */
	public function destroy_session () {
		session_destroy();
	}

	/**
	 * sets session name if specified in config file
	 *
	 * @access private
	 * @return void
	 */
	private function set_session_name () {
		include( dirname(__FILE__) . '/../../config.php' );
		$this->sessname = strlen(@$phpsessname)>0 ? $phpsessname : "phpipam";
	}

	/**
	 * saves parameters to session after authentication succeeds
	 *
	 * @access private
	 * @return void
	 */
	private function write_session_parameters () {
		// not for api
		if ($this->api !== true) {
			$_SESSION['ipamusername'] = $this->user->username;
			$_SESSION['ipamlanguage'] = $this->fetch_lang_details ();
			$_SESSION['lastactive']   = time();
		}
	}

	/**
	 * Uodate users language
	 *
	 * @access public
	 * @return void
	 */
	public function update_session_language () {
		// not for api
		if ($this->api !== true) {
			# update user object
			$this->fetch_user_details ($this->username);
			$_SESSION['ipamlanguage'] = $this->fetch_lang_details ();
		}
	}

	/**
	 * Checks if user is authenticated - session is set
	 *
	 * @access public
	 * @return void
	 */
	public function is_authenticated ($die = false) {
		# if checked for subpages first check if $user is array
		if(!is_array($this->user)) {
			if( isset( $_SESSION['ipamusername'] ) && strlen( @$_SESSION['ipamusername'] )>0 ) {
				# save username
				$this->username = $_SESSION['ipamusername'];
				# check for timeout
				if($this->timeout == true) {
					$this->authenticated = false;
				}
				else {
					# fetch user profile and save it
					$this->fetch_user_details ($this->username);

					$this->authenticated = true;
					$this->reset_inactivity_time();
					$this->update_activity_time ();
					# bind language
					$this->set_ui_language();
				}
			}
			else {
				$this->authenticated = false;
			}
		}
	}

	/**
	 * Checks if current user is admin or not
	 *
	 * @access public
	 * @param bool $die (default: true)
	 * @return void
	 */
	public function is_admin ($die = true) {
		if($this->isadmin)		{ return true; }
		else {
			if($die)			{ $this->Result->show("danger", _('Administrator level privileges required'), true); }
			else				{ return false; }
		}
	}

	/**
	 * checks if user is authenticated, if not redirects to login page
	 *
	 * @access public
	 * @param bool $redirect (default: true)
	 * @return void
	 */
	public function check_user_session ($redirect = true) {
		# not authenticated
		if($this->authenticated===false && $redirect) {
			# set url
			$url = $this->createURL();

			# error print for AJAX
			if(@$_SERVER['HTTP_X_REQUESTED_WITH'] == "XMLHttpRequest") {
				# for AJAX always check origin
				$this->check_referrer ();
				# kill session
				$this->destroy_session ();
				# error
				$this->Result->show("danger", _('Please login first')."!<hr><a class='btn btn-sm btn-default' href='".$url.create_link ("login")."'>"._('Login')."</a>", true, true);
			}
			# timeout
			elseif ($this->timeout) {
				# set redirect cookie
				$this->set_redirect_cookie ();
				header("Location:".$url.create_link ("login","timeout"));
			}
			else {
				# set redirect cookie
				$this->set_redirect_cookie ();
				header("Location:".$url.create_link ("login"));
			}
		}
	}

	/**
	 * Check if users timeout expired
	 * 	if yes set timeout flag
	 *
	 * @access private
	 * @return none
	 */
	private function check_timeout () {
		//session set
		if(isset($_SESSION['lastactive'])) {
			if( strlen($this->settings->inactivityTimeout)>0 && (time()-@$_SESSION['lastactive']) > $this->settings->inactivityTimeout) {
				$this->timeout = true;
				unset($_SESSION['lastactive']);
			}
		}
	}

	/**
	 * resets inactivity time after each succesfull login
	 *
	 * @access private
	 * @return void
	 */
	private function reset_inactivity_time () {
		if($this->timeout!==true) {
			$_SESSION['lastactive'] = time();
		}
	}

	/**
	 * Saves redirect cookie if session times out
	 *
	 * @access private
	 * @return void
	 */
	private function set_redirect_cookie () {
		# save current redirect vaule
		if($_SERVER['SCRIPT_URL']!="/login/" && $_SERVER['SCRIPT_URL']!="logout" && $_SERVER['SCRIPT_URL']!="?page=login" && $_SERVER['SCRIPT_URL']!="?page=logout" && $_SERVER['SCRIPT_URL']!="/" && $_SERVER['SCRIPT_URL']!="%2f");
		setcookie("phpipamredirect", $_SERVER['REQUEST_URI'], time()+10, "/");
	}

	/**
	 * Sets translation for logged in user
	 *
	 * @access private
	 * @return void
	 */
	private function set_ui_language () {
		if(strlen($_SESSION['ipamlanguage'])>0) 	{
			putenv("LC_ALL=$_SESSION[ipamlanguage]");
			setlocale(LC_ALL, $_SESSION['ipamlanguage']);		// set language
			bindtextdomain("phpipam", "./functions/locale");	// Specify location of translation tables
			textdomain("phpipam");								// Choose domain
		}
	}

	/**
	 *	Check if migration of AD settings is required
	 *
	 *	must be deleted after 1.2 release
	 *	along with:
	 *		> `settings`.`authmigrated`
	 *		> `settings`.`domainAuth`
	 *		> `settingsDomain`
	 *		> `users`.`domainUser`
	 *
	 * @access public
	 * @return void
	 */
	public function migrate_domain_settings () {
		# if not already migrated migrate settings!
		if($this->settings->authmigrated==0) {
			# only if AD used
			if($this->settings->domainAuth!=0) {
				# fetch AD settings
				$err = false;
				try { $ad = $this->Database->getObject("settingsDomain",1); }
				catch (Exception $e) { $err = true; }

				if($err == false) {
					# remove editDate
					unset($ad->editDate);
					# save to json array
					$ad = json_encode($ad);
					# update usersAuthMethod
					$type = $this->settings->domainAuth==1 ? "AD" : "LDAP";
					# update
					try { $this->Database->insertObject("usersAuthMethod", array("type"=>$type, "params"=>$ad, "description"=>$type." authentication", "protected"=>"No")); }
					catch (Exception $e) { $err = true; }
					# set migrated flag
					if($err == false) {
					try { $this->Database->updateObject("settings", array("id"=>1,"authmigrated"=>1), 'id'); }
					catch (Exception $e) { }
					}
				}
			}
		}
	}










	/**
	 * @miscalaneous methods
	 * ------------------------------
	 */

	/**
	 * Checks AJAX loaded pages for proper origin
	 *
	 * @access private
	 * @return void
	 */
	private function check_referrer () {
	    if ( ($_SERVER['HTTP_X_REQUESTED_WITH'] != "XMLHttpRequest") && ($_SERVER['HTTP_ORIGIN'] != $_SERVER['HTTP_HOST'] ) ) {
	        # write log and die
	    	$this->Log->write ("referrer_check", _('Page not referred properly'), 0 );
	        $this->Result->show ("danger", _('Page not referred properly', true));
	    }
	}

	/**
	 * fetches default language
	 *
	 * @access public
	 * @return void
	 */
	public function get_default_lang () {
		try { $lang = $this->Database->findObject("lang","l_id",$this->settings->defaultLang); }
		catch (Exception $e) { $this->debugging ? : $this->Result->show("danger", _("Database error: ").$e->getMessage()); }

		return $lang;
	}

	/**
	 * Sets available authentication methods
	 *
	 *	Can be extended by reading set properties from set field options
	 *
	 * @access public
	 * @return void
	 */
	public function fetch_available_auth_method_types () {
		return array("AD", "LDAP", "NetIQ", "Radius");
	}










	/**
	 * @favourite methods
	 * ------------------------------
	 */

	/**
	 * Fetches details for users favourite subnets
	 *
	 * @access public
	 * @return void
	 */
	public function fetch_favourite_subnets () {
	    # none
	    if(strlen($this->user->favourite_subnets)==0) {
		    return false;
	    }
	    # ok
	    else {
	    	# store to array
	    	$subnets = explode(";", $this->user->favourite_subnets);
	    	$subnets = array_filter($subnets);

	    	if(sizeof($subnets)>0) {
		    	# fetch details for each subnet
				foreach($subnets as $id) {
					$query = "select `su`.`id` as `subnetId`,`se`.`id` as `sectionId`, `subnet`, `mask`,`su`.`description`,`se`.`description` as `section`, `vlanId`, `isFolder`
							  from `subnets` as `su`, `sections` as `se` where `su`.`id` = ? and `su`.`sectionId` = `se`.`id` limit 1;";

					try { $fsubnet = $this->Database->getObjectQuery($query, array($id)); }
					catch (Exception $e) {
						$this->Result->show("danger", _("Error: ").$e->getMessage());
						return false;
					}

				    # out array
				    $fsubnets[] = (array) $fsubnet;
				}
			    return $fsubnets;
			} else {
				return false;
			}
	    }
	}

	/**
	 * Edit users favourites
	 *
	 * @access public
	 * @param mixed $post
	 * @return void
	 */
	public function edit_favourite($action, $subnetId) {
		# execute
		if($action=="remove")	{ return $this->remove_favourite ($subnetId); }
		elseif($action=="add")	{ return $this->add_favourite ($subnetId); }
		else					{ return false; }
	}

	/**
	 * Remove subnet from user favourite subnets
	 *
	 * @access private
	 * @param mixed $subnetId
	 * @return void
	 */
	private function remove_favourite ($subnetId) {
		# set old favourite subnets
		$old_favourites = explode(";", $this->user->favourite_subnets);
		# set new
		$new_favourites = implode(";", array_diff($old_favourites, array($subnetId)));
		# update
		try { $this->Database->updateObject("users", array("favourite_subnets"=>$new_favourites, "id"=>$this->user->id), "id"); }
		catch (Exception $e) {
			return false;
		}
		return true;
	}

	/**
	 * Add subnet to user favourite subnets
	 *
	 * @access private
	 * @param int $subnetId
	 * @return boolena
	 */
	private function add_favourite ($subnetId) {
		# set old favourite subnets
		$old_favourites = explode(";", $this->user->favourite_subnets);
		$old_favourites = is_array($old_favourites) ? $old_favourites : array();
		# set new
		$new_favourites = implode(";",array_merge(array($subnetId), $old_favourites));
		# update
		try { $this->Database->updateObject("users", array("favourite_subnets"=>$new_favourites, "id"=>$this->user->id), "id"); }
		catch (Exception $e) {
			return false;
		}
		return true;
	}

	/**
	 * Checks if subnet is in users favourite subnets
	 *
	 * @access public
	 * @param int $subnetId
	 * @return boolean
	 */
	public function is_subnet_favourite ($subnetId) {
		$this->fetch_favourite_subnets ();
		# check if in array
	    $subnets = explode(";", $this->user->favourite_subnets);
	    $subnets = array_filter($subnets);
	    # result
	    return in_array($subnetId, $subnets) ? true : false;
	}

	/**
	 * Checks if folder is favourite - alias for is subnet favourite
	 *
	 * @access public
	 * @param mixed $subnetId
	 * @return void
	 */
	public function is_folder_favourite ($subnetId) {
		return $this->is_subnet_favourite ($subnetId);
	}











	/**
	* @authentication functions
	* -------------------------------
	*/

	/**
	 * Main function for authenticating users
	 *
	 *	> tries to fetch user details from database by username
	 *	> sets authentication method and checks validity
	 *	> authenticates
	 *
	 * @access public
	 * @param mixed $username
	 * @param mixed $password
	 * @return void
	 */
	public function authenticate ($username, $password) {

		# first we need to check if username exists
		$this->fetch_user_details ($username);
		# set method type if set, otherwise presume local auth
		$this->authmethodid = strlen(@$this->user->authMethod)>0 ? $this->user->authMethod : 1;

		# get authentication method details
		$this->get_auth_method_type ();

		# authenticate based on name of auth method
		if(!method_exists($this, $this->authmethodtype))	{
			$this->Log->write ("User login", _('Error: Invalid authentication method'), 1 );
			$this->Result->show("danger", _("Error: Invalid authentication method"), true);
		}
		else {
			# set method name variable
			$authmethodtype = $this->authmethodtype;
			# authenticate
			$this->$authmethodtype ($username, $password);
		}
	}

	/**
	 * tries to fetch user datails from database by username if not already existing locally
	 *
	 * @access private
	 * @param mixed $username
	 * @return void
	 */
	private function fetch_user_details ($username) {
		# only if not already active
		if(!is_object($this->user)) {
			try { $user = $this->Database->findObject("users", "username", $username); }
			catch (Exception $e) 	{ $this->Result->show("danger", _("Error: ").$e->getMessage(), true);}

			# if not result return false
			$usert = (array) $user;

			# admin?
			if($user->role == "Administrator")	{ $this->isadmin = true; }

			if(sizeof($usert)==0)	{ $this->block_ip (); $this->Log->write ("User login", _('Invalid username'), 1, $username ); $this->Result->show("danger", _("Invalid username or password"), true);}
			else 					{ $this->user = $user; }
		}
	}

	/**
	 * Fetch all languages from database.
	 *
	 * @access public
	 * @return void
	 */
	public function fetch_langs () {
		try { $langs = $this->Database->getObjects("lang", "l_id"); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage());
			return false;
		}
		# return
		return $langs;
	}

	/**
	 * fetches language details from database
	 *
	 * @access private
	 * @return void
	 */
	private function fetch_lang_details () {
		try { $lang = $this->Database->findObject("lang", "l_id", $this->user->lang); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage(), true);
		}
		return $lang->l_code;
	}

	/**
	 * Fetches name and details of authentication method (local, AD, LDAP, ...) from DB and saves them to var
	 *
	 * @access private
	 * @return void
	 */
	private function get_auth_method_type () {
		# for older versions - only local is available!
		if($this->settings->version=="1.1") {
			$this->authmethodtype = "auth_local";
		}
		else {
			try { $method = $this->Database->getObject("usersAuthMethod", $this->authmethodid); }
			catch (Exception $e) {
				$this->Result->show("danger", _("Error: ").$e->getMessage(), true);
			}
			# save method name if existing
			if($method!==false) {
				$this->authmethodtype   = "auth_".$method->type;
				$this->authmethodparams = $method->params;
			}
		}
	}

	/**
	 * local user authentication method, authenticates users through local DB entry
	 * we provide user object from DB, and username/password entered by users
	 *
	 * @access private
	 * @param mixed $username
	 * @param mixed $password
	 * @return void
	 */
	private function auth_local ($username, $password) {
		# auth ok
		if($this->user->password == crypt($password, $this->user->password)) {
			# save to session
			$this->write_session_parameters ();

			$this->Result->show("success", _("Login successful"));
			$this->Log->write( "User login", "User ".$this->user->real_name." logged in", 0, $username );

			# write last logintime
			$this->update_login_time ();

			# remove possible blocked IP
			$this->block_remove_entry ();
		}
		# auth failed
		else {
			# add blocked count
			$this->block_ip ();

			$this->Log->write( "User login", "Invalid username or password", 1, $username );
			$this->Result->show("danger", _("Invalid username or password"), true);
		}
	}

	/**
	 *	Connect to a directory given our auth method settings
	 *
	 *	Connect using adLDAP
	 *
	 * @access private
	 * @param mixed $authparams
	 * @return adLDAP object
	 */
	private function directory_connect ($authparams) {

		# adLDAP script
		require(dirname(__FILE__) . "/../adLDAP/src/adLDAP.php");

		$dirparams = Array();
		$dirparams['base_dn'] = @$authparams['base_dn'];
		$dirparams['ad_port'] = @$authparams['ad_port'];
		$dirparams['account_suffix'] = @$authparams['account_suffix'];
		$dirparams['domain_controllers'] = explode(";", str_replace(" ", "", $authparams['domain_controllers']));

		// set ssl and tls separate for ldap and AD
		if ($this->ldap) {
			// set ssl and tls
			$dirparams['use_ssl'] = false;
			$dirparams['use_tls'] = false;

			if ($authparams['ldap_security'] == 'tls') 		{ $dirparams['use_tls'] = true; }
			elseif ($authparams['ldap_security'] == 'ssl') 	{ $dirparams['use_ssl'] = true; }

			if (isset($authparams['admin_username']) && isset($authparams['admin_password'])) {
				$dirparams['admin_username'] = $authparams['adminUsername'];
				$dirparams['admin_password'] = $authparams['adminPassword'];
			}
		}
		else {
			$dirparams['use_ssl'] = @$authparams['use_ssl'];
			$dirparams['use_tls'] = @$authparams['use_tls'];
		}

		# open connection
		try {
			# Initialize adLDAP
			$dirconn = new adLDAP($dirparams);

		} catch (adLDAPException $e) {
			$this->Log->write("Directory connection error", "Failed to connect: " . $e->getMessage(), 2, $username);
			$this->Result->show("danger", _("Error: ") . $e->getMessage(), true);
		}

		return $dirconn;
	}

	/**
	 *	Authenticate against a directory
	 *
	 *	Authenticates users against a directory - AD or LDAP
	 *	Using library > adLDAP - LDAP Authentication with PHP for Active Directory
	 *	http://adldap.sourceforge.net
	 *
	 * @access private
  	 * @param array $authparams
  	 * @param mixed $username
	 * @param mixed $password
	 * @return void
	 */
	private function directory_authenticate ($authparams, $username, $password) {
		// set method
		$method = $this->ldap ? "LDAP" : "AD";
		// connect
		$adldap = $this->directory_connect($authparams);

		# authenticate
		try {
			if ($adldap->authenticate($username, $password)) {
				# save to session
				$this->write_session_parameters();

				$this->Log->write($method . " login", "User " . $this->user->real_name . " logged in via " . $method, 0, $username);
				$this->Result->show("success", _($method . " Login successful"));

				# write last logintime
				$this->update_login_time();
				# remove possible blocked IP
				$this->block_remove_entry();
			} # wrong user/pass by default
			else {
				# add blocked count
				$this->block_ip();
				$this->Log->write($method . " login", "User $username failed to authenticate against " . $method, 1, $username);
				$this->Result->show("danger", _("Invalid username or password " . $username ), true);

			}
		} catch (adLDAPException $e) {
			$this->Log->write("Error", "Something went wrong during auth: " . $e->getMessage(), 2, $username);
			$this->Result->show("danger", _("Error: ") . $e->getMessage(), true);
		}

	}

	/**
	 *	AD (Active directory) authentication function
	 *
	 *
	 * @access private
	 * @param mixed $username
	 * @param mixed $password
	 * @return void
	 */
	private function auth_AD ($username, $password) {
		// parse settings for LDAP connection and store them to array
		$authparams = json_decode($this->authmethodparams, true);
		// authenticate
		$this->directory_authenticate($authparams, $username, $password);
	}

	/**
	 *	LDAP authentication
	 *	same as AD authentication, only set the LDAP flag to true
	 *
	 * @access private
	 * @param mixed $username
	 * @param mixed $password
	 * @return void
	 */
	private function auth_LDAP ($username, $password) {
		// parse settings for LDAP connection and store them to array
		$authparams = json_decode($this->authmethodparams, true);
		$this->ldap = true;							//set ldap flag

		// set uid
		if (isset($authparams['uid_attr'])) { $udn = $authparams['uid_attr'] . '=' . $username; }
		else 								{ $udn = 'uid=' . $username; }
		// set DN
		if (isset($authparams['users_base_dn'])) { $udn = $udn . "," . $authparams['users_base_dn']; }
		else 									 { $udn = $udn . "," . $authparams['base_dn']; }
		// authenticate
		$this->directory_authenticate($authparams, $udn, $password);
	}

	/**
	 *	NetIQ authentication
	 *	same as AD authentication, only add cn= before username
	 *
	 * @access private
	 * @param mixed $username
	 * @param mixed $password
	 * @return void
	 */
	private function auth_NetIQ ($username, $password) {
		$this->auth_AD ("cn=".$username, $password);
	}
	/**
	 * Authenticates user on radius server
	 *
	 * @access private
	 * @param mixed $username
	 * @param mixed $password
	 * @return void
	 */
	private function auth_radius ($username, $password) {
		# decode radius parameters
		$params = json_decode($this->authmethodparams);

		# check for socket support !
		if(!in_array("sockets", get_loaded_extensions())) {
			$this->Log->write( "Radius login", "php Socket extension missing", 2 );
			$this->Result->show("danger", _("php Socket extension missing"), true);
		}

		# initialize radius class
		require( dirname(__FILE__) . '/class.Radius.php' );
		$Radius = new Radius ($params->hostname, $params->secret, $params->suffix, $params->timeout, $params->port);
		$Radius->SetNasIpAddress($params->hostname);
		//debugging
		$this->debugging!==true ? : $Radius->SetDebugMode(TRUE);

		# authenticate
		$auth = $Radius->AccessRequest($username, $password);
		# debug?
		if($this->debugging) {
			print "<pre style='width:700px;margin:auto;margin-top:10px;'>";
			print(implode("<br>", $Radius->debug_text));
			print "</pre>";
		}

		# authenticate user
		if($auth) {
			# save to session
			$this->write_session_parameters ();

	    	$this->Log->write( "Radius login", "User ".$this->user->real_name." logged in via radius", 0, $username );
	    	$this->Result->show("success", _("Radius login successful"));

			# write last logintime
			$this->update_login_time ();
			# remove possible blocked IP
			$this->block_remove_entry ();
		}
		else {
			# add blocked count
			$this->block_ip ();
			$this->Log->write( "Radius login", "Failed to authenticate user on radius server", 2, $username );
			$this->Result->show("danger", _("Invalid username or password"), true);
		}
	}










	/**
	 *	@crypt functions
	 *	------------------------------
	 */


	/**
	 *	function to crypt user pass, randomly generates salt. Use sha256 if possible, otherwise Blowfish or md5 as fallback
	 *
	 *		types:
	 *			CRYPT_MD5 == 1   		(Salt starting with $1$, 12 characters )
	 *			CRYPT_BLOWFISH == 1		(Salt starting with $2a$. The two digit cost parameter: 09. 22 characters )
	 *			CRYPT_SHA256 == 1		(Salt starting with $5$rounds=5000$, 16 character salt.)
	 *			CRYPT_SHA512 == 1		(Salt starting with $6$rounds=5000$, 16 character salt.)
	 *
	 * @access public
	 * @param mixed $input
	 * @return void
	 */
	public function crypt_user_pass ($input) {
		# initialize salt
		$salt = "";
		# set possible salt characters in array
		$salt_chars = array_merge(range('A','Z'), range('a','z'), range(0,9));
		# loop to create salt
		for($i=0; $i < 22; $i++) { $salt .= $salt_chars[array_rand($salt_chars)]; }
		# get prefix
		$prefix = $this->detect_crypt_type ();
		# return crypted variable
		return crypt($input, $prefix.$salt);
	}

	/**
	 *	this function will detect highest crypt type to use for system
	 *
	 * @access public
	 * @return void
	 */
	private function detect_crypt_type () {
		if(CRYPT_SHA512 == 1)		{ return '$6$rounds=3000$'; }
		elseif(CRYPT_SHA256 == 1)	{ return '$5$rounds=3000$'; }
		elseif(CRYPT_BLOWFISH == 1)	{ return '$2y$'.str_pad(rand(4,31),2,0, STR_PAD_LEFT).'$'; }
		elseif(CRYPT_MD5 == 1)		{ return '$5$rounds=3000$'; }
		else						{ $this->Result->show("danger", _("No crypt types supported"), true); }
	}

	/**
	 * Updates users password
	 *
	 * @access public
	 * @param mixed $password
	 * @return void
	 */
	public function update_user_pass ($password) {
		try { $this->Database->updateObject("users", array("password"=>$this->crypt_user_pass ($password), "passChange"=>"No", "id"=>$this->user->id), "id"); }
		catch (Exception $e) { $this->Result->show("danger", $e->getMessage(), true); }

		$this->Result->show("success", "Hi, ".$this->user->real_name.", "._("your password was updated").". <a class='btn btn-sm btn-default' href='".create_link("dashboard")."'>Dashboard</a>", false);
	}










	/**
	 *	@updating user methods
	 *	------------------------------
	 */

	/**
	 * User self update method
	 *
	 * @access public
	 * @param mixed $post //posted user details
	 * @return void
	 */
	public function self_update($post) {
		# set items to update
		$items  = array("real_name"=>$post['real_name'],
						"mailNotify"=>$post['mailNotify'],
						"mailChangelog"=>$post['mailChangelog'],
						"email"=>$post['email'],
						"lang"=>$post['lang'],
						"id"=>$this->user->id,
						//display
						"compressOverride"=>$post['compressOverride'],
						"hideFreeRange"=>$this->verify_checkbox(@$post['hideFreeRange']),
						"printLimit"=>@$post['printLimit']
						);
		if(strlen($post['password1'])>0) {
		$items['password'] = $this->crypt_user_pass ($post['password1']);
		}

	    # prepare log file
	    $log = $this->array_to_log ($post);

		# update
		try { $this->Database->updateObject("users", $items); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage(), false);
			$this->Log->write( "User self update", "User self update failed!<br>".$log, 2 );
			return false;
		}
		# update language
		$this->update_session_language ();

		# ok, update log table
		$this->Log->write( "User self update", "User self update suceeded!", 0 );
	    return true;
	}

	/**
	 * User self update widgets.
	 *
	 * @access public
	 * @param mixed $widgets
	 * @return void
	 */
	public function self_update_widgets ($widgets) {
		# update
		try { $this->Database->updateObject("users", array("widgets"=>$widgets, "id"=>$this->user->id)); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage(), false);
			return false;
		}
		# ok, update log table
	    return true;
	}

	/**
	 * Updates last users login time
	 *
	 * @access public
	 * @return void
	 */
	public function update_login_time () {
		# fix for older versions
		if($this->settings->version!="1.1") {
			# update
			try { $this->Database->updateObject("users", array("lastLogin"=>date("Y-m-d H:i:s"), "id"=>$this->user->id)); }
			catch (Exception $e) {
				$this->Result->show("danger", _("Error: ").$e->getMessage(), false);
				return false;
			}
		}
	}

	/**
	 * Updates last users activity time
	 *
	 * @access public
	 * @return void
	 */
	public function update_activity_time () {
		# update
		try { $this->Database->updateObject("users", array("lastActivity"=>date("Y-m-d H:i:s"), "id"=>$this->user->id)); }
		catch (Exception $e) { }
	}








	/**
	 *	@blocking IP functions
	 *	------------------------------
	 */


	/**
	 * sets limit for failed login attempts
	 *
	 * @access public
	 * @param int $limit
	 * @return none
	 */
	public function set_block_limit ($limit) {
		$this->blocklimit = $limit;
	}

	/**
	 * checks if IP is blocked and returns count for entries
	 *
	 * @access public
	 * @param none
	 * @return int on match, false on no result
	 */
	public function block_check_ip () {
		# first purge
		$this->purge_blocked_entries ();
		$this->block_get_ip ();
		# set date and query
		$now = date("Y-m-d H:i:s", time() - 5*60);
		$query = "select count from `loginAttempts` where `ip` = ? and `datetime` > ?;";
		# fetch
		try { $cnt = $this->Database->getObjectQuery($query, array($this->ip, $now)); }
		catch (Exception $e) { !$this->debugging ? : $this->Result->show("danger", $e->getMessage(), false); }

	    # verify
	    return @$cnt->count>0 ? $cnt->count : false;
	}

	/**
	 * adds new IP to block or updates count if already present
	 *
	 * @access private
	 * @return void
	 */
	private function block_ip () {
		# validate IP
		if(!filter_var($this->ip, FILTER_VALIDATE_IP))	{ return false; }

		# first check if already in
		if($this->block_check_ip ()) 		{ $this->block_update_count(); }
		# if not in add first entry
		else 								{ $this->block_add_entry(); }
	}

	/**
	 * sets IP address to block
	 * needed for proxy access to block end user not whole proxy
	 *
	 * @access private
	 * @return void
	 */
	private function block_get_ip () {
		# set IP
		if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))	{ $this->ip = @$_SERVER['HTTP_X_FORWARDED_FOR']; }
		else										{ $this->ip = @$_SERVER['REMOTE_ADDR']; }
	}

	/**
	 * purges login attampts more than 5 minutes old (since last attempt)
	 *
	 * @access private
	 * @return void
	 */
	private function purge_blocked_entries () {
		# set date 5 min ago and query
		$ago = date("Y-m-d H:i:s", time() - 5*60);
		$query = "delete from `loginAttempts` where `datetime` < ?; ";

		try { $this->Database->runQuery($query, array($ago)); }
		catch (Exception $e) { !$this->debugging ? : $this->Result->show("danger", $e->getMessage(), false); }
	}

	/**
	 * updates existing log attampt count
	 *
	 * @access private
	 * @return void
	 */
	private function block_update_count() {
		# query
		$query = "update `loginAttempts` set `count`=`count`+1 where `ip` = ?; ";
		try { $this->Database->runQuery($query, array($this->ip)); }
		catch (Exception $e) { !$this->debugging ? : $this->Result->show("danger", $e->getMessage(), false); }
	}

	/**
	 * adds new IP entry to block with count 1
	 *
	 * @access private
	 * @return void
	 */
	private function block_add_entry() {
		try { $this->Database->insertObject("loginAttempts", array("ip"=>$this->ip, "count"=>1)); }
		catch (Exception $e) { !$this->debugging ? : $this->Result->show("danger", $e->getMessage(), false); }
	}

	/**
	 * removes blocked IP entry if it exists on successfull login
	 *
	 * @access private
	 * @return void
	 */
	private function block_remove_entry() {
		try { $this->Database->deleteRow("loginAttempts", "ip", $this->ip); }
		catch (Exception $e) { !$this->debugging ? : $this->Result->show("danger", $e->getMessage(), false); }
	}
}
?>