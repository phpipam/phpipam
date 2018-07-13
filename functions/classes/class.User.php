<?php

/**
*
*  User class to work with current user, authentication etc
*
*/
class User extends Common_functions {


    /**
     * Current username
     *
     * @var string
     */
    public $username;

    /**
     * from api flag
     *
     * (default value: false)
     *
     * @var bool
     */
    public $api = false;

    /**
     * flag if user is authenticated
     *
     * (default value: false)
     *
     * @var bool
     */
    protected $authenticated = false;

    /**
     * timeout flag - is timeout reached
     *
     * (default value: false)
     *
     * @var bool
     */
    protected $timeout = false;

    /**
     * user details
     *
     * (default value: null)
     *
     * @var object
     */
    public $user = null;

    /**
     * flag if user is admin
     *
     * (default value: false)
     *
     * @var bool
     */
    protected $isadmin = false;

    /**
     * limit for IP block - after how many attampts user is blocked
     *
     * (default value: 5)
     *
     * @var int
     */
    public $blocklimit = 5;

    /**
     * authentication method id for user
     *
     * (default value: 1)
     *
     * @var int
     */
    private $authmethodid = 1;

    /**
     * authentication method type
     *
     * (default value: "local")
     *
     * @var string
     */
    private $authmethodtype = "local";

    /**
     * ldap is used flag
     *
     * (default value: false)
     *
     * @var bool
     */
    private $ldap = false;

    /**
     * Users IP address
     *
     * @var mixed
     */
    private $ip;

    /**
     * session name
     *
     * (default value: "phpipam")
     *
     * @var string
     */
    protected $sessname = "phpipam";

    /**
     * Set allowed themes
     *
     * @var array
     */
    public $themes = array("white", "dark");

    /**
     * (json) parameters for authentication
     *
     * @var mixed
     */
    protected $authmethodparams;

    /**
     *  debugging flag
     *
     * (default value: false)
     *
     * @var bool
     */
    protected $debugging = false;

    /**
     * Result object
     *
     * @var object
     */
    public $Result;

    /**
     * for Database connection
     *
     * @var mixed
     */
    protected $Database;

    /**
     * for Logging connection
     *
     * @var object
     */
    public $Log;

    /**
     * Cryptographic functions
     * @var Crypto
     */
    public $Crypto;


    /**
     * __construct function.
     *
     * @access public
     * @param Database_PDO $database
     * @param bool $api (default: false)
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

        # initialize Crypto
        $this->Crypto = new Crypto ();

        # register new session
        $this->register_session ();
        # check timeut
        $this->check_timeout ();
        # set authenticated flag
        $this->is_authenticated ();
        # get users IP address
        $this->block_get_ip ();
        # set theme
        $this->set_user_theme ();
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
            // set default params
            $this->set_session_ini_params ();
            //register session
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
        if(!isset($_SESSION)) {
            include( dirname(__FILE__).'/../../config.php' );
            $this->sessname = strlen(@$phpsessname)>0 ? $phpsessname : "phpipam";
        }
    }

    /**
     * Default session parameters for phpipam - MAX
     *
     *  gc_maxlifetime  : time for server to keep data parameters for (at least 24 hours)
     *  cookie_lifetime : time for client browser to keep cookies
     *
     * @access private
     * @return void
     */
    private function set_session_ini_params () {
        if(!isset($_SESSION)) {
            ini_set('session.gc_maxlifetime', 86400);
            ini_set('session.cookie_lifetime', 86400);
        }
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
     * Update users language
     *
     * @access public
     * @return void
     */
    public function update_session_language () {
        // not for api
        if ($this->api !== true) {
            # update user object
            $this->fetch_user_details ($this->username, true);
            $_SESSION['ipamlanguage'] = $this->fetch_lang_details ();
        }
    }

    /**
     * Checks if user is authenticated - session is set
     *
     * @access public
     * @return bool
     */
    public function is_authenticated () {
        # if checked for subpages first check if $user is array
        if(!is_array($this->user)) {
            if( isset( $_SESSION['ipamusername'] ) && strlen( @$_SESSION['ipamusername'] )>0 ) {
                # save username
                $this->username = $_SESSION['ipamusername'];
                # check for timeout
                if($this->timeout === true) {
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
        }

        # return
        return $this->authenticated;
    }

    /**
     * Checks if current user is admin or not
     *
     * @access public
     * @param bool $die (default: true)
     * @return string|bool
     */
    public function is_admin ($die = true) {
        if($this->isadmin)      { return true; }
        else {
            if($die)            { $this->Result->show("danger", _('Administrator level privileges required'), true); }
            else                { return false; }
        }
    }

    /**
     * checks if user is authenticated, if not redirects to login page
     *
     * @access public
     * @param bool $redirect (default: true)
     * @return string|false
     */
    public function check_user_session ($redirect = true) {
        # not authenticated
        if($this->authenticated===false) {
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
                die();
            }
            # timeout
            elseif ($this->timeout) {
                # set redirect cookie
                $this->set_redirect_cookie ();
                # redirect
                if ($redirect)
                header("Location:".$url.create_link ("login","timeout"));
                die();
            }
            else {
                # set redirect cookie
                $this->set_redirect_cookie ();
                # redirect
                if ($redirect)
                header("Location:".$url.create_link ("login"));
                die();
            }
        }
        else {
            return true;
        }
    }

    /**
     * Sets UI theme for user
     *
     * @method set_user_theme
     * @return void
     */
    private function set_user_theme () {
        // set defaukt theme if field is missing
        if(!isset($this->settings->theme)) {
            $this->settings->theme = "dark";
        }
        // set user
        if(is_object($this->user)) {
            // use default theme from general settings
            if(!isset($this->user->theme) || @$this->user->theme=="") {
                $this->user->ui_theme = $this->settings->theme;
            }
            else {
                $this->user->ui_theme = $this->user->theme;
            }
            // validate
            if(!in_array($this->user->ui_theme, $this->themes)) {
                $this->user->ui_theme = "white";
            }
        }
    }

    /**
     * Check if users timeout expired
     *     if yes set timeout flag
     *
     * @access private
     * @return void
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
        if($_SERVER['SCRIPT_URL']!="/login/" && $_SERVER['SCRIPT_URL']!="logout" && $_SERVER['SCRIPT_URL']!="?page=login" && $_SERVER['SCRIPT_URL']!="?page=logout" && $_SERVER['SCRIPT_URL']!="index.php?page=login" && $_SERVER['SCRIPT_URL']!="index.php?page=logout" && $_SERVER['SCRIPT_URL']!="/" && $_SERVER['SCRIPT_URL']!="%2f");
        setcookie("phpipamredirect", preg_replace('/^\/+/', '/', $_SERVER['REQUEST_URI']), time()+10, "/", null, null, true);
    }

    /**
     * Sets translation for logged in user
     *
     * @access private
     * @return void
     */
    private function set_ui_language () {
        if(strlen($_SESSION['ipamlanguage'])>0)     {
            putenv("LC_ALL=$_SESSION[ipamlanguage]");
            setlocale(LC_ALL, $_SESSION['ipamlanguage']);        // set language
            bindtextdomain("phpipam", "./functions/locale");    // Specify location of translation tables
            textdomain("phpipam");                                // Choose domain
        }
    }

    /**
     * Checks if system is in maintaneance mode and exits if it is
     *
     * @method check_maintaneance_mode
     * @param  bool    $is_popup (default: false)
     * @return void
     */
    public function check_maintaneance_mode ($is_popup = false) {
        if($this->settings->maintaneanceMode == "1" && $this->user->username!="Admin") {
            if($is_popup) {
                $this->Result->show("warning", "<i class='fa fa-info'></i> "._("System is running in maintenance mode")." !", true, true);
            }
            else {
                $this->Result->show("warning text-center nomargin", "<i class='fa fa-info'></i> "._("System is running in maintenance mode")." !", true);
            }
        }
    }

    /**
     * Sets maintaneance mode
     *
     * @method set_maintaneance_mode
     * @param  bool $on (default: false)
     */
    public function set_maintaneance_mode ($on = false) {
        # set mode status
        $maintaneance_mode = $on ? "1" : "0";
        # execute
        try { $this->Database->updateObject("settings", array("id"=>1, "maintaneanceMode"=>$maintaneance_mode), "id"); }
        catch (Exception $e) {}
    }

    /**
     * Migrate resolve_subnets from config.php to database
     * for versions older than 1.31
     *
     * @method migrate_resolve_subnets
     *
     * @return void
     */
    public function migrate_resolve_subnets () {
        // read config.php
        include( dirname(__FILE__).'/../../config.php' );
        // check for array and values
        if(isset($config['resolve_subnets'])) {
            if(is_array($config['resolve_subnets'])) {
                if (sizeof($config['resolve_subnets'])>0) {
                    foreach ($config['resolve_subnets'] as $subnetId) {
                        $update = array (
                                         "id"         => $subnetId,
                                         "resolveDNS" => 1
                                         );
                        // update
                        try {
                            $this->Database->updateObject("subnets", $update);
                        } catch (Exception $e) {}
                    }
                    // print that is can be deleted
                    $this->Result->show ("warning", '$config[resolve_subnets] '._('was migrated to database. It can be deleted from config.php'), false);
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
            $this->Result->show ("danger", _('Page not referred properly'), true);
        }
    }

    /**
     * fetches default language
     *
     * @access public
     * @return object
     */
    public function get_default_lang () {
        try { $lang = $this->Database->findObject("lang","l_id",$this->settings->defaultLang); }
        catch (Exception $e) { $this->debugging ? : $this->Result->show("danger", _("Database error: ").$e->getMessage()); }

        return $lang;
    }

    /**
     * Sets available authentication methods
     *
     *    Can be extended by reading set properties from set field options
     *
     * @access public
     * @return array
     */
    public function fetch_available_auth_method_types () {
		return array("AD", "LDAP", "NetIQ", "Radius", "SAML2");
	}










    /**
     * @favourite methods
     * ------------------------------
     */

    /**
     * Fetches details for users favourite subnets
     *
     * @access public
     * @return array|false
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
                // init
                $fsubnets = array();
                # fetch details for each subnet
                foreach($subnets as $id) {
                    $query = "select `su`.`id` as `subnetId`,`se`.`id` as `sectionId`, `subnet`, `mask`,`isFull`,`su`.`description`,`se`.`description` as `section`, `vlanId`, `isFolder`
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
     * @param mixed $action
     * @param mixed $subnetId
     * @return bool
     */
    public function edit_favourite($action, $subnetId) {
        # execute
        if($action=="remove")    { return $this->remove_favourite ($subnetId); }
        elseif($action=="add")   { return $this->add_favourite ($subnetId); }
        else                     { return false; }
    }

    /**
     * Remove subnet from user favourite subnets
     *
     * @access private
     * @param mixed $subnetId
     * @return bool
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
     * @return bool
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
     * @return bool
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
     *    > tries to fetch user details from database by username
     *    > sets authentication method and checks validity
     *    > authenticates
     *
     * @access public
     * @param string $username
     * @param string $password
     * @param bool $saml
     * @return void
     */
    public function authenticate ($username, $password, $saml = false) {
        if(($saml !== false ) && (defined('MAP_SAML_USER'))) {
            $username = SAML_USERNAME;
        }
        # first we need to check if username exists
        $this->fetch_user_details ($username);
        # set method type if set, otherwise presume local auth
        $this->authmethodid = strlen(@$this->user->authMethod)>0 ? $this->user->authMethod : 1;

        # get authentication method details
        $this->get_auth_method_type ();

        # authenticate based on name of auth method
        if(!method_exists($this, $this->authmethodtype))    {
            $this->Log->write ("User login", _('Error: Invalid authentication method'), 2 );
            $this->Result->show("danger", _("Error: Invalid authentication method"), true);
        }
        else {
            # set method name variable
            $authmethodtype = $this->authmethodtype;
            if($saml !== false) {
                $authmethodtype = 'auth_SAML2';
            }
            # is auth_SAML and $saml == false throw error
            if ($authmethodtype=="auth_SAML2" && $saml===false) {
                $this->Result->show("danger", "Please use <a href='".create_link('saml2')."'>login</a>!", true);
            }
            else {
                # authenticate
                $this->{$authmethodtype} ($username, $password);
            }
        }
    }

    /**
     * tries to fetch user datails from database by username if not already existing locally
     *
     * @access private
     * @param string $username
     * @param bool $force
     * @return void
     */
    private function fetch_user_details ($username, $force = false) {
        # only if not already active
        if(!is_object($this->user) || $force) {
            try { $user = $this->Database->findObject("users", "username", $username); }
            catch (Exception $e)     { $this->Result->show("danger", _("Error: ").$e->getMessage(), true);}

            # if not result return false
            $usert = (array) $user;

            # admin?
            if($user->role == "Administrator")    { $this->isadmin = true; }

            if(sizeof($usert)==0)    { $this->block_ip (); $this->Log->write ("User login", _('Invalid username'), 2, $username ); $this->Result->show("danger", _("Invalid username or password"), true);}
            else                     { $this->user = $user; }
        }
    }

    /**
     * Fetch all languages from database.
     *
     * @access public
     * @return array
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
     * @return string
     */
    private function fetch_lang_details () {
        // fetch from db
        try { $lang = $this->Database->findObject("lang", "l_id", $this->user->lang); }
        catch (Exception $e) {
            $this->Result->show("danger", _("Error: ").$e->getMessage(), true);
            return false;
        }
        // return code
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

            $this->Log->write( "User login", "Invalid username or password", 2, $username );

            # apache
            if (!empty($_SERVER['PHP_AUTH_USER']) && $this->api!==true) { $this->show_http_login(); }
            else                                                        { $this->Result->show("danger", _("Invalid username or password"), true); }
        }
    }

    /**
     * HTTP REMOTE_USER authentication, the user is already authenticated
     * by the web server so just create the session
     *
     * @access private
     * @param mixed $username
     * @param mixed $password
     * @return void
     */
    public function auth_http ($username, $password) {
        # save to session
        $this->write_session_parameters ();

        $this->Result->show("success", _("Login successful"));
        $this->Log->write( "User login", "User ".$this->user->real_name." logged in", 0, $username );

        # write last logintime
        $this->update_login_time ();

        # remove possible blocked IP
        $this->block_remove_entry ();
    }

    /**
     * Shows login prompt for apache logins
     *
     * @access private
     * @return void
     */
    private function show_http_login () {
        header('WWW-Authenticate: Basic realm="phpIPAM authentication"');
        header('HTTP/1.0 401 Unauthorized');
        echo 'Authentication failed';
        exit;
    }

    /**
     * Connect to a directory given our auth method settings
     *
     *Connect using adLDAP
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
            // Support the pre-1.2 auth settings as well as the current version
            // TODO: remove legacy support at some point
            if ($authparams['ldap_security'] == 'tls' || $authparams['use_tls'] == 1)         { $dirparams['use_tls'] = true; }
            elseif ($authparams['ldap_security'] == 'ssl' || $authparams['use_ssl'] == 1)     { $dirparams['use_ssl'] = true; }
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
            $this->Log->write("Directory connection error", "Failed to connect: " . $e->getMessage(), 2, null);
            $this->Result->show("danger", _("Error: ") . $e->getMessage(), true);
        }
        return $dirconn;
    }

    /**
     *    Authenticate against a directory
     *
     *    Authenticates users against a directory - AD or LDAP
     *    Using library > adLDAP - LDAP Authentication with PHP for Active Directory
     *    http://adldap.sourceforge.net
     *
     * @access private
     * @param array $authparams
     * @param string $username
     * @param string $password
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
                $this->Result->show("danger", _("Invalid username or password "), true);

            }
        } catch (adLDAPException $e) {
            $this->Log->write("Error", "Something went wrong during auth: " . $e->getMessage(), 2, $username);
            $this->Result->show("danger", _("Error: ") . $e->getMessage(), true);
        }
    }

    /**
     * AD (Active directory) authentication function
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
     *    LDAP authentication
     *    same as AD authentication, only set the LDAP flag to true
     *
     * @access private
     * @param mixed $username
     * @param mixed $password
     * @return void
     */
    private function auth_LDAP ($username, $password) {
        // parse settings for LDAP connection and store them to array
        $authparams = json_decode($this->authmethodparams, true);
        $this->ldap = true;                            //set ldap flag

        // set uid
        if (!empty($authparams['uid_attr'])) { $udn = $authparams['uid_attr'] . '=' . $username; }
        else                                 { $udn = 'uid=' . $username; }
        // set DN
        if (!empty($authparams['users_base_dn'])) { $udn = $udn . "," . $authparams['users_base_dn']; }
        else                                      { $udn = $udn . "," . $authparams['base_dn']; }
        // authenticate
        $this->directory_authenticate($authparams, $udn, $password);
    }

    /**
     * NetIQ authentication
     * same as AD authentication, only add cn= before username
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
     * SAML2 auth
     *
     * @access private
     * @param mixed $username
     * @param mixed $password (default: null)
     * @return void
     */
    private function auth_SAML2 ($username, $password = null) {
        # save to session
        $this->write_session_parameters ();

        $this->Log->write( "SAML2 login", "User ".$this->user->real_name." logged in via SAML2", 0, $username );
        $this->Result->show("success", _("SAML2 login successful"));

        # write last logintime
        $this->update_login_time ();
        # remove possible blocked IP
        $this->block_remove_entry ();
    }










    /**
     *    @crypt functions
     *    ------------------------------
     */


    /**
     *    function to crypt user pass, randomly generates salt. Use sha256 if possible, otherwise Blowfish or md5 as fallback
     *
     *        types:
     *            CRYPT_MD5 == 1           (Salt starting with $1$, 12 characters )
     *            CRYPT_BLOWFISH == 1        (Salt starting with $2a$. The two digit cost parameter: 09. 22 characters )
     *            CRYPT_SHA256 == 1        (Salt starting with $5$rounds=5000$, 16 character salt.)
     *            CRYPT_SHA512 == 1        (Salt starting with $6$rounds=5000$, 16 character salt.)
     *
     * @access public
     * @param mixed $input
     * @return string
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
     *    this function will detect highest crypt type to use for system
     *
     * @access public
     * @return string
     */
    private function detect_crypt_type () {
        if(CRYPT_SHA512 == 1)        { return '$6$rounds=3000$'; }
        elseif(CRYPT_SHA256 == 1)    { return '$5$rounds=3000$'; }
        elseif(CRYPT_BLOWFISH == 1)  { return '$2y$'.str_pad(rand(4,31),2,0, STR_PAD_LEFT).'$'; }
        elseif(CRYPT_MD5 == 1)       { return '$5$rounds=3000$'; }
        else                         { $this->Result->show("danger", _("No crypt types supported"), true); }
    }

    /**
     * Returns crypt type used to encrypt password
     *
     * @access public
     * @return string
     */
    public function return_crypt_type () {
        if(CRYPT_SHA512 == 1)        { return 'CRYPT_SHA512'; }
        elseif(CRYPT_SHA256 == 1)    { return 'CRYPT_SHA256'; }
        elseif(CRYPT_BLOWFISH == 1)  { return 'CRYPT_BLOWFISH'; }
        elseif(CRYPT_MD5 == 1)       { return 'CRYPT_MD5'; }
        else                         { return "No crypt types supported"; }
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
     *    @updating user methods
     *    ------------------------------
     */

    /**
     * User self update method
     *
     * @access public
     * @param array|object $post //posted user details
     * @return bool
     */
    public function self_update($post) {
        # remove theme
        if($post['theme'] == "default") { $post['theme'] = ""; }
        # set items to update
        $items  = array("real_name"        => escape_input(strip_tags($post['real_name'])),
                        "mailNotify"       => $post['mailNotify'] == "Yes" ? "Yes" : "No",
                        "mailChangelog"    => $post['mailChangelog'] == "Yes" ? "Yes" : "No",
                        "email"            => $this->validate_email($post['email']) ? escape_input($post['email']) : '',
                        "lang"             => escape_input(strip_tags($post['lang'])),
                        "id"               => $this->user->id,
                        //display
                        "compressOverride" => escape_input(strip_tags($post['compressOverride'])),
                        "hideFreeRange"    => $this->verify_checkbox(@$post['hideFreeRange']),
                        "menuType"         => $this->verify_checkbox(@$post['menuType']),
                        "menuCompact"      => $this->verify_checkbox(@$post['menuCompact']),
                        "theme"            => $post['theme']
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
     * @return bool
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
     * @return bool
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
     *    @blocking IP functions
     *    ------------------------------
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
     * @return int|false
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
     * @return bool
     */
    private function block_ip () {
        # validate IP
        if(!filter_var($this->ip, FILTER_VALIDATE_IP))    { return false; }

        # first check if already in
        if($this->block_check_ip ())         { $this->block_update_count(); }
        # if not in add first entry
        else                                 { $this->block_add_entry(); }
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
        if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) { $this->ip = @$_SERVER['HTTP_X_FORWARDED_FOR']; }
        else                                        { $this->ip = @$_SERVER['REMOTE_ADDR']; }
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



	/* @users and groups -------------------- */

    /**
     * From json {"2":"2","3":"1"}, get user list + perm
     *
     * @method get_user_permissions_from_json
     * @param  json     $json
     * @return array
     */
    public function get_user_permissions_from_json ($json) {
        $groups = array();
        foreach((array) json_decode($json, true) as $group_id => $perm) {
            $group_details = $this->groups_parse (array($group_id));

            $tmp = array();
            $tmp['group_id'] = $group_id;
            $tmp['permission'] = $perm;
            $tmp['name'] = $group_details[$group_id]['g_name'];
            $tmp['desc'] = $group_details[$group_id]['g_desc'];
            $tmp['members'] = $group_details[$group_id]['members'];

            $groups[] = $tmp;
        }
        return $groups;
    }

	/**
	 * Parse user groups
	 *
	 *	input:  array of group ids
	 *	output: array of groups ( "id"=>array($group) )
	 *
     * @method groups_parse
	 * @param array  $group_ids
	 * @return array
	 */
	private function groups_parse ($group_ids) {
		if(sizeof($group_ids)>0) {
	    	foreach($group_ids as $g_id) {
	    		// group details
	    		$group = $this->fetch_object ("userGroups", "g_id", $g_id);
	    		$out[$group->g_id] = (array) $group;
	    		$out[$group->g_id]['members'] = $this->fetch_multiple_objects("users", "groups", "%\"$g_id\"%", "real_name", true, true, array("username"));
	    	}
	    }
	    # return array of groups
	    return isset($out) ? $out : array();
	}
}
