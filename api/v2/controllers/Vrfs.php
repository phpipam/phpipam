<?php

/**
 *	phpIPAM API class to work with vrfs
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

class Vrfs_controller {

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
		// subnets
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
		$this->valid_keys = $this->Tools->fetch_standard_fields ("vrf");

		# add custom fields
		$custom_fields = $this->Tools->fetch_custom_fields("vrf");
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
	 * Creates new VRF
	 *
	 * @access public
	 * @return void
	 */
	public function add () {
		# check for valid keys
		$values = $this->validate_keys ();

		# validate input
		$this->validate_vrf_edit ("add");

		# execute update
		if(!$this->Admin->object_modify ("vrf", "add", "vrfId", $values))
													{ throw new Exception('Vrf create failed'); }
		else {
			//set result
			$result['result']   = "success";
			$result['response'] = "Vrf created successfully";
			$result['id'] 		= $this->Admin->lastId;
		}
		# return
		return $result;
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
	 * Validates VLAN
	 *
	 * @access private
	 * @param mixed $action
	 * @return void
	 */
	private function validate_vrf_edit ($action="add") {
		if(@$this->_params->name == "" || !isset($this->_params->name)) 			{ throw new Exception('Name is mandatory'); }
	}





	/**
	 * Read vrf
	 *
	 *
	 * @access public
	 * @return void
	 */
	public function read () {
		// id must be set
		if(!isset($this->_params->id))			{ throw new Exception('VRF Id is required'); }
		// all
		elseif($this->_params->id=="all") {
			$result = $this->Tools->fetch_all_objects ("vrf", "vrfId");
			// false fix
			if($result===false)					{ $result = NULL; }
		}
		// subnets inside VRF
		elseif(@$this->_params->subnets=="true") {
			if(!is_numeric($this->_params->id))	{ throw new Exception('VRF Id must be a number'); }

			// fetch
			$result = $this->Tools->fetch_multiple_objects ("subnets", "vrfId", $this->_params->id, 'subnet', true);
			// none fix
			if($result===false)					{ $result = NULL; }
		}
		// check for Id
		else {
			if(!is_numeric($this->_params->id))	{ throw new Exception('VRF Id must be a number'); }
			$result = $this->Tools->fetch_object ("vrf", "vrfId", $this->_params->id);
		}

		# return result
		if($result===false)					{ throw new Exception('Invalid vrf Id'); }
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
	 * Validates vrf
	 *
	 * @access private
	 * @return void
	 */
	private function validate_vrf () {
		// validate id
		if(!isset($this->_params->id))													{ throw new Exception('Vrf Id is required'); }
		// check that it exists
		if($this->Tools->fetch_object ("vrf", "vrfId", $this->_params->id) === false )	{ throw new Exception('Invalid Vrf Id'); }
	}




	/**
	 * Updates existing vrf
	 *
	 * @access public
	 * @return void
	 */
	public function edit() {
		# verify
		$this->validate_vrf_edit ("edit");
		# check that it exists
		$this->read ();

		# rewrite id
		$this->_params->vrfId = $this->_params->id;
		unset($this->_params->id);

		# validate and prepare keys
		$values = $this->validate_keys ();

		# execute update
		if(!$this->Admin->object_modify ("vrf", "edit", "vrfId", $values))
													{ throw new Exception('Vrf edit failed'); }
		else {
			//set result
			$result['result']   = "success";
			$result['response'] = "Vrf id ".$this->_params->vrfId." edited successfully";
		}
		# return
		return $result;
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
	 * Deletes existing vrf
	 *
	 * @access public
	 * @return void
	 */
	public function delete() {
		# check that vrf exists
		$this->validate_vrf ();

		# set variables for update
		$values["vrfId"] = $this->_params->id;

		# execute delete
		if(!$this->Admin->object_modify ("vrf", "delete", "vrfId", $values))
													{ throw new Exception('Vrf delete failed'); }
		else {
			// delete all references
			$this->Admin->remove_object_references ("subnets", "vrfId", $this->_params->id);

			// set result
			$result['result']   = "success";
			$result['response'] = "Vrf id ".$this->_params->id." deleted successfully";
		}
		# return
		return $result;
	}
}

?>