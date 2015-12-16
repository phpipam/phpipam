<?php

/**
 *	phpIPAM API class to work with Addresses
 *
 *
 */
class Addresses_controller extends Common_api_functions  {

	/* public variables */
	public $_params;

	/* object holders */
	protected $Database;			// Database object
	protected $Sections;			// Sections object
	protected $Response;			// Response handler
	protected $Tools;				// Tools object
	protected $Subnets;				// Subnets object


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
		$this->init_object ("Subnets", $Database);
		$this->init_object ("Addresses", $Database);
		// set valid keys
		$this->set_valid_keys ("ipaddresses");
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
		$result['methods'] = array(
								array("href"=>"/api/addresses/".$this->_params->app_id."/", 	"methods"=>array(array("rel"=>"options", "method"=>"OPTIONS"))),
								array("href"=>"/api/addresses/".$this->_params->app_id."/{id}/","methods"=>array(array("rel"=>"read", 	"method"=>"GET"),
																												 array("rel"=>"create", "method"=>"POST"),
																												 array("rel"=>"update", "method"=>"PATCH"),
																												 array("rel"=>"delete", "method"=>"DELETE"))),
							);
		# result
		return array("code"=>200, "data"=>$result);
	}





	/**
	 * Read address functions
	 *
	 *	identifiers can be:
	 *		- {id}
	 *		- {id}/ping/					// pings address
	 *		- /search/{ip_address}/			// searches for addresses in database, returns multiple if found
	 *		- custom_fields
	 *		- tags							// all tags
	 *		- tags/{id}/					// specific tag
	 *		- tags/{id}/addresses			// returns all addresses that are tagged with this tag ***if subnetId is provided it will be filtered to specific subnet
	 *
	 * @access public
	 * @return void
	 */
	public function GET () {
		// subnet Id > read all addresses in subnet
		if($this->_params->id=="custom_fields") {
			// check result
			if(sizeof($this->custom_fields)==0)			{ $this->Response->throw_exception(404, 'No custom fields defined'); }
			else										{ return array("code"=>200, "data"=>$this->custom_fields); }
		}
		// tags
		elseif($this->_params->id=="tags") {
			// validate
			$this->validate_tag ();
			// all addresses with tag
			if (@$this->_params->id3=="addresses") {
				// fetch
				$result = $this->Tools->fetch_multiple_objects ("ipaddresses", "state", $this->_params->id2);

				// filter by subnetId
				if ($result!==false) {
					if(isset($this->_params->subnetId)) {
						if (is_numeric($this->_params->subnetId)) {
							// filter
							foreach ($result as $k=>$v) {
								if ($v->subnetId != $this->_params->subnetId) {
									unset($result[$k]);
								}
							}
							// any left
							if (sizeof($result)==0) {
								$result = false;
							}
						}
					}
				}

				// result
				if($result===false)						{ $this->Response->throw_exception(404, 'No addresses found'); }
				else									{ return array("code"=>200, "data"=>$this->prepare_result ($result, "addresses", true, false)); }
			}
			// tags
			else {
				// fetch all by tag
				if(isset($this->_params->id2)) {
					// numeric
					if(is_numeric($this->_params->id2)) { $result = $this->Tools->fetch_object ("ipTags", "id", $this->_params->id2); }
					// type
					else 								{ $result = $this->Tools->fetch_multiple_objects ("ipTags", "type", $this->_params->id2); }
				}
				// all tags
				else 									{ $result = $this->Tools->fetch_all_objects ("ipTags"); }

				// result
				if($result===false)						{ $this->Response->throw_exception(404, 'Tag not found'); }
				else									{ return array("code"=>200, "data"=>$this->prepare_result ($result, "addresses/tags", true, false)); }
			}
		}
		// id not set
		elseif (!isset($this->_params->id)) {
														{ $this->Response->throw_exception(400, 'Address ID is required'); }
		}
		// id
		elseif (is_numeric($this->_params->id)) {
			// ping
			if(@$this->_params->id2=="ping") {
				# scan class
				$Scan = new Scan ($this->Database);
				$Scan->ping_set_exit (false);
				// check address
				$this->validate_address_id ();

				// set result
				$result['scan_type'] = $Scan->icmp_type;
				$result['exit_code'] = $Scan->ping_address ($this->old_address->ip_addr);

				// success
				if($result['exit_code']==0) 			{ $Scan->ping_update_lastseen ($this->_params->id); return array("code"=>200, "data"=>$result); }
				else									{ $this->Response->throw_exception(404, "Address offline. Exit code: ".$result['exit_code']."( ".$Scan->ping_exit_explain ($result['exit_code'])." )"); }
			}
			else {
				// fetch
				$result = $this->Addresses->fetch_address ("id", $this->_params->id);
				// check result
				if($result==false)						{ $this->Response->throw_exception(404, "Invalid Id"); }
				else									{ return array("code"=>200, "data"=>$this->prepare_result ($result, $this->_params->controller, true, true)); }
			}
		}
		// ip address ?
		elseif (@$this->_params->id=="search") {
			// validate
			if(!$this->Addresses->validate_address ($this->_params->id2))
														{ $this->Response->throw_exception(404, 'Invalid address'); }
			// search
			$result = $this->Tools->fetch_multiple_objects ("ipaddresses", "ip_addr", $this->Subnets->transform_address ($this->_params->id2, "decimal"));
			// check result
			if($result===false)							{ $this->Response->throw_exception(404, 'Address not found'); }
			else										{ return array("code"=>200, "data"=>$this->prepare_result ($result, $this->_params->controller, true, true)); }
		}
		// false
		else											{  $this->Response->throw_exception(400, "Invalid Id"); }
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
	 * Creates new address
	 *
	 *	required parameters: ip, subnetId
	 *
	 * @access public
	 * @return void
	 */
	public function POST () {
		// remap keys
		$this->remap_keys ();

		// validate ip address - format, proper subnet, subnet/broadcast check
		$this->validate_create_parameters ();

		// check for valid keys
		$values = $this->validate_keys ();

		// transform address to decimal format
		$values['ip_addr'] = $this->Addresses->transform_address($values['ip_addr'] ,"decimal");
		// set action
		$values['action'] = "add";

		# execute
		if(!$this->Addresses->modify_address ($values)) {
			$this->Response->throw_exception(500, "Failed to create address");
		}
		else {
			//set result
			return array("code"=>201, "data"=>"Address created", "location"=>"/api/".$this->_params->app_id."/addresses/".$this->Addresses->lastId."/");
		}
	}





	/**
	 * Updates existing address
	 *
	 *	forbidden parameters: ip, subnetId
	 *
	 * @access public
	 * @return void
	 */
	public function PATCH () {
		// remap keys
		$this->remap_keys ();

		// we dont allow address or subnet change
		if(isset($this->_params->ip_addr))			{ $this->Response->throw_exception(400, "IP address cannot be changed"); }
		if(isset($this->_params->subnetId))			{ $this->Response->throw_exception(400, "Subnet cannot be changed"); }

		// validations
		$this->validate_update_parameters ();

		# check for valid keys
		$values = $this->validate_keys ();
		// add action and id
		$values["id"] = $this->_params->id;

		# we need admin object
		$this->init_object ("Admin", $this->Database);

		# execute
		if(!$this->Admin->object_modify ("ipaddresses", "edit", "id", $values)) {
			$this->Response->throw_exception(500, "Failed to update address");
		}
		else {
			//set result
			return array("code"=>200, "data"=>"Address updated");
		}

	}





	/**
	 * Deletes existing address
	 *
	 *	required parameters: id
	 *
	 * @access public
	 * @return void
	 */
	public function DELETE () {
		// Check for id
		$this->validate_address_id ();

		// set variables for delete
		$values["id"] 	  = $this->_params->id;
		$values["action"] = "delete";

		# execute update
		if(!$this->Addresses->modify_address ($values))
													{ $this->Response->throw_exception(500, "Failed to delete address"); }
		else {
			//set result
			return array("code"=>200, "data"=>"Address deleted");
		}

	}









	/* @validations ---------- */

	/**
	 * Make sure the address exists in database.
	 *
	 * @access private
	 * @return void
	 */
	private function validate_address_id () {
		if(!$this->old_address = $this->Addresses->fetch_address ("id", $this->_params->id)){ $this->Response->throw_exception(404, "Address does not exist"); }
	}

	/**
	 * Validate IP tag
	 *
	 * @access private
	 * @return void
	 */
	private function validate_tag () {
		// numeric
		if(!is_numeric(@$this->_params->id2))												{ $this->Response->throw_exception(400, 'Invalid tag identifier'); }
		// check db
		if (!$this->Tools->fetch_object ("ipTags", "id", $this->_params->id2))				{ $this->Response->throw_exception(404, "Address tag does not exist"); }
	}

	/**
	 * Validates subnet
	 *
	 * @access private
	 * @return void
	 */
	private function validate_subnet () {
		// numberic
		if(!is_numeric($this->_params->subnetId))											{ $this->Response->throw_exception(400, "Subnet Id must be numeric"); }
		// check subnet
		if(is_null($res = $this->Subnets->fetch_subnet ("id", $this->_params->subnetId)))	{ $this->Response->throw_exception(400, "Invalid subnet Id"); }
		else																				{ $this->subnet_details = $res; }
	}

	/**
	 * Validates address on creation
	 *
	 * @access private
	 * @return void
	 */
	private function validate_create_parameters () {
		// validate subnet
		$this->validate_subnet ();

		// validate overlapping
		if($this->Addresses->address_exists ($this->_params->ip_addr, $this->_params->subnetId))	{ $this->Response->throw_exception(400, "IP address already exists"); }

		// fetch subnet
		$subnet = $this->subnet_details;
		// formulate CIDR
		$subnet = $this->Subnets->transform_to_dotted ($subnet->subnet)."/".$subnet->mask;

		// validate address, that it is inside subnet, not subnet/broadcast
		$this->Addresses->verify_address( $this->_params->ip_addr, $subnet, false, true );

		// validate device
		if(isset($this->_params->switch)) {
		if($this->Tools->fetch_object("devices", "vlanId", $this->_params->switch)===false)	{ $this->Response->throw_exception(400, "Device does not exist"); } }
		// validate state
		if(isset($this->_params->state)) {
		if($this->Tools->fetch_object("ipTags", "id", $this->_params->state)===false)		{ $this->Response->throw_exception(400, "Tag does not exist"); } }
		else { $this->_params->state = 2; }
	}

	/**
	 * Validation of PATCH parameters
	 *
	 * @access private
	 * @return void
	 */
	private function validate_update_parameters () {
		// make sure address exists
		$this->validate_address_id ();

		// validate device
		if(isset($this->_params->switch)) {
		if($this->Tools->fetch_object("devices", "vlanId", $this->_params->switch)===false)	{ $this->Response->throw_exception(400, "Device does not exist"); } }
		// validate state
		if(isset($this->_params->state)) {
		if($this->Tools->fetch_object("ipTags", "id", $this->_params->state)===false)		{ $this->Response->throw_exception(400, "Tag does not exist"); } }
		else { $this->_params->state = 2; }
	}
}

?>