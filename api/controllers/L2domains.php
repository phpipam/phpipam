<?php

/**
 *	phpIPAM API class to work with VLAN domains
 *
 *
 */

class L2domains_controller extends Common_api_functions {

	/**
	 * __construct function
	 *
	 * @access public
	 * @param class $Database
	 * @param class $Tools
	 * @param mixed $params		// post/get values
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
		$result = array();
		$result['methods'] = array(
								array("href"=>"/api/l2domains/".$this->_params->app_id."/", 		"methods"=>array(array("rel"=>"options", "method"=>"OPTIONS"))),
								array("href"=>"/api/l2domains/".$this->_params->app_id."/{id}/", 	"methods"=>array(array("rel"=>"read", 	"method"=>"GET"),
																												 	array("rel"=>"create", "method"=>"POST"),
																												 	array("rel"=>"update", "method"=>"PATCH"),
																												 	array("rel"=>"delete", "method"=>"DELETE"))),
							);
		# result
		return array("code"=>200, "data"=>$result);
	}





	/**
	 * Read domain functions
	 *
	 *	identifier can be:
	 *		- / 				// will return all domains
	 *		- /{id}/
	 *		- /{id}/vlans/
	 *		- /custom_fields/
	 *		- /all/				// will return all domains
	 *
	 * @access public
	 * @return void
	 */
	public function GET () {
		// all domains
		if(!isset($this->_params->id) || $this->_params->id == "all") {
			$result = $this->Tools->fetch_all_objects ("vlanDomains", 'id', true);
			// check result
			if($result===false)						{ $this->Response->throw_exception(200, 'No domains configured'); }
			else									{ return array("code"=>200, "data"=>$this->prepare_result ($result, null, true, true)); }
		}
		// set
		else {
			// custom fields
			if($this->_params->id=="custom_fields") {
				if(sizeof($this->custom_fields)==0)	{ $this->Response->throw_exception(200, 'No custom fields defined'); }
				else								{ return array("code"=>200, "data"=>$this->custom_fields); }
			}
			// vlans
			elseif (@$this->_params->id2=="vlans") {
				// validate domain
				$this->validate_domain ();
				// save result
				$result = $this->Tools->fetch_multiple_objects ("vlans", "domainId", $this->_params->id, 'vlanId', true);
				// check result
				if($result==NULL)					{ $this->Response->throw_exception(200, "No vlans belonging to this domain"); }
				else								{ return array("code"=>200, "data"=>$this->prepare_result ($result, null, true, true)); }
			}
			// id
			else {
				// validate domain
				$this->validate_domain ();
				// result
				$result = $this->Tools->fetch_object ("vlanDomains", "id", $this->_params->id);
				// check result
				if($result==NULL)					{ $this->Response->throw_exception(404, "Invalid domain id"); }
				else								{ return array("code"=>200, "data"=>$this->prepare_result ($result, null, true, true)); }
			}

		}
	}






	/**
	 * Creates new domain
	 *
	 * @access public
	 * @return void
	 */
	public function POST () {
		# remap keys
		$this->remap_keys ();

		# check for valid keys
		$values = $this->validate_keys ();

		# validate input
		$this->validate_domain_edit ();

		# execute update
		if(!$this->Admin->object_modify ("vlanDomains", "add", "id", $values))
													{ $this->Response->throw_exception(500, "Domain creation failed"); }
		else {
			//set result
			return array("code"=>201, "message"=>"L2 domain created", "id"=>$this->Admin->lastId, "location"=>"/api/".$this->_params->app_id."/l2domains/".$this->Admin->lastId."/");
		}
	}





	/**
	 * Updates existing domain
	 *
	 * @access public
	 * @return void
	 */
	public function PATCH () {
		# remap keys
		$this->remap_keys ();

		# verify
		$this->validate_domain_edit ();
		# check that it exists
		$this->validate_domain ();

		# validate and prepare keys
		$values = $this->validate_keys ();

		# execute update
		if(!$this->Admin->object_modify ("vlanDomains", "edit", "id", $values))
													{ $this->Response->throw_exception(500, "Domain edit failed"); }
		else {
			//set result
			return array("code"=>200, "message"=>"L2 domain updated");
		}
	}







	/**
	 * Deletes existing domain
	 *
	 * @access public
	 * @return void
	 */
	public function DELETE () {
		# check that domain exists
		$this->validate_domain ();

		# set variables for update
		$values = array();
		$values["id"] = $this->_params->id;

		# execute delete
		if(!$this->Admin->object_modify ("vlanDomains", "delete", "id", $values))
													{ $this->Response->throw_exception(500, "L2 domain delete failed"); }
		else {
			// delete references, reset to default
			$this->Admin->update_object_references ("vlans", "domainId", $this->_params->id, 1);

			// set result
			return array("code"=>200, "message"=>"L2 domain deleted and vlans migrated to default domain");
		}
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
		if(!isset($this->_params->id))														{ $this->_params->id = 1; }
		// validate number
		if(!is_numeric($this->_params->id))													{ $this->Response->throw_exception(400, "Domain id must be numeric"); }
		// check that it exists
		if($this->Tools->fetch_object ("vlanDomains", "id", $this->_params->id) === false )
																							{ $this->Response->throw_exception(404, "Invalid domain id"); }
	}


	/**
	 * Validates domain on edit
	 *
	 * @access private
	 * @return void
	 */
	private function validate_domain_edit () {
		// delete checks
		if($_SERVER['REQUEST_METHOD']=="DELETE") {
			// we cannot delete default domain
			if(@$this->_params->id==1 && $_SERVER['REQUEST_METHOD']=="DELETE")				{ $this->Response->throw_exception(409, "Default domain cannot be deleted"); }
			// ID must be numeric
			if(!is_numeric($this->_params->id))												{ $this->Response->throw_exception(404, "Invalid domain id"); }
		}
		// create checks
		elseif ($_SERVER['REQUEST_METHOD']=="POST") {
			// name must be present
			if(@$this->_params->name == "" || !isset($this->_params->name)) 				{ $this->Response->throw_exception(400, "Domain name is mandatory"); }
		}
		// update checks
		elseif ($_SERVER['REQUEST_METHOD']=="PATCH") {
			// ID must be numeric
			if(!is_numeric($this->_params->id))												{ $this->Response->throw_exception(400, "Invalid domain id"); }
			// name must be present
			if(@$this->_params->name == "" && isset($this->_params->name)) 					{ $this->Response->throw_exception(400, "Domain name is mandatory"); }
		}

	}
}
