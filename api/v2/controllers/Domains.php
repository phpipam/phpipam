<?php

/**
 *	phpIPAM API class to work with VLAN domains
 *
 *
 */

class Vlans_controller extends Common_functions {

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
		$this->init_object ("Subnets", $Database);
		// set valid keys
		$this->set_valid_keys ("vlanDomains");

		//die
		$this->Response->throw_exception(501, 'Not implemented');
	}






	/**
	 * Read domain functions
	 *
	 *
	 * @access public
	 * @return void
	 */
	public function GET () {
		// check for Id
		if(!isset($this->_params->id))		{ throw new Exception('Domain Id is required'); }

		// all domains
		if(@$this->_params->id=="all") {
			$result = $this->Tools->fetch_all_objects ("vlanDomains", 'id', true);
			//none
			if($result===false)				{ $result = null; }
		}
		// check for Id
		elseif(!isset($this->_params->id))	{ throw new Exception('Domain Id is required'); }
		// per-domain vlans
		elseif(@$this->_params->vlans=="true") {
			// validate domain
			$this->validate_domain ();
			// save result
			$result = $this->Tools->fetch_multiple_objects ("vlans", "domainId", $this->_params->id, 'vlanId', true);
			// none
			if($result===false)				{ $result = null; }
		}
		// domain
		else {
			if(!is_numeric($this->_params->id))	{ throw new Exception('Domain Id must be a number'); }
			// result
			$result = $this->Tools->fetch_object ("vlanDomains", "id", $this->_params->id);
		}

		# return result
		if($result===false)					{ throw new Exception('Invalid domain Id'); }
		else								{ return $result; }
	}






	/**
	 * Creates new domain
	 *
	 * @access public
	 * @return void
	 */
	public function POST () {
		# check for valid keys
		$values = $this->validate_keys ();

		# validate input
		$this->validate_domain_edit ("add");

		# execute update
		if(!$this->Admin->object_modify ("vlanDomains", "add", "id", $values))
													{ throw new Exception('Vlan create failed'); }
		else {
			//set result
			$result['result']   = "success";
			$result['response'] = "Vlan domain created successfully";
			$result['id'] 		= $this->Admin->lastId;
		}
	}





	/**
	 * Updates existing domain
	 *
	 * @access public
	 * @return void
	 */
	public function PATCH () {
		# verify
		$this->validate_domain_edit ("edit");
		# check that it exists
		$this->read ();

		# validate and prepare keys
		$values = $this->validate_keys ();

		# execute update
		if(!$this->Admin->object_modify ("vlanDomains", "edit", "id", $values))
													{ throw new Exception('Domain edit failed'); }
		else {
			//set result
			$result['result']   = "success";
			$result['response'] = "Domain id ".$this->_params->id." edited successfully";
		}
	}







	/**
	 * Deletes existing domain
	 *
	 * @access public
	 * @return void
	 */
	public function DELETE () {
		# Check for id
		if(!isset($this->_params->id))				{ throw new Exception('Domain Id required'); }
		# check that vlan exists
		$this->validate_domain ();

		# set variables for update
		$values["id"] = $this->_params->id;

		# execute delete
		if(!$this->Admin->object_modify ("vlanDomains", "delete", "id", $values))
													{ throw new Exception('Domain delete failed'); }
		else {
			// delete references, reset to default
			$this->Admin->update_object_references ("vlans", "domainId", $this->_params->id, 1);

			// set result
			$result['result']   = "success";
			$result['response'] = "Vlan id ".$this->_params->id." deleted successfully";
		}
		# return
		return $result;
	}









	/* @validations ---------- */

	/**
	 * Validates domains
	 *
	 * @access private
	 * @return void
	 */
	private function validate_domain () {
		// validate id
		if(!isset($this->_params->domainId))												{ $this->_params->domainId = 1; }
		// validate number
		if(!is_numeric($this->_params->domainId))											{ $this->Response->throw_exception(400, "Domain id must be numeric"); }
		// check that it exists
		if($this->Tools->fetch_object ("vlanDomains", "id", $this->_params->domainId) === false )
																							{ $this->Response->throw_exception(400, "Invalid domain id"); }
	}


	/**
	 * Validates domain on edit
	 *
	 * @access private
	 * @param string $action (default: "add")
	 * @return void
	 */
	private function validate_domain_edit ($action="add") {
		# we cannot delete default domain
		if(@$this->_params->id==1 && $action=="delete")				{ throw new Exception('Default domain cannot be deleted'); }
		// ID must be numeric
		if($action!="add" && !is_numeric($this->_params->id))		{ throw new Exception('Invalid ID"), true'); }
		// Hostname must be present
		if(@$this->_params->name == "") 							{ throw new Exception('Name is mandatory'); }
	}
}

?>