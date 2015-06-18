<?php

/**
 *	phpIPAM API class to work with subnets
 *
 *
 */
class Subnets_controller extends Common_functions {

	/* public variables */
	public $_params;

	/* protected variables */
	protected $valid_keys;

	/* object holders */
	protected $Database;		// Database object
	protected $Response;		// Response handler
	protected $Subnets;			// Subnets object
	protected $Addresses;		// Addresses object
	protected $Tools;			// Tools object


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
		$this->init_object ("Subnets", $Database);
		$this->init_object ("Addresses", $Database);
		// set valid keys
		$this->set_valid_keys ("subnets");
	}





	/**
	 * Returns json encoded options
	 *
	 * @access public
	 * @return void
	 */
	public function OPTIONS () {
		// methods
		$result['methods'] = array(
								array("href"=>"/api/subnets/".$this->_params->app_id."/",		"method"=>"OPTIONS"),
								array("href"=>"/api/subnets/".$this->_params->app_id."/{id}/", 	"method"=>"GET"),
								array("href"=>"/api/subnets/".$this->_params->app_id."/{id}/", 	"method"=>"POST"),
								array("href"=>"/api/subnets/".$this->_params->app_id."/{id}/", 	"method"=>"PATCH"),
								array("href"=>"/api/subnets/".$this->_params->app_id."/{id}/", 	"method"=>"DELETE")
							);
		# result
		return array("code"=>200, "data"=>$result);
	}




	/**
	 * Creates new subnet
	 *
	 *	required params : subnet, mask, name
	 *	optional params : all subnet values
	 *
	 *
	 * @access public
	 * @return void
	 */
	public function POST () {
		# add required parameters
		if(!isset($this->_params->isFolder)) { $this->_params->isFolder = null; }
		elseif($this->_params->isFolder==1)	 { unset($this->_params->subnet, $this->_params->mask); }

		# validate parameters
		$this->validate_create_parameters ();

		# check for valid keys
		$values = $this->validate_keys ();

		# transform subnet to decimal format
		$values['subnet'] = $this->Addresses->transform_address($values['subnet'] ,"decimal");

		# execute
		if(!$this->Subnets->modify_subnet ("add", $values)) {
			$this->Response->throw_exception(500, "Failed to create subnet");
		}
		else {
			//set result
			return array("code"=>201, "data"=>"Subnet created", "location"=>"/api/".$this->_params->app_id."/subnets/".$this->Subnets->lastInsertId."/");
		}
		# return
		return $result;

	}





	/**
	 * Reads subnet functions
	 *
	 *	Identifier can be:
	 *		- {id}
	 *		- custom_fields				// returns custom fields
	 *		- {subnet}					// subnets in CIDR format
	 *		- {id}/usage/				// returns subnet usage
	 *		- {id}/first_free/			// returns first available address in subnet
	 *		- {id}/slaves/ 				// returns all immediate slave subnets
	 *		- {id}/slaves_recursive/ 	// returns all slave subnets recursively
	 *
	 * @access public
	 * @return void
	 */
	public function GET () {
		// check if id2 is set ?
		if(isset($this->_params->id2)) {
			// validate id
			$this->validate_subnet_id ();
			// slaves
			if($this->_params->id2=="slaves") {
				$result = $this->read_subnet_slaves ();
				// check result
				if($result==NULL)						{ return array("code"=>200, NULL); }
				else									{ return array("code"=>200, "data"=>$this->prepare_result ($result, null, true, true)); }
			}
			// slaves-recursive
			elseif ($this->_params->id2=="slaves_recursive") {
				$result = $this->read_subnet_slaves_recursive ();
				// check result
				if($result==NULL)						{ return array("code"=>200, NULL); }
				else									{ return array("code"=>200, "data"=>$this->prepare_result ($result, null, true, true)); }
			}
			// usage
			elseif ($this->_params->id2=="usage") 		{ return array("code"=>200, "data"=>$this->subnet_usage ()); }
			// first available address
			elseif ($this->_params->id2=="first_free") { return array("code"=>200, "data"=>$this->subnet_first_free ());  }
			// fail
			else										{ $this->Response->throw_exception(400, 'Invalid request'); }
		}
		// custom fields
		elseif ($this->_params->id=="custom_fields") {
			// check result
			if(sizeof($this->custom_fields)==0)			{ return array("code"=>200, NULL); }
			else										{ return array("code"=>200, "data"=>$this->custom_fields); }
		}
		// id
		elseif (is_numeric($this->_params->id)) {
			$result = $this->read_subnet ();
			// check result
			if($result==NULL)							{ return array("code"=>200, NULL); }
			else										{ return array("code"=>200, "data"=>$this->prepare_result ($result, "Subnets", true, true)); }
		}
		// false
		else 											{ $this->Response->throw_exception(400, 'Invalid Id'); }
	}






	/**
	 * Updates existing subnet
	 *
	 *	required params : id
	 *	forbidden params : subnet, mask
	 *
	 *	if id2 is present than execute:
	 *		- {id}/truncate/
	 *		- {id}/resize/
	 *		- {id}/split/
	 *
	 * @access public
	 * @return void
	 */
	public function PATCH () {
		// Check for id
		$this->validate_subnet_id ();

		// check if id2 is set > additional methods
		if(isset($this->_params->id2)) {
			// truncate
			if($this->_params->id2=="truncate") 		{ return $this->subnet_truncate (); }
			// resize
			elseif($this->_params->id2=="resize") 		{ return $this->subnet_resize (); }
			// split
			elseif($this->_params->id2=="split") 		{ return $this->subnet_split (); }
			// error
			else										{ $this->Response->throw_exception(400, 'Invalid parameters'); }
		}
		// ok, normal update
		else {
			// new section
			if(isset($this->_params->sectionId)) 		{ $this->validate_section (); }

			// if subnet is provided die
			if(isset($this->_params->subnet))			{ $this->Response->throw_exception(400, 'Subnet cannot be changed'); }
			if(isset($this->_params->mask))				{ $this->Response->throw_exception(400, 'To change mask use resize method'); }

			# check for valid keys
			$values = $this->validate_keys ();
			// add id
			$values["id"] = $this->_params->id;

			# execute update
			if(!$this->Subnets->modify_subnet ("edit", $values))
														{ $this->Response->throw_exception(500, 'Subnet update failed'); }
			else {
				return array("code"=>200, "data"=>"Subnet updated");
			}
		}
	}

	/**
	 * Alias function for edit
	 *
	 * @access public
	 * @return void
	 */
	public function PUT () {
		return $this->PATCH ();
	}





	/**
	 * Deletes existing subnet along with and addresses
	 *
	 *	required params : id
	 *
	 * @access public
	 * @return void
	 */
	public function DELETE () {
		# Check for id
		if(!isset($this->_params->id))				{ $this->Response->throw_exception(400, "Subnet id required"); }
		# check that subnet exists
		if(sizeof($this->Subnets->fetch_subnet ("id", $this->_params->id))==0)
													{ $this->Response->throw_exception(400, "Invalid subnet Id"); }

		# set variables for delete
		$values["id"] = $this->_params->id;

		# execute update
		if(!$this->Subnets->modify_subnet ("delete", $values))
													{ $this->Response->throw_exception(500, "Failed to delete subnet"); }
		else {
			//set result
			return array("code"=>200, "data"=>"Subnet deleted");
		}
	}





	/**
	 * Truncates subnet
	 *
	 *	required params : id
	 *
	 * @access private
	 * @return void
	 */
	private function subnet_truncate () {
		// Check for id
		$this->validate_subnet_id ();
		// ok, try to truncate
		$this->Subnets->modify_subnet ("truncate", (array) $this->_params);
		//set result
		return array("code"=>200, "data"=>"Subnet truncated");
	}





	/**
	 * Resize subnet
	 *
	 *	required params : id, mask
	 *
	 * @access private
	 * @return void
	 */
	private function subnet_resize () {
		// Check for id
		$this->validate_subnet_id ();

		// validate input parmeters
		if(!isset($this->_params->mask))				{ $this->Response->throw_exception(400, "Subnet mask not provided"); }

		// fetch old subnet
		$old_subnet = $this->Subnets->fetch_subnet ("id", $this->_params->id);

		// validate resizing
		$this->Subnets->verify_subnet_resize ($old_subnet->subnet, $this->_params->mask, $this->_params->id, $old_subnet->vrfId, $old_subnet->masterSubnetId, $old_subnet->mask);

		# set update values
		$values = array("id"=>$this->_params->id,
						"mask"=>$this->_params->mask
						);
		$this->Subnets->modify_subnet ("resize", $values);

		//set result
		return array("code"=>200, "data"=>"Subnet truncated");
	}





	/**
	 * Splits existing network into new networks
	 *
	 *	required params : id, number
	 *	optional params : group (default yes), strict (default yes), prefix
	 *
	 * @access private
	 * @return void
	 */
	private function subnet_split () {
		// Check for id
		$this->validate_subnet_id ();

		// validate input parmeters
		if(!is_numeric($this->_params->number))			{ $this->Response->throw_exception(400, "Invalid number of new subnets"); }
		if(!isset($this->_params->group))				{ $this->_params->group = "yes"; }
		if(!isset($this->_params->strict))				{ $this->_params->strict = "yes"; }

		// fetch old subnet
		$subnet_old = $this->Subnets->fetch_subnet ("id", $this->_params->id);
		// create new subnets and move addresses
		$this->Subnets->subnet_split ($subnet_old, $this->_params->number, $this->_params->prefix, $this->_params->group, $this->_params->strict);

		//set result
		return array("code"=>200, "data"=>"Subnet splitted");
	}





	/**
	 * Calculates subnet usage
	 *
	 * @access private
	 * @return void
	 */
	private function subnet_usage () {
		# check that section exists
		if(sizeof($subnet = $this->Subnets->fetch_subnet ("id", $this->_params->id))==0)
														{ $this->Response->throw_exception(400, "Subnet does not exist"); }

		# set slaves
		$slaves = $this->Subnets->has_slaves ($this->_params->id) ? true : false;

		# fetch all addresses and calculate usage
		if($slaves) {
			$addresses = $this->Addresses->fetch_subnet_addresses_recursive ($this->_params->id, false);
		} else {
			$addresses = $this->Addresses->fetch_subnet_addresses ($this->_params->id);
		}
		// calculate
		$subnet_usage  = $this->Subnets->calculate_subnet_usage (gmp_strval(sizeof($addresses)), $subnet->mask, $subnet->subnet );		//Calculate free/used etc

		# return
		return $subnet_usage;
	}





	/**
	 * Returns first available address in subnet
	 *
	 * @access private
	 * @return void
	 */
	private function subnet_first_free () {
		// Check for id
		$this->validate_subnet_id ();
		// fetch
		$first = $this->Addresses->get_first_available_address ($this->_params->id, $this->Subnets);
		// available?
		if($first===false)	{ $first = null; }
		else				{ $first = $this->Addresses->transform_to_dotted($first); }

		# return
		return $first;
	}






	/* @helper methods ---------- */

	/**
	 * Fetches subnet by id
	 *
	 * @access private
	 * @return void
	 */
	private function read_subnet ($subnetId = null) {
		// null
		$subnetId = !is_null($subnetId) ? $this->_params->id : $subnetId;
		// fetch
		$result = $this->Subnets->fetch_subnet ("id", $this->_params->id);
		# result
		return sizeof($result)==0 ? false : $result;
	}

	/**
	 * Returns all immediate subnet slaves
	 *
	 * @access private
	 * @return void
	 */
	private function read_subnet_slaves () {
		// fetch
		$result = $this->Subnets->fetch_subnet_slaves ($this->_params->id);
		# result
		return $result===false ? NULL : $result;
	}

	/**
	 * Returns all subnet slaves (recursive)
	 *
	 * @access private
	 * @return void
	 */
	private function read_subnet_slaves_recursive () {
		// get array of ids
		$this->Subnets->fetch_subnet_slaves_recursive ($this->_params->id);
		// fetch all
		foreach($this->Subnets->slaves as $s) {
			$result[] = $this->read_subnet ($s);
		}
		# result
		return $result===false ? NULL : $result;
	}






	/* @validations ---------- */

	/**
	 * Validates create parameters before adding new subnet
	 *
	 *	checks and validations - cidr check, issubnet, mastersubnet, sectionId
	 *
	 * @access private
	 * @return void
	 */
	private function validate_create_parameters () {
		# make sure subnet is in dotted format for checks
		$this->_params->subnet = $this->Addresses->transform_address($this->_params->subnet ,"dotted");

		# cidr check
		$this->validate_cidr ();
		# verify that it is subnet
		$this->validate_network ();
		# verify that master subnet exists
		$this->validate_master_subnet ();
		# verify section
		$this->validate_section ();
		# verify folder
		$this->validate_folder ();
		# verify overlapping
		$this->validate_overlapping ();
	}

	/**
	 * Validates provided CIDR address
	 *
	 * @access private
	 * @return void
	 */
	private function validate_cidr () {
		// not for folder
		if($this->_params->isFolder!=1) {
			if(strlen($err = $this->Subnets->verify_cidr_address($this->_params->subnet."/".$this->_params->mask))>1)
																									{ $this->Response->throw_exception(400, $err); }
		}
	}

	/**
	 * Validates that provided subnet is network and not host
	 *
	 * @access private
	 * @return void
	 */
	private function validate_network () {
		// not for folder
		if($this->_params->isFolder!=1) {
			if(!$this->Addresses->is_network ($this->_params->subnet, $this->_params->mask))		{ $this->Response->throw_exception(400, "Address is not subnet"); }
		}
	}

	/**
	 * Validates master subnet
	 *
	 * @access private
	 * @return void
	 */
	private function validate_master_subnet () {
		// set 0 if not set
		if(!isset($this->_params->masterSubnetId) || $this->_params->masterSubnetId=="0") 			{ $this->_params->masterSubnetId = 0; }
		else {
			// validate subnet
			if(sizeof($this->Subnets->fetch_subnet ("id", $this->_params->masterSubnetId))==0)		{ $this->Response->throw_exception(400, "Master Subnet does not exist (id=".$this->_params->masterSubnetId.")"); }
			// check that it is inside subnet
			else {
				// not fr folders
				if(@$this->_params->isFolder!=1) {
					if(!$this->Subnets->verify_subnet_nesting ($this->_params->masterSubnetId, $this->_params->subnet."/".$this->_params->mask))
																									{ $this->Response->throw_exception(400, "Subnet is not within boundaries of its master subnet"); }
				}
			}
		}
	}

	/**
	 * Validates section
	 *
	 * @access private
	 * @return void
	 */
	private function validate_section () {
		// Section Id must be present and numeric
		if(!isset($this->_params->sectionId))														{ $this->Response->throw_exception(400, "Invalid Section (".$this->_params->sectionId.")"); }
		elseif(!is_numeric($this->_params->sectionId))												{ $this->Response->throw_exception(400, "Section Id must be numeric (".$this->_params->sectionId.")"); }
		else {
			if($this->Tools->fetch_object("sections", "id", $this->_params->sectionId)===false)		{ $this->Response->throw_exception(400, "Section id (".$this->_params->sectionId.") does not exist"); }
		}
	}

	/**
	 * Validates subnet by Id
	 *
	 * @access private
	 * @return void
	 */
	private function validate_subnet_id () {
		// numberic
		if(!is_numeric($this->_params->id))															{ $this->Response->throw_exception(400, "Subnet Id must be numeric (".$this->_params->id.")"); }
		// check subnet
		if(is_null($this->Subnets->fetch_subnet ("id", $this->_params->id)))						{ $this->Response->throw_exception(400, "Invalid subnet Id (".$this->_params->id.")"); }
	}

	/**
	 * Folder validation
	 *
	 * @access private
	 * @return void
	 */
	private function validate_folder () {
		// only fo folders
		if(@$this->_params->isFolder==1) {
			// if parent is set it must be a folder!
			if($this->_params->masterSubnetId!=0) {
				$parent = $this->Subnets->fetch_subnet ("id", $this->_params->masterSubnetId);
				if($parent->isFolder!=1) 															{ $this->Response->throw_exception(400, "Parent is not a folder"); }
			}
		}
	}

	/**
	 * Validates overlapping for newly created subnet
	 *
	 * @access private
	 * @return void
	 */
	private function validate_overlapping () {
		// section details
		$section = $this->Tools->fetch_object ("sections", "id", $this->_params->sectionId);
		if($section===false)																		{ $this->Response->throw_exception(400, "Invalid section Id"); }
		// settings
		$this->settings = $this->Tools->fetch_object ("settings", "id", 1);

		# get master subnet details for folder overrides
		if($this->_params->masterSubnetId!=0)	{
			$master_section = $this->Subnets->fetch_subnet(null, $this->_params->masterSubnetId);
			if($master_section->isFolder==1)	{ $parent_is_folder = true; }
			else								{ $parent_is_folder = false; }
		}
		else 									{ $parent_is_folder = false; }


		// create cidr address
		$cidr = $this->Addresses->transform_address($this->_params->subnet,"dotted")."/".$this->_params->mask;

		// root subnet
		if($this->_params->masterSubnetId==0) {
			// check overlapping
			if($section->strictMode==1 && !$parent_is_folder) {
		    	/* verify that no overlapping occurs if we are adding root subnet only check for overlapping if vrf is empty or not exists! */
		    	$overlap = $this->Subnets->verify_subnet_overlapping ($this->_params->sectionId, $cidr, $this->_params->vrfId);
		    	if($overlap!==false) 																{ $this->Response->throw_exception(400, $overlap); }
			}
		}
		// not root
		else {
		    //disable checks for folders and if strict check enabled
		    if($section->strictMode==1 && !$parent_is_folder ) {
			    //verify that nested subnet is inside root subnet
		        if (!$this->Subnets->verify_subnet_nesting($this->_params->masterSubnetId, $cidr)) 	{ $this->Response->throw_exception(400, "Nested subnet not in root subnet"); }

			    //nested?
		        $overlap = $this->Subnets->verify_nested_subnet_overlapping($this->_params->sectionId, $cidr, $this->_params->vrfId, $this->_params->masterSubnetId);
				if($overlap!==false) 																{ $this->Response->throw_exception(400, $overlap); }
		    }
		}
	}

}

?>