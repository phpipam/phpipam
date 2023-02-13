<?php

/**
 *	phpIPAM API class to work with sections
 *
 *
 */
class Sections_controller extends Common_api_functions {

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
	 *      - /                     // returns all sections
	 *		- /{id}/                // returns section details
	 *		- /{id}/subnets/		// returns all subnets in this section
	 *		- /{id}/subnets/addresses/ // returns all subnets in this section + addresses
	 *		- /{name}/subnets/		// returns all subnets in this named section
	 *		- /{name}/ 				// section name
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
			// init required objects
			$this->init_object ("Subnets", $this->Database);
			$this->init_object ("Addresses", $this->Database);
			//fetch
			$result = $this->Subnets->fetch_section_subnets ($this->_params->id);
			if(is_array($result)) {
				// add subnet details
				foreach ($result as $k=>$r) {
					// Don't calculate statistics for folders.
					if ($r->isFolder == 1) continue;

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

					// get usage
					$result[$k]->usage = $this->read_subnet_usage($r->id);

					// fetch addresses
					if(@$this->_params->id3=="addresses") {
						// fetch
						$result[$k]->addresses = $this->Addresses->fetch_subnet_addresses ($r->id);
					}
				}
			}
			// check result
			if(empty($result)) 						{ $this->Response->throw_exception(404, "No subnets found"); }
			else {
				$this->custom_fields = $this->Tools->fetch_custom_fields('subnets');
				return array("code"=>200, "data"=>$this->prepare_result ($result, "subnets", true, true));
			}
		}
		// verify ID
		elseif(isset($this->_params->id)) {
			# fetch by id
			if(is_numeric($this->_params->id)) {
				$result = $this->Sections->fetch_section ("id", $this->_params->id);
				// check result
				if($result===false) 					{ $this->Response->throw_exception(404, "Section does not exist"); }
				else									{ return array("code"=>200, "data"=>$this->prepare_result ($result, null, true, true)); }
			}
			# Custom fields not supported
			elseif($this->_params->id=="custom_fields") {
				$this->Response->throw_exception(409, 'Custom fields not supported');
			}
			# fetch by name
			else {
				$result = $this->Sections->fetch_section ("name", $this->_params->id);
				// check result
				if($result==false) 					    { $this->Response->throw_exception(404, $this->Response->errors[404]); }
				else									{ return array("code"=>200, "data"=>$this->prepare_result ($result, null, true, true)); }
			}
		}
		# all sections
		else {
				// all sections
				$result = $this->Sections->fetch_all_sections();
				// check result
				if($result===false) 					{ return array("code"=>200, "message"=>"No sections available"); }
				else									{ return array("code"=>200, "data"=>$this->prepare_result ($result, null, true, true)); }
		}
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
			return array("code"=>201, "message"=>"Section created", "id"=>$this->Sections->lastInsertId, "location"=>"/api/".$this->_params->app_id."/sections/".$this->Sections->lastInsertId."/");
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
		if($this->Sections->fetch_section ("id", $this->_params->id)===false)
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
		if($this->Sections->fetch_section ("id", $this->_params->id)===false)
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
 	 * Calculates subnet usage
	 *
	 * @access private
	 * @param mixed $subnetId
	 * @return array
	 */
	private function read_subnet_usage ($subnetId) {
		# check that section exists
		$subnet = $this->Subnets->fetch_subnet ("id", $subnetId);
		if($subnet===false)
														{ $this->Response->throw_exception(404, "Subnet does not exist"); }
        # calculate
        $subnet_usage = $this->Subnets->calculate_subnet_usage ($subnet);     //Calculate free/used etc

        # return
        return $subnet_usage;
	 }
}
