<?php

/**
 *	phpIPAM Section class
 */

class Devices extends Common_functions {

	/**
	 * (array of objects) to store sections, section ID is array index
	 *
	 * @var mixed
	 * @access public
	 */
	public $sections;

	/**
	 * id of last insert
	 *
	 * (default value: null)
	 *
	 * @var mixed
	 * @access public
	 */
	public $lastInsertId = null;

	/**
	 * (object) for User profile
	 *
	 * (default value: null)
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $user = null;

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
	 * @param Database_PDO $database
	 */
	public function __construct (Database_PDO $database) {
		# Save database object
		$this->Database = $database;
		# initialize Result
		$this->Result = new Result ();
		# Log object
		$this->Log = new Logging ($this->Database);
	}


}
?>
