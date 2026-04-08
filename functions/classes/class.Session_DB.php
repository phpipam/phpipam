<?php

/**
 *
 * php php_sessions class wrapper to work with session cookies in database
 *
 */
class Session_DB {

	/**
	 * Database
	 *
	 * @var mixed
	 */
	protected $Database;

	/**
	 * Result
	 *
	 * @var Result
	 * @access public
	 */
	public $Result;

	/**
	 * Constructor
	 *
	 * @method __construct
	 * @param  Database_PDO $database
	 */
	public function __construct (Database_PDO $database) {
		# initialize database object
		$this->Database = $database;
		// result
		$this->Result = new Result ();
		// set handler
		$this->set_handler ();
	}

	/**
	 * Register this class as session handler
	 * @method set_handler
	 */
	private function set_handler () {
		// Set database as handler if requested
		session_set_save_handler(
			array($this, "_open"),
			array($this, "_close"),
			array($this, "_read"),
			array($this, "_write"),
			array($this, "_destroy"),
			array($this, "_gc")
		);
		// start
		session_start ();
	}

	/**
	 * Open connection
	 * @method _open
	 * @return bool
	 */
	public function _open () {
		try {
			$this->Database->connect();
			return true;
		}
		catch (Exception $e) {
			$this->Result->show("danger", $e->getMessage(), false);
			return false;
		}
	}

	/**
	 * Close connection - not needed
	 * @method _close
	 * @return void
	 */
	public function _close () {
		return true;
	}

	/**
	 * Check database for session data
	 * @method _read
	 * @param  string $id
	 * @return string
	 */
	public function _read ($id) {
		// check database for cookie
		try {
			// Note: Database->getObject() does not support non-numeric id's. Use findObject().
			$session = $this->Database->findObject ('php_sessions', 'id', $id);
			// check
			if (!is_object($session) || empty($session->data))
				return "";

			return $session->data;
		}
		catch (Exception $e) {
			$this->Result->show("danger", $e->getMessage(), false);
			return "";
		}
	}

	/**
	 * Save session data
	 *
	 * @method _write
	 * @param  string $id
	 * @param  string $data
	 * @return bool
	 */
	public function _write ($id, $data) {
		// we need some data, otherwise don't save session
		if(is_blank($data)) {
			//return true;
		}
		// set insert / update values
		$values = [
					"id" 		=> $id,
					"access"    => time(),
					"data"      => $data,
					"remote_ip"	=> $_SERVER['REMOTE_ADDR']
				  ];
		// insert
		try {
			$this->Database->insertObject ("php_sessions", $values, false, true, false);
			return true;
		}
		catch (Exception $e) {
			$this->Result->show("danger", $e->getMessage(), false);
			return false;
		}
	}

	/**
	 * Destroy session
	 *
	 * @method _destroy
	 * @param  string $id
	 * @return bool
	 */
	public function _destroy ($id) {
		try {
			$this->Database->deleteObject ("php_sessions", $id);
			return true;
		}
		catch (Exception $e) {
			$this->Result->show("danger", $e->getMessage(), false);
			return false;
		}
	}

	/**
	 * Garbage collection functions
	 * @method _gc
	 * @param  int $max
	 * @return bool
	 */
	public function _gc ($max) {
		try {
			$this->Database->runQuery ("DELETE FROM php_sessions WHERE `access` < ?", [time() - $max]);
			return true;
		}
		catch (Exception $e) {
			return false;
		}
	}
}
