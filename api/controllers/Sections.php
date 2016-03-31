<?php

/**
 *	phpIPAM API class to work with sections
 *
 *
 */
class Sections_controller extends Common_api_functions {


	/**
	 * _params provided
	 *
	 * @var mixed
	 * @access public
	 */
	public $_params;

	/**
	 * custom_fields
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $custom_fields;

	/**
	 * Database object
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $Database;

	/**
	 *  Response handler
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $Response;

	/**
	 * Master Subnets object
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $Subnets;

	/**
	 * Master Sections object
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $Sections;

	/**
	 * Master Tools object
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $Tools;


	/**
	 * __construct function
	 *
	 * @access public
	 * @param class $Database
	 * @param class $Tools
	 * @param mixed $params
	 * @param mixed $Response
	 */
	public function __construct($Database, $Tools, $params, $Response) {
		$this->Database = $Database;
		$this->Response = $Response;
		$this->Tools 	= $Tools;
		$this->_params 	= $params;
		# sections
		// init required objects
		$this->init_object ("Sections", $Database);
		# set valid keys
		$this->set_valid_keys ("sections");
	}





	/**
	 * Returns json encoded options and version
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
								array("href"=>"/api/".$this->_params->app_id."/sections/", 			"methods"=>array(array("rel"=>"options", "method"=>"OPTIONS"))),
								array("href"=>"/api/".$this->_params->app_id."/sections/{id}/", 	"methods"=>array(array("rel"=>"read", 	"method"=>"GET"),
																													 array("rel"=>"create", "method"=>"POST"),
																													 array("rel"=>"update", "method"=>"PATCH"),
																													 array("rel"=>"delete", "method"=>"DELETE"))),
							);
		# result
		return array("code"=>200, "data"=>$result);
	}





	/**
	 * GET sections functions
	 *
	 *	ID can be:
	 *		- {id}
	 *		- {id}/subnets/		// returns all subnets in this section
	 *		- name 				// section name
	 *		- custom_fields		// returns custom fields
	 *
	 *	If no ID is provided all sections are returned
	 *
	 * @access public
	 * @return void
	 */
	public function GET () {
		// fetch subnets in section
		if(@$this->_params->id2=="subnets" && is_numeric($this->_params->id)) {
			// we dont need id2 anymore
			unset($this->_params->id2);
			//validate section
			$this->GET ();
			// init required objects
			$this->init_object ("Subnets", $this->Database);
			//fetch
			$result = $this->Subnets->fetch_section_subnets ($this->_params->id);
            // add gateway
			if($result!=false) {
				foreach ($result as $k=>$r) {
    				//gw
            		$gateway = $this->read_subnet_gateway ($r->id);
            		if ( $gateway!== false) {
                		$result[$k]->gatewayId = $gateway->id;
            		}
            		//nameservers
            		$ns = $this->read_subnet_nameserver ($r->nameserverId);
                    if ($ns!==false) {
                        $result[$k]->nameservers = $ns;
                    }
				}
			}
			// check result
			if(sizeof($result)==0) 						{ return array("code"=>200, "data"=>NULL); }
			else										{ return array("code"=>200, "data"=>$this->prepare_result ($result, "subnets", true, true)); }
		}
		// verify ID
		elseif(isset($this->_params->id)) {
			# fetch by id
			if(is_numeric($this->_params->id)) {
				$result = $this->Sections->fetch_section ("id", $this->_params->id);
				// check result
				if(sizeof($result)==0) 					{ $this->Response->throw_exception(404, NULL); }
				else									{ return array("code"=>200, "data"=>$this->prepare_result ($result, null, true, true)); }
			}
			# return custom fields
			elseif($this->_params->id=="custom_fields") {
				// check result
				if(sizeof($this->custom_fields)==0)		{ $this->Response->throw_exception(404, 'No custom fields defined'); }
				else									{ return array("code"=>200, "data"=>$result); }
			}
			# fetch by name
			else {
				$result = $this->Sections->fetch_section ("name", $this->_params->id);
				// check result
				if(sizeof($result)==0) 					{ $this->Response->throw_exception(404, $this->Response->errors[404]); }
				else									{ return array("code"=>200, "data"=>$this->prepare_result ($result, null, true, true)); }
			}
		}
		# all sections
		else {
				// all sections
				$result = $this->Sections->fetch_all_sections();
				// check result
				if($result===false) 					{ return array("code"=>204, NULL); }
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
	 * Creates new section
	 *
	 * @access public
	 * @return void
	 */
	public function POST () {
		# check for valid keys
		$values = $this->validate_keys ();

		// remove editDate if set
		unset($values['editDate']);

		# validate mandatory parameters
		if(strlen($this->_params->name)<3)				{ $this->Response->throw_exception(400, 'Name is mandatory or too short (mininum 3 characters)'); }

		# verify masterSection
		if(isset($this->_params->masterSection)) {
			$masterSection = $this->Sections->fetch_section ("id", $this->_params->masterSection);
			// checks
			if(sizeof($masterSection)==0)				{ $this->Response->throw_exception(400, 'Invalid masterSection id '.$this->_params->masterSection); }
			elseif($masterSection->masterSection!="0")	{ $this->Response->throw_exception(400, 'Only 1 level of nesting is permitted for sections');  }
		}

		# execute update
		if(!$this->Sections->modify_section ("add", $values))
														{ $this->Response->throw_exception(500, "Section create failed"); }
		else {
			//set result
			return array("code"=>201, "data"=>"Section created", "location"=>"/api/".$this->_params->app_id."/sections/".$this->Sections->lastInsertId."/");
		}
	}





	/**
	 * Updates existing section
	 *
	 * @access public
	 * @return void
	 */
	public function PATCH () {
		# Check for id
		if(!isset($this->_params->id))					{ $this->Response->throw_exception(400, "Section Id required"); }
		# check that section exists
		if(sizeof($this->Sections->fetch_section ("id", $this->_params->id))==0)
														{ $this->Response->throw_exception(404, "Section does not exist"); }

		# validate and prepare keys
		$values = $this->validate_keys ();

		# execute update
		if(!$this->Sections->modify_section ("edit", $values))
														{ $this->Response->throw_exception(500, "Section update failed"); }
		else {
			//set result
			return array("code"=>200, "data"=>NULL);
		}
	}





	/**
	 * Deletes existing section along with subnets and addresses
	 *
	 * @access public
	 * @return void
	 */
	public function DELETE () {
		# Check for id
		if(!isset($this->_params->id))					{ $this->Response->throw_exception(400, "Section Id required"); }
		# check that section exists
		if(sizeof($this->Sections->fetch_section ("id", $this->_params->id))==0)
														{ $this->Response->throw_exception(404, "Section does not exist"); }

		# set variables for update
		$values = array();
		$values["id"] = $this->_params->id;

		# execute update
		if(!$this->Sections->modify_section ("delete", $values))
														{ $this->Response->throw_exception(500, "Section delete failed"); }
		else {
			//set result
			return array("code"=>200, "data"=>NULL);
		}
	}

	/**
	 * Returns id of subnet gateay
	 *
	 * @access private
	 * @params mixed $subnetId
	 * @return void
	 */
	private function read_subnet_gateway ($subnetId) {
    	return $this->Subnets->find_gateway ($subnetId);
	}

	/**
	 * Returns nameserver details
	 *
	 * @access private
	 * @param mixed $nsid
	 * @return void
	 */
	private function read_subnet_nameserver ($nsid) {
    	return $this->Tools->fetch_object ("nameservers", "id", $nsid);
	}
}

?>
