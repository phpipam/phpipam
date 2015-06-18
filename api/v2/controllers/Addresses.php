<?php

/**
 *	phpIPAM API class to work with Addresses
 *
 *
 */
class Addresses_controller extends Common_functions  {

	/* public variables */
	public $_params;

	/* protected variables */
	protected $valid_keys;

	/* object holders */
	protected $Database;			// Database object
	protected $Sections;			// Sections object
	protected $Response;			// Response handler
	protected $Tools;				// Tools object
	protected $Admin;				// Admin object


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
		$this->Tools 	= $Tools;
		$this->_params 	= $params;
		$this->Response = $Response;
		// init required objects
		$this->init_object ("Admin", $Database);
		$this->init_object ("Addresses", $Database);
		// set valid keys
		$this->set_valid_keys ("ipaddresses");

		//die
		$this->Response->throw_exception(501, 'Not implemented');
	}





	/**
	 * Creates new address
	 *
	 * @access public
	 * @return void
	 */
	public function add () {

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
	 * Read address functions
	 *
	 * @access public
	 * @return void
	 */
	public function read () {
		// subnet Id > read all addresses in subnet
		if(isset($this->_params->subnetId)) {
			// validate
			$this->validate_subnet ();
			// fetch
			$result = $this->Tools->fetch_multiple_objects ("ipaddresses", "subnetId", $this->_params->subnetId, 'ip_addr', true);
			// none fix
			if($result===false)				{ $result = NULL; }
		}
		// id
		else {
			// id must be set
			if(!isset($this->_params->id))	{ throw new Exception('Address Id is required'); }
			// fetch
			$result = $this->Tools->fetch_object ("ipaddresses", "id", $this->_params->id);
		}

		# return result
		if($result===false)					{ throw new Exception('Invalid address Id'); }
		else								{ return $result; }
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
	 * Validates subnet
	 *
	 * @access private
	 * @return void
	 */
	private function validate_subnet () {
		// numberic
		if(!is_numeric($this->_params->subnetId))											{ throw new Exception("Subnet Id must be numeric (".$this->_params->subnetId.")"); }
		// check subnet
		if($this->Admin->fetch_object ("subnets", "id", $this->_params->subnetId)===false)	{ throw new Exception("Invalid subnet Id (".$this->_params->subnetId.")"); }
	}





	/**
	 * Updates existing address
	 *
	 * @access public
	 * @return void
	 */
	public function edit() {

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
	 * Deletes existing address
	 *
	 * @access public
	 * @return void
	 */
	public function delete() {

	}
}

?>