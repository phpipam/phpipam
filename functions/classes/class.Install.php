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
		# Set webhost
		$webhost = !empty($this->db['webhost']) ? $this->db['webhost'] : 'localhost';
		# set query
		$query = 'grant ALL on `'. $this->db['name'] .'`.* to \''. $this->db['user'] .'\'@\''. $webhost .'\' identified by "'. $this->db['pass'] .'";';
		# execute
		try { $this->Database_root->runQuery($query); }
		catch (Exception $e) {	$this->Result->show("danger", $e->getMessage(), true);}
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
	 * Sets DB parmaeters
	 *
	 * @access private
	 * @return void
	 */
	private function set_db_params () {
		$this->db = Config::get('db');
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
		// reset dbversion if not set
		if (!isset($this->settings->dbversion)) {
			$this->settings->dbversion = 0;
		}
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
		if($this->old_version == VERSION.DBVERSION) { $this->Result->show("danger", "Database already at latest version", true); }
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
					$this->Database->runQuery($query);
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
			$this->Log->write( "Database upgrade", $e->getMessage()."<br>query: ".$query, 2 );
			# fail
			print "<h3>Upgrade failed !</h3><hr style='margin:30px;'>";
			$this->Result->show("danger", $e->getMessage()."<hr>Failed query: <pre>".$query."</pre>", false);

			# print failure
			$this->Result->show("danger", _("Failed to upgrade database!"), false);
			print "<div class='text-right'><a class='btn btn-sm btn-default' href='".create_link('administration', "verify-database")."'>Go to administration and fix</a></div><br><hr><br>";

			if(sizeof($queries_ok)>0)
			$this->Result->show("success", "Succesfull queries: <pre>".implode("<br>", $queries_ok)."</pre>", false);
			if(sizeof($queries)>0)
			$this->Result->show("warning", "Not executed queries: <pre>".implode("<br>", $queries)."</pre>", false);

			return false;
		}

		# all good, print it
		usleep(500000);
		$this->Log = new Logging ($this->Database);
		$this->Log->write( "Database upgrade", "Database upgraded from version ".$this->settings->version.".r".$this->settings->dbversion." to version ".VERSION.".r".DBVERSION, 1 );
		return true;
	}
}