<?php

/**
 *	phpIPAM API class to work with tools
 *
 */
class Tools_controller extends Common_functions {

	/* public variables */
	public $result_type;				// sets output - JSON or XML
	public $result;						// result

	/* object holders */
	private $Database;
	private $Exceptions;
	protected $Tools;

	/**
	 * __construct function
	 *
	 * @access public
	 * @param class $Database
	 * @param class $Tools
	 * @param mixed $params		// post/get values
	 * @return void
	 */
	public function __construct($Database, $Tools, $params, $Exceptions) {
		$this->Database = $Database;
		$this->Exceptions = $Exceptions;
		$this->Tools 	= $Tools;
		$this->_params 	= $params;
	}





	/**
	 * returns general options
	 *
	 * @access public
	 * @return void
	 */
	public function OPTIONS () {

	}






	/**
	 * Creates new object
	 *
	 * @access public
	 * @return void
	 */
	public function POST () {

	}





	/**
	 * Reads object
	 *
	 * @access public
	 * @return void
	 */
	public function GET () {

	}





	/**
	 * Update object
	 *
	 * @access public
	 * @return void
	 */
	public function PATCH () {

	}

	/**
	 * Alias function for PATCH
	 *
	 * @access public
	 * @return void
	 */
	public function PUT () {
		return $this->PATCH ();
	}





	/**
	 * Deletes existing object
	 *
	 * @access public
	 * @return void
	 */
	public function DELETE () {

	}
}

?>