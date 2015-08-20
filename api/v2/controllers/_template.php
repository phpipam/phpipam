<?php

/**
 *	phpIPAM API class template
 *
 *
 *	Exception handling:
 *		$this->Response->throw_exception(404, "Exception text");
 *		(codes are available in Responses controller)
 *
 *	Success handling:
 *		return array("code"=>200, "data"=>"Text or object with result");
 *		return array("code"=>200, "data"=>$this->prepare_result ("Text or object with result", null, true, true));
 *		return array("code"=>201, "data"=>"Object created", "location"=>"/api/".$this->_params->app_id."/".$this->_params->controller."/".$this->Tools->lastId."/");
 *
 */
class Tools_controller extends Common_api_functions {

	/* public variables */
	public $result_type;				// sets output - JSON or XML
	public $result;						// result

	/* object holders */
	protected $Database;
	protected $Response;
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
	public function __construct($Database, $Tools, $params, $Response) {
		$this->Database = $Database;
		$this->Response = $Response;
		$this->Tools 	= $Tools;
		$this->_params 	= $params;
		// init required objects
		$this->init_object ("Subnets", $Database);
		// set valid keys
		$this->set_valid_keys ("mydatabase");
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
		// remap keys if needed
		$this->remap_keys ();
		// check for valid keys
		$values = $this->validate_keys ();
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
	 * HEAD, no response
	 *
	 * @access public
	 * @return void
	 */
	public function HEAD () {
		return $this->GET ();
	}





	/**
	 * Update object
	 *
	 * @access public
	 * @return void
	 */
	public function PATCH () {
		// remap keys if needed
		$this->remap_keys ();
		// check for valid keys
		$values = $this->validate_keys ();
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