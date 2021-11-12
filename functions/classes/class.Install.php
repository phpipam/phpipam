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
	 * Database_root - for initial installation
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $Database_root;




	/**
	 * __construct function.
	 *
	 * @access public
	 * @param Database_PDO $Database
	 */
	public function __construct (Database_PDO $Database) {
		parent::__construct();

		# initialize Result
		$this->Result = new Result ();
		# initialize object
		$this->Database = $Database;
		# set db
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
	 * @param bool $migrate (default: false)
	 * @return void
	 */
	public function install_database ($rootuser, $rootpass, $drop_database = false, $create_database = false, $create_grants = false, $migrate = false) {

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
		if($this->install_database_execute ($migrate) !== false) {
		    # return true, if some errors occured script already died! */
			sleep(1);
			$this->Log = new Logging ($this->Database);
			$this->Log->write( _("Database installation"), _("Database installed successfully.")._(" Version ").VERSION.".".REVISION._(" installed"), 1 );
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
		$esc_user = addcslashes($this->db['user'],"'");
		$esc_pass = addcslashes($this->db['pass'],"'");
		$db_name  = $this->db['name'];
		$webhost  = is_string($this->db['webhost']) && strlen($this->db['webhost']) > 0 ? addcslashes($this->db['webhost'],"'") : 'localhost';

		try {
			# Check if user exists;
			$result = $this->Database_root->getObjectQuery("SELECT EXISTS(SELECT 1 FROM mysql.user WHERE user = '$esc_user' AND host = '$webhost') AS user_exists;");

			# create user if not exists and set permissions
			if ($result->user_exists == 0) {
				$this->Database_root->runQuery("CREATE USER '$esc_user'@'$webhost' IDENTIFIED BY '$esc_pass';");
			}
			$this->Database_root->runQuery("GRANT ALL ON `$db_name`.* TO '$esc_user'@'$webhost';");
			$this->Database_root->runQuery("FLUSH PRIVILEGES;");

		} catch (Exception $e) { $this->Result->show("danger", $e->getMessage(), true); }
	}

	/**
	 * Execute files installation
	 *
	 * @access private
	 * @param $migrate (default: false)
	 * @return void
	 */
	private function install_database_execute ($migrate = false) {
	    # import SCHEMA file queries
	    if($migrate) {
		    $query  = file_get_contents("../../db/MIGRATE.sql");
		}
		else {
		    $query  = file_get_contents("../../db/SCHEMA.sql");
		}

	    # formulate queries
	    $queries = array_filter(explode(";\n", $query));

	    # append version
		$queries[] = "UPDATE `settings` SET `version` = '".VERSION."'";
		$queries[] = "UPDATE `settings` SET `dbversion` = '".DBVERSION."'";
		$queries[] = "UPDATE `settings` SET `dbverified` = 0";

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
						$this->Result->show("danger", _("Cannot drop database: ").$e->getMessage(), true);
					}
					//print error
					$this->Result->show("danger", _("Cannot install sql SCHEMA file").": ".$e->getMessage()."<br>"._("query that failed").": <pre>$q</pre>", false);
					$this->Result->show("info", _("Database dropped"), false);

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
	 * Sets DB parmaeters
	 *
	 * @access private
	 * @return void
	 */
	private function set_db_params () {
		$this->db = Config::ValueOf('db');
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
}






/**
 *
 * Upgrade class
 *
 */
class Upgrade extends Install {

	/**
	 * upgrade queries with comments
	 * @var array
	 */
	private $queries = [];

	/**
	 * Old version
	 * @var string
	 */
	private $old_version = "1.2";


	/**
	 * __construct function.
	 *
	 * @method __construct
	 * @param  Database_PDO $Database
	 */
	public function __construct (Database_PDO $Database) {
		# initialize objects
		$this->Database = $Database;
		$this->Result   = new Result ();
		// check DB connection
		try { $this->Database->connect(); }
		catch ( Exception $e ) {}
		// get old version
		$this->get_old_version ();
		// load queries
		$this->load_all_queries ();
	}

	/**
	 * Get old version from database
	 *
	 * @method get_old_version
	 * @return void
	 */
	private function get_old_version () {
		// fetch settings from database
		$this->get_settings ();
		// save version
		$this->old_version = $this->settings->version.".".$this->settings->dbversion;
	}

	/**
	 * Load all queries from upgrade list and add them to array of queries
	 *
	 * @method load_all_queries
	 * @return void
	 */
	private function load_all_queries () {
		// include upgrade files
		require (dirname(__FILE__)."/../upgrade_queries.php");
		// add queries
		foreach ($upgrade_queries as $version=>$query_arr) {
			foreach ($query_arr as $query) {
				// save query
				$this->reqister_query ($version, $query);
			}
		}
	}

	/**
	 * Add new query to upgrade query list
	 *
	 * @method reqister_query
	 * @param  string $version
	 * @param  string $query
	 * @return void
	 */
	private function reqister_query ($version, $query) {
		// check if version is higher than old version, otherwise skip query
		if ($this->cmp_version_strings($version, $this->old_version) > 0) {
			// break
			if (strpos($query, "--")===0) {
				$this->queries[] = "\n";
			}
			// save query
			$this->queries[] = trim($this->process_procedures($query));
		}
	}

	/**
	 * Returns all upgrade queries to be executed
	 *
	 * @method get_queries
	 * @return array
	 */
	public function get_queries () {
		return $this->queries;
	}

	/**
	 * For PDO execution we cannot use delimiters so we need to remove them
	 * @method process_procedures
	 * @param  string $query
	 * @return string
	 */
	private function process_procedures ($query) {
		$query = str_replace("DELIMITER $$", "", $query);		// remove DELIMITER $$ start statement
		$query = str_replace("DELIMITER $", "", $query);		// remove DELIMITER $  end statement
		$query = str_replace("END $$", "END;", $query);		// Replace END $$ with END;
		// result
		return $query;
	}



	/**
	 * Execute upgrade
	 * ---------------
	 */

	/**
	 * Upgrade database checks and executes.
	 *
	 * @access public
	 * @return void
	 */
	public function upgrade_database () {
		if($this->old_version == VERSION.DBVERSION) { $this->Result->show("danger", _("Database already at latest version"), true); }
		else {
			# check db connection
			if($this->check_db_connection(false)===false)  	{ $this->Result->show("danger", _("Cannot connect to database"), true); }
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
		$queries = $this->get_queries ();
		// create default arrays
		$queries_ok = array();			// succesfull queries

		// execute
		try {
			# Begin transaction
			$this->Database->beginTransaction();

			# execute all queries
			foreach($queries as $k=>$query) {
				// execute
				if(strpos($query, "--")!==0 && strlen(trim($query))>0) {
					$ignore_on_failure = (strpos($query, '-- IGNORE_ON_FAILURE')!== false);

					if ($ignore_on_failure) $this->Database->setErrMode(\PDO::ERRMODE_SILENT);
					$this->Database->runQuery($query);
					if ($ignore_on_failure) $this->Database->setErrMode(\PDO::ERRMODE_EXCEPTION);
				}
				// save ok
				$queries_ok[] = $query;
				// remove old
				unset($queries[$k]);
			}

			$this->Database->runQuery("UPDATE `settings` SET `version` = ?", VERSION);
			$this->Database->runQuery("UPDATE `settings` SET `dbversion` = ?", DBVERSION);
			$this->Database->runQuery("UPDATE `settings` SET `dbverified` = ?", 0);

			# All good, commit changes
			$this->Database->commit();
		}
		catch (Exception $e) {
			# Something went wrong, revert all upgrade changes
			$this->Database->rollBack();
			// write log
			$this->Log = new Logging ($this->Database);
			$this->Log->write( _("Database upgrade"), $e->getMessage()."<br>"._("query: ").$query, 2 );
			# fail
			print "<h3>"._("Upgrade failed")." !</h3><hr style='margin:30px;'>";
			$this->Result->show("danger", $e->getMessage()."<hr>"._("Failed query").": <pre>".$query."</pre>", false);

			# print failure
			$this->Result->show("danger", _("Failed to upgrade database!"), false);
			print "<div class='text-right'><a class='btn btn-sm btn-default' href='".create_link('administration', "verify-database")."'>"._("Go to administration and fix")."</a></div><br><hr><br>";

			if(sizeof($queries_ok)>0)
			$this->Result->show("success", _("Succesfull queries").": <pre>".implode("<br>", $queries_ok)."</pre>", false);
			if(sizeof($queries)>0)
			$this->Result->show("warning", _("Not executed queries").": <pre>".implode("<br>", $queries)."</pre>", false);

			return false;
		}

		# all good, print it
		usleep(500000);
		$this->Log = new Logging ($this->Database);
		$this->Log->write( _("Database upgrade"), _("Database upgraded from version ").$this->settings->version._(".r").$this->settings->dbversion._(" to version ").VERSION._(".r").DBVERSION, 1 );
		return true;
	}
}
