<?php

/**
 *	phpIPAM API class to work with vlans
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
 *		- domain (bool) - this parameter controls weather we will operate on vlans or on domains
 *
 *	add/create
 *		all parameters
 */

class Vlans_controller {

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
		# set key - domain or vlan ?
		$key = $this->_params->domains=="true" ? "vlanDomains" : "vlans";

		# array of controller keys
		$this->controller_keys = array("app_id", "controller", "action", "domains");

		# array of all valid keys - fetch from SHCEMA
		$this->valid_keys = $this->Tools->fetch_standard_fields ($key);

		# add custom fields
		$custom_fields = $this->Tools->fetch_custom_fields($key);
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
	 * Creates new vlan
	 *
	 * @access public
	 * @return void
	 */
	public function add () {
		# domains or vlans
		if(@$this->_params->domains=="true")	{ return $this->add_domain (); }
		else									{ return $this->add_vlan (); }
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
	 * Creates new VLAN
	 *
	 * @access private
	 * @return void
	 */
	private function add_vlan () {
		# check for valid keys
		$values = $this->validate_keys ();

		# verify or set domain
		if(isset($this->_params->domainId)) { $this->validate_domain ($this->_params->domainId); }
		else 								{ $this->_params->domainId = 1; }

		# validate input
		$this->validate_vlan_edit ("add");

		# execute update
		if(!$this->Admin->object_modify ("vlans", "add", "vlanId", $values))
													{ throw new Exception('Vlan create failed'); }
		else {
			//set result
			$result['result']   = "success";
			$result['response'] = "Vlan created successfully";
			$result['id'] 		= $this->Admin->lastId;
		}
		# return
		return $result;
	}

	/**
	 * Created new domain
	 *
	 * @access private
	 * @return void
	 */
	private function add_domain () {
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
		# return
		return $result;
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

	/**
	 * Validates VLAN
	 *
	 * @access private
	 * @param mixed $action
	 * @return void
	 */
	private function validate_vlan_edit ($action="add") {
		# get settings
		$this->settings = $this->Admin->fetch_object ("settings", "id", 1);

		//if it already exist die
		if($this->settings->vlanDuplicate==0 && $action=="add") {
			$check_vlan = $this->Admin->fetch_multiple_objects ("vlans", "domainId", $this->_params->domainId, "vlanId");
			if($check_vlan!==false) {
				foreach($check_vlan as $v) {
					if($v->number == $this->_params->number) {
																					{ throw new Exception('VLAN already exists'); }
					}
				}
			}
		}

		//if number too high
		if($this->_params->number>$this->settings->vlanMax && $action!="delete")	{ throw new Exception('Highest possible VLAN number is '.$this->settings->vlanMax.'!'); }
		if($action=="add") {
			if($this->_params->number<0)											{ throw new Exception('VLAN number cannot be negative'); }
			elseif(!is_numeric($this->_params->number))								{ throw new Exception('Not number'); }
		}
		if(strlen($this->_params->name)==0)											{ throw new Exception('Name is required'); }
	}





	/**
	 * Read vlan/domain functions
	 *
	 *
	 * @access public
	 * @return void
	 */
	public function read () {
		# domains or vlans
		if(@$this->_params->domains=="true")	{ return $this->read_domain (); }
		else									{ return $this->read_vlan (); }
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
	 * Reads vlan details
	 *
	 * @access private
	 * @return void
	 */
	private function read_vlan () {
		// check for Id
		if(!isset($this->_params->id))		{ throw new Exception('Vlan Id is required'); }
		if(!is_numeric($this->_params->id))	{ throw new Exception('Vlan Id must be a number'); }

		// check weather to read belonging subnets
		elseif(@$this->_params->subnets=="true") {
			// first validate
			$this->validate_vlan ();
			// save result
			$result = $this->Tools->fetch_multiple_objects ("subnets", "vlanId", $this->_params->id, 'id', true);
			// none
			if($result===false)				{ $result = null; }
		}
		// read vlan details
		else {
			$result = $this->Tools->fetch_object ("vlans", "vlanId", $this->_params->id);
		}

		# return result
		if($result===false)					{ throw new Exception('Invalid vlan Id'); }
		else								{ return $result; }
	}

	/**
	 * Reads l2 domain parameters
	 *
	 * @access private
	 * @return void
	 */
	private function read_domain () {
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
	 * Validates Vlan
	 *
	 * @access private
	 * @return void
	 */
	private function validate_vlan () {
		// validate id
		if(!isset($this->_params->id))														{ throw new Exception('Vlan Id is required'); }
		// validate number
		if(!is_numeric($this->_params->id))													{ throw new Exception('Vlan Id must be numeric'); }
		// check that it exists
		if($this->Tools->fetch_object ("vlans", "vlanId", $this->_params->id) === false )	{ throw new Exception('Invalid Vlan Id'); }
	}

	/**
	 * Validates domains
	 *
	 * @access private
	 * @return void
	 */
	private function validate_domain ($id = null) {
		// override keys if requested
		$id = $id===null ? $this->_params->id : $id;

		// validate id
		if(!isset($id))																		{ throw new Exception('Domain Id is required'); }
		// validate number
		if(!is_numeric($id))																{ throw new Exception('Domain Id must be numeric'); }
		// check that it exists
		if($this->Tools->fetch_object ("vlans", "vlanId", $id) === false )					{ throw new Exception('Invalid Domain Id'); }
	}




	/**
	 * Updates existing vlan/domain
	 *
	 * @access public
	 * @return void
	 */
	public function edit() {
		# domains or vlans
		if(@$this->_params->domains=="true")	{ return $this->edit_domain (); }
		else									{ return $this->edit_vlan (); }
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
	 * Edits VLAN
	 *
	 * @access private
	 * @return void
	 */
	private function edit_vlan () {
		# verify
		$this->validate_vlan_edit ("edit");
		# check that it exists
		$this->read ();

		# rewrite id
		$this->_params->vlanId = $this->_params->id;
		unset($this->_params->id);

		# validate and prepare keys
		$values = $this->validate_keys ();

		# execute update
		if(!$this->Admin->object_modify ("vlans", "edit", "vlanId", $values))
													{ throw new Exception('Vlan edit failed'); }
		else {
			//set result
			$result['result']   = "success";
			$result['response'] = "Vlan id ".$this->_params->vlanId." edited successfully";
		}
		# return
		return $result;
	}

	/**
	 * Edits existing domain
	 *
	 * @access private
	 * @return void
	 */
	private function edit_domain () {
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
		# return
		return $result;
	}






	/**
	 * Deletes existing vlan
	 *
	 * @access public
	 * @return void
	 */
	public function delete() {
		# domains or vlans
		if(@$this->_params->domains=="true")	{ return $this->delete_domain (); }
		else									{ return $this->delete_vlan (); }
	}

	/**
	 * Delete vlan
	 *
	 * @access private
	 * @return void
	 */
	private function delete_vlan () {
		# Check for id
		if(!isset($this->_params->id))				{ throw new Exception('Vlan Id required'); }
		# check that vlan exists
		$this->validate_vlan ();

		# set variables for update
		$values["vlanId"] = $this->_params->id;

		# execute delete
		if(!$this->Admin->object_modify ("vlans", "delete", "vlanId", $values))
													{ throw new Exception('Vlan delete failed'); }
		else {
			// delete all references
			$Admin->remove_object_references ("subnets", "vlanId", $this->_params->id);

			// set result
			$result['result']   = "success";
			$result['response'] = "Vlan id ".$this->_params->id." deleted successfully";
		}
		# return
		return $result;
	}

	/**
	 * Delete domain
	 *
	 * @access private
	 * @return void
	 */
	private function delete_domain () {
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

}

?>