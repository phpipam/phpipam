<?php

/**
 *	phpIPAM API class to work with folders
 *
 *	just an alias for subnets
 */
class Folders_controller {

	/**
	 * __construct function
	 *
	 * @access public
	 * @param class $Database
	 * @param class $Tools
	 * @param mixed $params		// post/get values
	 * @return void
	 */
	public function __construct($Database, $Tools, $params) {
		$this->Database = $Database;
		$this->Tools 	= $Tools;
		$this->_params 	= $params;
		// include
		include("Subnets.php");
		// subnets
		$this->Subnets_controller = new Subnets_controller ($Database, $Tools, $params);
	}





	/**
	 * Creates new folder
	 *
	 * @access public
	 * @return void
	 */
	public function add () {
		return $this->Subnets_controller->add ();
	}

	/**
	 * Alias for add
	 *
	 * @access public
	 * @return void
	 */
	public function create () {
		return $this->add();
	}





	/**
	 * Read folder functions
	 *
	 * @access public
	 * @return void
	 */
	public function read () {
		return $this->Subnets_controller->read ();
	}

	/**
	 * Alias for read function
	 *
	 * @access public
	 * @return void
	 */
	public function fetch () {
		return $this->read ();
	}





	/**
	 * Updates existing subnet
	 *
	 * @access public
	 * @return void
	 */
	public function edit() {
		return $this->Subnets_controller->edit ();
	}

	/**
	 * Alias function for edit
	 *
	 * @access public
	 * @return void
	 */
	public function update() {
		return $this->edit();
	}





	/**
	 * Deletes existing subnet along with and addresses
	 *
	 * @access public
	 * @return void
	 */
	public function delete() {
		return $this->Subnets_controller->delete ();
	}
}

?>