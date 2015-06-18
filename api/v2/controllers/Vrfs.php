<?php

/**
 *	phpIPAM API class to work with vrfs
 *
 *
 */

class Vrfs_controller extends Common_functions {

	/* public variables */
	public $_params;

	/* protected variables */
	protected $valid_keys;

	/* object holders */
	protected $Database;			// Database object
	protected $Sections;			// Sections object
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
		// set valid keys
		$this->set_valid_keys ("vrf");

		//die
		$this->Response->throw_exception(501, 'Not implemented');
	}











	/**
	 * Read vrf
	 *
	 *
	 * @access public
	 * @return void
	 */
	public function GET () {
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
	 * Creates new VRF
	 *
	 * @access public
	 * @return void
	 */
	public function POST () {
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
	 * Updates existing vrf
	 *
	 * @access public
	 * @return void
	 */
	public function PATCH () {
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
	 * Deletes existing vrf
	 *
	 * @access public
	 * @return void
	 */
	public function DELETE () {
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










	/* @validations ---------- */

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
}

?>