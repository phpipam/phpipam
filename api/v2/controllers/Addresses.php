<?php

/**
 *	phpIPAM API class to work with Addresses
 *
 *	actions:
 *		- add/create
 *		- read/fetch
 *		- edit/update
 *		- delete
 *
 *	identifiers
 *		- id
 *		- name
 *
 *	add/create
 *		all parameters
 */
class Addresses_controller {

	/* public variables */
	public $_params;

	/* protected variables */
	protected $valid_keys;

	/* object holders */
	private $Database;			// Database object
	private $Sections;			// Sections object
	private $Tools;				// Tools object
	private $Admin;				// Admin object


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
		// Admin
		$this->init_Admin ($Database);
		// set valid keys
		$this->set_valid_keys ();
	}

	/**
	 * Initializes Admin object
	 *
	 * @access private
	 * @param class $Database
	 * @return void
	 */
	private function init_Admin ($Database) {
		$this->Admin = new Admin ($Database, false);
		//set exit method
		$this->Admin->Result->exit_method = "exception";
	}

	/**
	 * Sets valid keys for actions
	 *
	 * @access private
	 * @return void
	 */
	private function set_valid_keys () {
		# array of controller keys
		$this->controller_keys = array("app_id", "controller", "action");

		# array of all valid keys - fetch from SHCEMA
		$this->valid_keys = $this->Tools->fetch_standard_fields ("ipaddresses");

		# add custom fields
		$custom_fields = $this->Tools->fetch_custom_fields("ipaddresses");
		if(sizeof($custom_fields)>0) {
			foreach($custom_fields as $cf) {
				$this->custom_keys[] = $cf['name'];
			}
		}

		# merge all
		$this->valid_keys = array_merge($this->controller_keys, $this->valid_keys);
		if(isset($this->custom_keys)) {
			$this->valid_keys = array_merge($this->valid_keys, $this->custom_keys);
		}

		# set items to remove
		$this->remove_keys = array("editDate");
		# remove update time
		foreach($this->valid_keys as $k=>$v) {
			if(in_array($v, $this->remove_keys)) {
				unset($this->valid_keys[$k]);
			}
		}
	}

	/**
	 * Validates posted keys and returns proper inset valies
	 *
	 * @access private
	 * @return void
	 */
	private function validate_keys () {
		foreach($this->_params as $pk=>$pv) {
			if(!in_array($pk, $this->valid_keys)) 		{ throw new Exception('Invalid request key '.$pk); }
			// set parameters
			else {
				if(!in_array($pk, $this->controller_keys)) {
					 $values[$pk] = $pv;
				}
			}
		}
		# return
		return $values;
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