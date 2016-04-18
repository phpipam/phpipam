<?php

/**
 *	phpIPAM Install class
 */

class Install extends Common_functions {


	/**
	 * to store DB exceptions
	 *
	 * @var mixed
	 * @access public
	 */
	public $exception;

	/**
	 * Database parameters
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $db;

	/**
	 * debugging flag
	 *
	 * (default value: false)
	 *
	 * @var bool
	 * @access public
	 */
	public $debugging = false;

	/**
	 * Result
	 *
	 * @var mixed
	 * @access public
	 */
	public $Result;

	/**
	 * Database
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $Database;

	/**
	 * Database_root - for initial installation
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $Database_root;

	/**
	 * Log
	 *
	 * @var mixed
	 * @access public
	 */
	public $Log;





	/**
	 * __construct function.
	 *
	 * @access public
	 * @param Database_PDO $Database
	 */
	public function __construct (Database_PDO $Database) {
		# initialize Result
		$this->Result = new Result ();
		# initialize object
		$this->Database = $Database;
		# set debugging
		$this->set_debugging ();
		# set debugging
		$this->set_db_params ();
		# Log object
		try { $this->Database->connect(); }
		catch ( Exception $e ) {}
	}









	/**
	 * @install methods
	 * ------------------------------
	 */

	/**
	 * Install database files
	 *
	 * @access public
	 * @param mixed $rootuser
	 * @param mixed $rootpass
	 * @param bool $drop_database (default: false)
	 * @param bool $create_database (default: false)
	 * @param bool $create_grants (default: false)
	 * @return void
	 */
	public function install_database ($rootuser, $rootpass, $drop_database = false, $create_database = false, $create_grants = false) {

		# open new connection
		$this->Database_root = new Database_PDO ($rootuser, $rootpass);

		# set install flag to make sure DB is not trying to be selected via DSN
		$this->Database_root->install = true;

		# drop database if requested
		if($drop_database===true) 	{ $this->drop_database(); }

		# create database if requested
		if($create_database===true) { $this->create_database(); }

		# set permissions!
		if($create_grants===true) 	{ $this->create_grants(); }

	    # reset connection, reset install flag and connect again
		$this->Database_root->resetConn();

		# install database
		if($this->install_database_execute () !== false) {
		    # return true, if some errors occured script already died! */
			sleep(1);
			$this->Log = new Logging ($this->Database);
			$this->Log->write( "Database installation", "Database installed successfully. Version ".VERSION.".".REVISION." installed", 1 );
			return true;
		}
	}

	/**
	 * Drop existing database
	 *
	 * @access private
	 * @return void
	 */
	private function drop_database () {
	 	# set query
	    $query = "drop database if exists `". $this->db['name'] ."`;";
		# execute
		try { $this->Database_root->runQuery($query); }
		catch (Exception $e) {	$this->Result->show("danger", $e->getMessage(), true);}
	}

	/**
	 * Create database
	 *
	 * @access private
	 * @return void
	 */
	private function create_database () {
	 	# set query
	    $query = "create database `". $this->db['name'] ."`;";
		# execute
		try { $this->Database_root->runQuery($query); }
		catch (Exception $e) {	$this->Result->show("danger", $e->getMessage(), true);}
	}

	/**
	 * Create user grants
	 *
	 * @access private
	 * @return void
	 */
	private function create_grants () {
	 	# set query
	    $query = 'grant ALL on `'. $this->db['name'] .'`.* to '. $this->db['user'] .'@localhost identified by "'. $this->db['pass'] .'";';
		# execute
		try { $this->Database_root->runQuery($query); }
		catch (Exception $e) {	$this->Result->show("danger", $e->getMessage(), true);}
	}

	/**
	 * Execute files installation
	 *
	 * @access private
	 * @return void
	 */
	private function install_database_execute () {
	    # import SCHEMA file queries
	    $query  = file_get_contents("../../db/SCHEMA.sql");

	    # formulate queries
	    $queries = array_filter(explode(";\n", $query));

	    # execute
	    foreach($queries as $q) {
		    //length check
		    if (strlen($q)>0) {
				try { $this->Database_root->runQuery($q.";"); }
				catch (Exception $e) {
					//unlock tables
					try { $this->Database_root->runQuery("UNLOCK TABLES;"); }
					catch (Exception $e) {}
					//drop database
					try { $this->Database_root->runQuery("drop database if exists `". $this->db['name'] ."`;"); }
					catch (Exception $e) {
						$this->Result->show("danger", 'Cannot drop database: '.$e->getMessage(), true);
					}
					//print error
					$this->Result->show("danger", "Cannot install sql SCHEMA file: ".$e->getMessage()."<br>query that failed: <pre>$q</pre>", false);
					$this->Result->show("info", "Database dropped", false);

					return false;
				}
			}
	    }
	}










	/**
	 * @check methods
	 * ------------------------------
	 */

	/**
	 * Tries to connect to database
	 *
	 * @access public
	 * @param bool $redirect
	 * @return void
	 */
	public function check_db_connection ($redirect = false) {
		# try to connect
		try { $res = $this->Database->connect(); }
		catch (Exception $e) 	{
			$this->exception = $e->getMessage();
			# redirect ?
			if($redirect == true)  	{ $this->redirect_to_install (); }
			else					{ return false; }
		}
		# ok
		return true;
	}

	/**
	 * Checks if table exists
	 *
	 * @access public
	 * @param mixed $table
	 * @return void
	 */
	public function check_table ($table, $redirect = false) {
		# set query
		$query = "SELECT COUNT(*) AS `cnt` FROM information_schema.tables WHERE table_schema = '".$this->db['name']."' AND table_name = '$table';";
		# try to fetch count
		try { $table = $this->Database->getObjectQuery($query); }
		catch (Exception $e) 	{ if($redirect === true) $this->redirect_to_install ();	else return false; }
		# redirect if it is not existing
		if($table->cnt!=1) 	 	{ if($redirect === true) $this->redirect_to_install ();	else return false; }
		# ok
		return true;
	}

	/**
	 * This function redirects to install page
	 *
	 * @access private
	 * @return void
	 */
	private function redirect_to_install () {
		# redirect to install
		header("Location: ".create_link("install"));
	}

	/**
	 * sets debugging if set in config.php file
	 *
	 * @access private
	 * @return void
	 */
	public function set_debugging () {
		require( dirname(__FILE__) . '/../../config.php' );
		if($debugging==true) { $this->debugging = true; }
	}

	/**
	 * Sets DB parmaeters
	 *
	 * @access private
	 * @return void
	 */
	private function set_db_params () {
		require( dirname(__FILE__) . '/../../config.php' );
		$this->db = $db;
	}









	/**
	 * @postinstallation functions
	 * ------------------------------
	 */

	/**
	 * Post installation settings update.
	 *
	 * @access public
	 * @param mixed $adminpass
	 * @param mixed $siteTitle
	 * @param mixed $siteURL
	 * @return void
	 */
	function postauth_update($adminpass, $siteTitle, $siteURL) {
		# update Admin pass
		$this->postauth_update_admin_pass ($adminpass);
		# update settings
		$this->postauth_update_settings ($siteTitle, $siteURL);
		# ok
		return true;
	}

	/**
	 * Updates admin password after installation
	 *
	 * @access public
	 * @param mixed $adminpass
	 * @return void
	 */
	public function postauth_update_admin_pass ($adminpass) {
		try { $this->Database->updateObject("users", array("password"=>$adminpass, "passChange"=>"No","username"=>"Admin"), "username"); }
		catch (Exception $e) { $this->Result->show("danger", $e->getMessage(), false); }
		return true;
	}

	/**
	 * Updates settings after installation
	 *
	 * @access private
	 * @param mixed $siteTitle
	 * @param mixed $siteURL
	 * @return void
	 */
	private function postauth_update_settings ($siteTitle, $siteURL) {
		try { $this->Database->updateObject("settings", array("siteTitle"=>$siteTitle, "siteURL"=>$siteURL,"id"=>1), "id"); }
		catch (Exception $e) { $this->Result->show("danger", $e->getMessage(), false); }
		return true;
	}










	/**
	 * @upgrade database
	 * -----------------
	 */

	/**
	 * Upgrade database checks and executes.
	 *
	 * @access public
	 * @return void
	 */
	public function upgrade_database () {
		# first check version
		$this->get_settings ();

		if($this->settings->version == VERSION)				{ $this->Result->show("danger", "Database already at latest version", true); }
		else {
			# check db connection
			if($this->check_db_connection(false)===false)  	{ $this->Result->show("danger", "Cannot connect to database", true); }
			# execute
			else {
				return $this->upgrade_database_execute ();
			}
		}
	}

	/**
	 * Execute database upgrade.
	 *
	 * @access private
	 * @return void
	 */
	private function upgrade_database_execute () {
		# set queries
		$subversion_queries = $this->get_upgrade_queries ();
		// create default arrays
		$queries = array();
		// succesfull queries:
		$queries_ok = array();

		// replace CRLF
		$subversion_queries = str_replace("\r\n", "\n", $subversion_queries);
		$queries = array_filter(explode(";\n", $subversion_queries));

	    # execute all queries
	    foreach($queries as $query) {
    	    if (strlen($query)>5) {
    			try { $this->Database->runQuery($query); }
    			catch (Exception $e) {
    				$this->Log = new Logging ($this->Database);
    				# write log
    				$this->Log->write( "Database upgrade", $e->getMessage()."<br>query: ".$query, 2 );
    				# fail
    				print "<h3>Upgrade failed !</h3><hr style='margin:30px;'>";
    				$this->Result->show("danger", $e->getMessage()."<hr>Failed query: <pre>".$query.";</pre>", false);
    				$this->Result->show("success", "Succesfull queries: <pre>".implode(";", $queries_ok).";</pre>", false);
    				# revert version
    				//try { $this->Database->runQuery('update `settings` set `version` = ?', array($this->settings->version)); }
    				//catch (Exception $e) { var_dump($e); }
    				// false
    				return false;
    			}
    			// save ok
    			$queries_ok[] = $query;
			}
	    }


		# all good, print it
		sleep(1);
		$this->Log = new Logging ($this->Database);
		$this->Log->write( "Database upgrade", "Database upgraded from version ".$this->settings->version." to version ".VERSION.".".REVISION, 1 );
		return true;
	}

	/**
	 * Fetch all upgrade queries from DB files
	 *
	 * @access public
	 * @return void
	 */
	public function get_upgrade_queries () {
		// save all queries fro UPDATE.sql file
		$queries = str_replace("\r\n", "\n", (file_get_contents( dirname(__FILE__) . '/../../db/UPDATE.sql')));

		// fetch settings if not present - for manual instructions
		if (!isset($this->settings->version)) { $this->get_settings (); }

        // explode and loop to get next version from current
        $delimiter = false;
        foreach (explode("/* VERSION ", $queries) as $k=>$q) {
            $q_version = str_replace(" */", "", array_shift(explode("\n", $q)));

            // if delimiter was found in previous loop
            if ($delimiter!==false) {
                $delimiter = $q_version;
                break;
            }
            // if match with current set pointer to next item - delimiter
            if ($q_version==$this->settings->version) {
                $delimiter = true;
            };
        }

        // remove older queries before this version
        $old_queries = explode("/* VERSION $delimiter */", $queries);
        $old_queries = trim($old_queries[1]);

		# return
		return $old_queries;
	}
}