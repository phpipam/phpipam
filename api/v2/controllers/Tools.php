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
	 * returns general Controllers and supported methods
	 *
	 * @access public
	 * @return void
	 */
	public function OPTIONS () {
		// controllers
		$controllers = array(
						array("href"=>"/api/".$_GET['app_id']."/sections/"),
						array("href"=>"/api/".$_GET['app_id']."/subnets/"),
						array("href"=>"/api/".$_GET['app_id']."/folders/"),
						array("href"=>"/api/".$_GET['app_id']."/addresses/"),
						array("href"=>"/api/".$_GET['app_id']."/vlans/"),
						array("href"=>"/api/".$_GET['app_id']."/vrfs/"),
						array("href"=>"/api/".$_GET['app_id']."/tools/"),
					);
		# result
		return array("code"=>200, "data"=>$controllers);
	}






	/**
	 * Creates new vlan
	 *
	 * @access public
	 * @return void
	 */
	public function POST () {

	}





	/**
	 * Read vlan functions
	 *
	 * @access public
	 * @return void
	 */
	public function GET () {

	}





	/**
	 * Updates existing vlan
	 *
	 * @access public
	 * @return void
	 */
	public function PATCH () {

	}

	/**
	 * Alias function for edit
	 *
	 * @access public
	 * @return void
	 */
	public function PUT () {
		return $this->PATCH ();
	}





	/**
	 * Deletes existing vlan
	 *
	 * @access public
	 * @return void
	 */
	public function DELETE () {

	}
}

?>