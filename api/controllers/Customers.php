<?php

/**
 *	phpIPAM API class to work with Customers
 *
 *
 */

class Customers_controller extends Common_api_functions {

	/**
	 * _params [provided
	 *
	 * @var mixed
	 * @access public
	 */
	public $_params;

	/**
	 * Database object
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $Database;

	/**
	 * Master Tools object
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $Tools;

	/**
	 * Master  Admin object
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $Admin;


	/**
	 * __construct function
	 *
	 * @access public
	 * @param class $Database
	 * @param class $Tools
	 * @param mixed $params		// post/get values
	 * @param class $Response
	 */
	public function __construct($Database, $Tools, $params, $Response) {
		$this->Database = $Database;
		$this->Tools 	= $Tools;
		$this->_params 	= $params;
		$this->Response = $Response;
		// init required objects
		$this->init_object ("Admin", $Database);
		$this->init_object ("User", $Database);
		// set valid keys
		$this->set_valid_keys ("customers");
	}

	/**
	 * Returns json encoded options
	 *
	 * @access public
	 * @return void
	 */
	public function OPTIONS () {
		// validate
		$this->validate_options_request ();

		// methods
		$result['methods'] = array(
								array("href"=>"/api/".$this->_params->app_id."/customers/", "methods"=>array(array("rel"=>"options", "method"=>"OPTIONS"))),
								array("href"=>"/api/".$this->_params->app_id."/customers/{id}/", "methods"=>array(array("rel"=>"read", 	"method"=>"GET"),
																											 array("rel"=>"create", "method"=>"POST"),
																											 array("rel"=>"update", "method"=>"PATCH"),
																											 array("rel"=>"delete", "method"=>"DELETE"))),
							);
		# result
		return array("code"=>200, "data"=>$result);
	}

	/**
	 * Read Customers
	 *
	 *	identifiers:
	 *		- /				        // returns all Customers
	 *		- /{id}/				// returns Customer by id
	 *		- /all/			        // returns all Customers
	 *
	 *
	 * @access public
	 * @return void
	 */
	public function GET () {
		// all
		if (!isset($this->_params->id) || $this->_params->id == "all") {
			$result = $this->Tools->fetch_all_objects ("customers", 'id');
			// check result
			if($result===false)						{ $this->Response->throw_exception(200, 'No Customers configured'); }
			else									{ return array("code"=>200, "data"=>$this->prepare_result ($result, null, true, true)); }
		}
		// by id
		else {
			// validate
			$this->validate_customer ();
			// fetch
			$result = $this->Tools->fetch_object ("customers", "id", $this->_params->id);
			// check result
			if($result===false)						{ $this->Response->throw_exception(404, "Customer not found"); }
			else									{ return array("code"=>200, "data"=>$this->prepare_result ($result, null, true, true)); }
		}
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
	 * Creates new Customer
	 *
	 * @access public
	 * @return void
	 */
	public function POST () {
		# check for valid keys
		$values = $this->validate_keys ();

		# validate input
		$this->validate_customer_edit ();

		# execute update
		if(!$this->Admin->object_modify ("customers", "add", "id", $values))
													{ $this->Response->throw_exception(500, "Customer creation failed"); }
		else {
			//set result
			return array("code"=>201, "message"=>"Customer created", "id"=>$this->Admin->lastId, "location"=>"/api/".$this->_params->app_id."/customers/".$this->Admin->lastId."/");
		}
	}

	/**
	 * Updates existing Customer
	 *
	 * @access public
	 * @return void
	 */
	public function PATCH () {

		# verify
		$this->validate_customer ();
		# check that it exists
		$this->validate_customer_edit ();

		# validate and prepare keys
		$values = $this->validate_keys ();

		# execute update
		if(!$this->Admin->object_modify ("customers", "edit", "id", $values))
													{ $this->Response->throw_exception(500, "Customer edit failed"); }
		else {
			//set result
			return array("code"=>200, "message"=>"Customer updated");
		}
	}

	/**
	 * Deletes existing Customer
	 *
	 * @access public
	 * @return void
	 */
	public function DELETE () {
		# check that vrf exists
		$this->validate_vrf ();

		# execute delete
		if(!$this->Admin->object_modify ("customers", "delete", "id", $values))
													{ $this->Response->throw_exception(500, "Customer delete failed"); }
		else {
			// delete all references
			$this->Admin->remove_object_references ("circuits", "customer_id", $this->_params->id);
			$this->Admin->remove_object_references ("ipaddresses", "customer_id", $this->_params->id);
			$this->Admin->remove_object_references ("racks", "customer_id", $this->_params->id);
			$this->Admin->remove_object_references ("subnets", "customer_id", $this->_params->id);
			$this->Admin->remove_object_references ("vlans", "customer_id", $this->_params->id);
			$this->Admin->remove_object_references ("vrf", "customer_id", $this->_params->id);

			// set result
			return array("code"=>200, "message"=>"Customer deleted");
		}
	}

	/* @validations ---------- */

	/**
	 * Validates Customer - checks if it exists
	 *
	 * @access private
	 * @return void
	 */
	private function validate_customer () {
		// validate id
		if(!isset($this->_params->id))														{ $this->Response->throw_exception(400, "Customer Id is required");  }
		// validate number
		if(!is_numeric($this->_params->id))													{ $this->Response->throw_exception(400, "Customer Id must be numeric"); }
		// check that it exists
		if($this->Tools->fetch_object ("customers", "id", $this->_params->id) === false )	{ $this->Response->throw_exception(400, "Invalid Customer id"); }
	}

	/**
	 * Validates Customer on add and edit
	 *
	 * @access private
	 * @return void
	 */
	private function validate_customer_edit () {
		// check for POST method
		if($_SERVER['REQUEST_METHOD']=="POST") {
			// check name
			if(strlen($this->_params->title)==0)												{ $this->Response->throw_exception(400, "Customer title is required"); }
			// check that it exists
			if($this->Tools->fetch_object ("customers", "title", $this->_params->name) !== false )	{ $this->Response->throw_exception(409, "Customer with that title already exists"); }
			if(strlen($this->_params->address)==0)												{ $this->Response->throw_exception(400, "Customer address is required"); }
			if(strlen($this->_params->postcode)==0)												{ $this->Response->throw_exception(400, "Customer postcode is required"); }
			if(strlen($this->_params->city)==0)													{ $this->Response->throw_exception(400, "Customer city is required"); }
			if(strlen($this->_params->state)==0)												{ $this->Response->throw_exception(400, "Customer state is required"); }
		}
		// update check
		else {
			// old values
			$customer_old = $this->Tools->fetch_object ("customers", "vrfId", $this->_params->id);

			if(isset($this->_params->title)) {
				if ($this->_params->title != $vrf_old->title) {
					if($this->Tools->fetch_object ("customers", "title", $this->_params->title))	{ $this->Response->throw_exception(409, "Customer with that name already exists"); }
				}
			}
		}
	}
}
