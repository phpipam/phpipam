<?php

/**
 *	phpIPAM API class to work with folders
 *
 *	just an alias for subnets
 *
 */
class Folders_controller extends Common_api_functions {

	/**
	 * __construct function
	 *
	 * @access public
	 * @param class $Database
	 * @param class $Tools
	 * @param mixed $params		// post/get values
	 * @param mixed $Response
	 */
	public function __construct($Database, $Tools, $params, $Response) {
		// include
		require("Subnets.php");
		// subnets
		$this->Subnets_controller = new Subnets_controller ($Database, $Tools, $params, $Response);
	}




	/**
	 * Options
	 *
	 * @access public
	 * @return void
	 */
	public function OPTIONS () {
		return $this->Subnets_controller->OPTIONS ();
	}

	/**
	 * HEAD, no response
	 *
	 * @access public
	 * @return void
	 */
	public function HEAD () {
		return $this->Subnets_controller->GET ();
	}


	/**
	 * Creates new folder
	 *
	 * @access public
	 * @return void
	 */
	public function POST () {
		return $this->Subnets_controller->POST ();
	}


	/**
	 * Read folder functions
	 *
	 * @access public
	 * @return void
	 */
	public function GET () {
		return $this->Subnets_controller->GET ();
	}


	/**
	 * Updates existing subnet
	 *
	 * @access public
	 * @return void
	 */
	public function PATCH () {
		return $this->Subnets_controller->PATCH ();
	}


	/**
	 * Deletes existing subnet along with and addresses
	 *
	 * @access public
	 * @return void
	 */
	public function DELETE () {
		return $this->Subnets_controller->DELETE ();
	}
}

?>