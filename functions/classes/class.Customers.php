<?php

/**
 *	phpIPAM Customers class
 */

class Customers extends Common_functions {

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
