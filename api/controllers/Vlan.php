<?php

/**
 *	phpIPAM API class to work with folders
 *
 *	just an alias for subnets
 *
 */
class Vlan_controller extends Common_api_functions {

    /**
     * Vlans_controller
     *
     * @var mixed
     * @access protected
     */
    protected $vlans_controller;

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
		require("Vlans.php");
		// subnets
		$this->Vlans_controller = new vlans_controller ($Database, $Tools, $params, $Response);
	}




	/**
	 * Options
	 *
	 * @access public
	 * @return void
	 */
	public function OPTIONS () {
		return $this->Vlans_controller->OPTIONS ();
	}

	/**
	 * HEAD, no response
	 *
	 * @access public
	 * @return void
	 */
	public function HEAD () {
		return $this->Vlans_controller->GET ();
	}


	/**
	 * Creates new folder
	 *
	 * @access public
	 * @return void
	 */
	public function POST () {
		return $this->Vlans_controller->POST ();
	}


	/**
	 * Read folder functions
	 *
	 * @access public
	 * @return void
	 */
	public function GET () {
		return $this->Vlans_controller->GET ();
	}


	/**
	 * Updates existing subnet
	 *
	 * @access public
	 * @return void
	 */
	public function PATCH () {
		return $this->Vlans_controller->PATCH ();
	}


	/**
	 * Deletes existing subnet along with and addresses
	 *
	 * @access public
	 * @return void
	 */
	public function DELETE () {
		return $this->Vlans_controller->DELETE ();
	}
}

?>