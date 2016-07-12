<?php

/**
 *	phpIPAM API class to work with folders
 *
 *	just an alias for subnets
 *
 */
class Vrf_controller extends Common_api_functions {

    /**
     * vrf_controller
     *
     * @var mixed
     * @access protected
     */
    protected $vrf_controller;

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
		$this->vrf_controller = new Vrfs_controller ($Database, $Tools, $params, $Response);
	}




	/**
	 * Options
	 *
	 * @access public
	 * @return void
	 */
	public function OPTIONS () {
		return $this->vrf_controller->OPTIONS ();
	}

	/**
	 * HEAD, no response
	 *
	 * @access public
	 * @return void
	 */
	public function HEAD () {
		return $this->vrf_controller->GET ();
	}


	/**
	 * Creates new folder
	 *
	 * @access public
	 * @return void
	 */
	public function POST () {
		return $this->vrf_controller->POST ();
	}


	/**
	 * Read folder functions
	 *
	 * @access public
	 * @return void
	 */
	public function GET () {
		return $this->vrf_controller->GET ();
	}


	/**
	 * Updates existing subnet
	 *
	 * @access public
	 * @return void
	 */
	public function PATCH () {
		return $this->vrf_controller->PATCH ();
	}


	/**
	 * Deletes existing subnet along with and addresses
	 *
	 * @access public
	 * @return void
	 */
	public function DELETE () {
		return $this->vrf_controller->DELETE ();
	}
}

?>