<?php

/**
 *	phpIPAM API class to work with Addresses
 *
 *
 */
class Addresses_controller extends Common_api_functions  {

	/**
	 * Saves details of current subnet
	 *
	 * @var mixed
	 * @access private
	 */
	private $subnet_details;

	/**
	 * Old address values
	 *
	 * @var mixed
	 * @access private
	 */
	private $old_address;


	/**
	 * __construct function
	 *
	 * @access public
	 * @param PDO_Database $Database
	 * @param Tools $Tools
	 * @param API_params $params
	 * @param Response $response
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
		$result = array();
		$result['methods'] = array(
								array("href"=>"/api/".$this->_params->app_id."/addresses/", 	"methods"=>array(array("rel"=>"options", "method"=>"OPTIONS"))),
								array("href"=>"/api/".$this->_params->app_id."/addresses/{id}/","methods"=>array(array("rel"=>"read", 	"method"=>"GET"),
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
	 *		- /								             // returns all addresses in all sections
	 *		- /addresses/{id}/
	 *		- /addresses/{id}/ping/					     // pings address
	 *      - /addresses/{ip}/{subnetId}/                // Returns address from subnet
	 *		- /addresses/search/{ip_address}/			 // searches for addresses in database, returns multiple if found
	 *		- /addresses/search_hostname/{hostname}/     // searches for addresses in database by hostname, returns multiple if found
	 *		- /addresses/search_linked/{value}/          // searches in database for addresses linked by customer defined "Link addresses" field, returns multiple if found
	 *		- /addresses/search_hostbase/{hostbase}/     // searches for addresses by leading substring (base) of hostname, returns ordered multiple
	 *		- /addresses/search_mac/{mac}/   		     // searches for addresses by mac, returns ordered multiple
	 *      - /addresses/first_free/{subnetId}/          // returns first available address (subnetId can be provided with parameters)
	 *		- /addresses/custom_fields/                  // custom fields
	 *		- /addresses/tags/						     // all tags
	 *		- /addresses/tags/{id}/					     // specific tag
	 *		- /addresses/tags/{id}/addresses/			 // returns all addresses that are tagged with this tag ***if subnetId is provided it will be filtered to specific subnet
	 *		- /all/							             // returns all addresses in all sections
	 *
	 * @access public
	 * @return void
	 */
	public function GET () {
		// all
		if (!isset($this->_params->id) || $this->_params->id == "all") {
			// fetch all
			$result = $this->Addresses->fetch_all_objects ("ipaddresses");
			// check result
			if ($result===false)						{ $this->Response->throw_exception(500, "Unable to read addresses"); }
			else										{ return array("code"=>200, "data"=>$this->prepare_result($result, "addresses", true, true)); }
		}
		// subnet Id > read all addresses in subnet
		elseif($this->_params->id=="custom_fields") {
			// check result
			if(sizeof($this->custom_fields)==0)			{ $this->Response->throw_exception(404, 'No custom fields defined'); }
			else										{ return array("code"=>200, "data"=>$this->custom_fields); }
		}
		// first free
		elseif($this->_params->id=="first_free") {
    		// check for isFull
    		if(isset($this->_params->subnetId)) {
        		$subnet = $this->Tools->fetch_object ("subnets", "id", $this->_params->subnetId);
            } else {
        		$subnet = $this->Tools->fetch_object ("subnets", "id", $this->_params->id2);
            }
    		if($subnet->isFull==1)                       { $this->Response->throw_exception(404, 'No free addresses found'); }

    		$this->_params->ip_addr = $this->Addresses->get_first_available_address ($subnet->id);
    		// null
    		if ($this->_params->ip_addr==false)          { $this->Response->throw_exception(404, 'No free addresses found'); }
            else                                         { return array("code"=>200, "data"=>$this->Addresses->transform_address ($this->_params->ip_addr, "dotted")); }
		}
		// address search inside predefined subnet
		elseif($this->Tools->validate_ip ($this->_params->id)!==false && isset($this->_params->id2)) {
            // fetch all in subnet
            $result = $this->Tools->fetch_multiple_objects ("ipaddresses", "subnetId", $this->_params->id2);
            if($result!==false) {
            	$result_filtered = "";
                foreach ($result as $k=>$r) {
                    if($r->ip !== $this->_params->id) {
                        unset($result[$k]);
                    }
                    else {
                        $result_filtered = $r;
                    }
                }
                if(sizeof($result)==0)  { $result = false;  }
                else                    { $result = $result_filtered; }
            }
    		if ($result==false)                          { $this->Response->throw_exception(404, 'No addresses found'); }
            else                                         { return array("code"=>200, "data"=>$result); }
		}
		// tags
		elseif($this->_params->id=="tags") {
			// all addresses with tag
			if (@$this->_params->id3=="addresses") {
				// validate
				$this->validate_tag ();
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
					// validate
					$this->validate_tag ();
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
		// Search all addresses matching custom link_field field's value
		elseif($this->_params->id=="search_linked") {
			//
			$result = $this->Tools->fetch_multiple_objects ("ipaddresses", $this->Addresses->Log->settings->link_field, $this->_params->id2);
			// result
				if($result===false)						{ $this->Response->throw_exception(404, 'No addresses found'); }
				else									{ return array("code"=>200, "data"=>$this->prepare_result ($result, "addresses", true, false)); }
		}
		//
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
				$result = array();
				$result['scan_type'] = $Scan->icmp_type;
				$result['exit_code'] = $Scan->ping_address ($this->old_address->ip);
				$result['result_code'] = $Scan->ping_exit_explain ($result['exit_code']);
				$result['message'] = $result['exit_code']==0 ? "Address online" : "Address offline";

				// success
				if($result['exit_code']==0) 			{ $Scan->ping_update_lastseen ($this->_params->id); }
				return array("code"=>200, "data"=>$result);
			}
			// changelog
			elseif ($this->_params->id2=="changelog")   {
				return array("code"=>200, "data"=>$this->address_changelog ());
			}
			// default
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
														{ $this->Response->throw_exception(400, 'Invalid address'); }
			// search
			$result = $this->Tools->fetch_multiple_objects ("ipaddresses", "ip_addr", $this->Subnets->transform_address ($this->_params->id2, "decimal"));
			// check result
			if($result===false)							{ $this->Response->throw_exception(404, 'Address not found'); }
			else										{ return array("code"=>200, "data"=>$this->prepare_result ($result, $this->_params->controller, true, true)); }
		}
        // search host ?
        elseif (@$this->_params->id=="search_hostname") {
            $result = $this->Tools->fetch_multiple_objects ("ipaddresses", "hostname", $this->_params->id2);
            // check result
            if($result===false)                         { $this->Response->throw_exception(404, 'Hostname not found'); }
            else                                        { return array("code"=>200, "data"=>$this->prepare_result ($result, $this->_params->controller, false, false));}
        }
        // search host base (initial substring), return sorted by name
        elseif (@$this->_params->id=="search_hostbase") {
            $target = $this->_params->id2."%";
            $result = $this->Tools->fetch_multiple_objects ("ipaddresses", "hostname", $target, "hostname", true, true);
            // check result
            if($result===false)                         { $this->Response->throw_exception(404, 'Host name not found'); }
            else                                        { return array("code"=>200, "data"=>$this->prepare_result ($result, $this->_params->controller, false, false));}
        }
		 elseif (@$this->_params->id=="search_mac") {
            $this->_params->id2 = $this->reformat_mac_address ($this->_params->id2, 1);
            $result = $this->Tools->fetch_multiple_objects ("ipaddresses", "mac", $this->_params->id2, "mac");
            // check result
            if($result===false)                         { $this->Response->throw_exception(404, 'Host name not found'); }
            else                                        { return array("code"=>200, "data"=>$this->prepare_result ($result, $this->_params->controller, false, false));}
		// false
		} else											{  $this->Response->throw_exception(400, "Invalid Id"); }
	}





	/**
	 * Creates new address
	 *
	 *   /addresses/                            // create ip_addr in subnet (required parameters: ip, subnetId)
	 *   /addresses/first_free/{subnetId}/      // will search for first free address in subnet, creating ip_addr
	 *
	 * @access public
	 * @return void
	 */
	public function POST () {
		// remap keys
		$this->remap_keys ();

		// first free
		if($this->_params->id=="first_free")   {
    		// check for isFull
    		if(isset($this->_params->subnetId)) {
        		$subnet = $this->Tools->fetch_object ("subnets", "id", $this->_params->subnetId);
            } else {
        		$subnet = $this->Tools->fetch_object ("subnets", "id", $this->_params->id2);
        		unset($this->_params->id2);
            }
    		if($subnet===false)                          { $this->Response->throw_exception(400, "Invalid subnet identifier"); }
    		if($subnet->isFull==1)                       { $this->Response->throw_exception(404, "No free addresses found (subnet is full)"); }

    		// Obtain exclusive MySQL lock so parallel API requests on the same object are thread safe.
    		$Lock = new LockForUpdate($this->Database, 'subnets', $subnet->id);

    		$this->_params->ip_addr = $this->Addresses->get_first_available_address ($subnet->id);
    		// null
    		if ($this->_params->ip_addr==false)          { $this->Response->throw_exception(404, 'No free addresses found'); }
            else {
                $this->_params->ip_addr = $this->Addresses->transform_address ($this->_params->ip_addr, "dotted");
                $this->_params->subnetId = $subnet->id;
                if(!isset($this->_params->description))  $this->_params->description = "API created";
            }
		}

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
    		if($this->_params->id=="first_free")   {
        	    return array("code"=>201, "message"=>"Address created", "id"=>$this->Addresses->lastId, "location"=>"/api/".$this->_params->app_id."/addresses/".$this->Addresses->lastId."/", "data"=>$this->Addresses->transform_address ($this->_params->ip_addr, "dotted"));
    		}
    		else {
        	    return array("code"=>201, "message"=>"Address created", "id"=>$this->Addresses->lastId, "location"=>"/api/".$this->_params->app_id."/addresses/".$this->Addresses->lastId."/");
    		}
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
		if(isset($this->_params->ip))				{ $this->Response->throw_exception(400, "IP address cannot be changed"); }
		if(isset($this->_params->ip_addr))			{ $this->Response->throw_exception(400, "IP address cannot be changed"); }
		if(isset($this->_params->subnetId))			{ $this->Response->throw_exception(400, "Subnet cannot be changed"); }

		// validations
		$this->validate_update_parameters ();

		# check for valid keys
		$values = $this->validate_keys ();
		// add action and id
		$values["id"] = $this->_params->id;

		# we need admin and addresses object
		$this->init_object ("Admin", $this->Database);
		$this->init_object ("Addresses", $this->Database);

		# append old address details and fill details if not provided - validate_update_parameters fetches $this->old_address
		foreach ($this->old_address as $ok=>$oa) {
			if (!array_key_exists($ok, $values)) {
				if(!is_null($oa)) {
					$values[$ok] = $oa;
				}
			}
		}

		# append action
		$values["action"] = "edit";

		# execute
		if(!$this->Addresses->modify_address ($values)) {
			$this->Response->throw_exception(500, "Failed to update address");
		}
		else {
			//set result
			return array("code"=>200, "message"=>"Address updated");
		}

	}





	/**
	 * Deletes existing address
	 *
	 *	required parameters: id
	 *
 	 *	identifiers can be:
     *
     *      /addresses/{id}                            // Returns address by id
     *      /addresses/{ip}/{subnetId}/                // Deletes address from subnet by ip
	 *
	 * @access public
	 * @return void
	 */
	public function DELETE () {
    	// delete by ip
    	if ($this->Tools->validate_ip ($this->_params->id)!==false && isset($this->_params->id2)) {
        	// find
        	$result = $this->Tools->fetch_multiple_objects ("ipaddresses", "ip_addr", $this->Tools->transform_address($this->_params->id, "decimal"));
        	if($result!==false) {
            	foreach ($result as $k=>$r) {
                	if($r->subnetId != $this->_params->id2) {
                    	unset($result[$k]);
                	}
            	}
        	}
        	if (sizeof($result)==0 || $result===false)   { $this->Response->throw_exception(404, "No addresses found"); }
        	else {
            	// rekey
            	$result = array_values($result);
            	// replace parameters
            	$this->_params->id = $result[0]->id;
        	}
    	}

		// Check for id
		$this->validate_address_id ();

		// set variables for delete
		$values = array();
		$values["id"] 	  = $this->_params->id;
		$values["action"] = "delete";

		// delete pdns records ?
		if(isset($this->_params->remove_dns)) {
			$values['remove_all_dns_records'] = 1;
			$values['hostname']				  = $this->old_address->hostname;
			$values['ip_addr']				  = $this->Tools->transform_address($this->old_address->ip, "dotted");
			$values['PTR']				  	  = $this->old_address->PTR;
			$values['subnetId']				  = $this->old_address->subnetId;
		}

		# execute update
		if(!$this->Addresses->modify_address ($values))
													{ $this->Response->throw_exception(500, "Failed to delete address"); }
		else {
			//set result
			return array("code"=>200, "message"=>"Address deleted");
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
    	// fetch
    	$this->old_address = $this->Addresses->fetch_address ("id", $this->_params->id);
    	// check
		if($this->old_address===false) { $this->Response->throw_exception(404, "Address does not exist"); }
	}

	/**
	 * Validate IP tag
	 *
	 * @access private
	 * @return void
	 */
	private function validate_tag () {
		// numeric
		if(!is_numeric(@$this->_params->id2))									{ $this->Response->throw_exception(400, 'Invalid tag identifier'); }
		// check db
		if (!$this->Tools->fetch_object ("ipTags", "id", $this->_params->id2))	{ $this->Response->throw_exception(404, "Address tag does not exist"); }
	}

	/**
	 * Validates subnet
	 *
	 * @access private
	 * @return void
	 */
	private function validate_subnetId () {
		if(!is_numeric($this->_params->subnetId))
			$this->Response->throw_exception(400, _("SubnetId must be numeric"));

		// check subnet exists
		$res = $this->Subnets->fetch_subnet ("id", $this->_params->subnetId);
		if(!is_object($res))
			$this->Response->throw_exception(404, _("Invalid subnet Id"));

		$this->subnet_details = $res;
	}

	/**
	 * This method will be used if subnetId is not present and will try to
	 * find it in system itself.
	 *
	 * It will only take into consideration subnets that do not have underlying
	 * slave subnets.
	 *
	 * In case more than 1 subnet is found error will be thrown.
	 *
	 * @method autosearch_subnet_id
	 *
	 * @return void
	 */
	private function autosearch_subnet_id () {

	}

	/**
	 * Validates address on creation
	 *
	 * @access public
	 * @return void
	 */
	public function validate_create_parameters () {
		// validate subnet
		$this->validate_subnetId ();

		// validate overlapping
		if($this->Addresses->address_exists ($this->_params->ip_addr, $this->_params->subnetId))
			$this->Response->throw_exception(409, "IP address already exists");

		// check if it is a folder
		if($this->subnet_details->isFolder) {
			if($this->Addresses->validate_address ($this->_params->ip_addr)===false)
				$this->Response->throw_exception(400, "Invalid address");
		}

		$this->validate_create_update_common();
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

		// if no data is present print it
		if(sizeof((array) $this->_params)==3) {
			if(isset($this->_params->app_id) && isset($this->_params->controller) && isset($this->_params->id))
				$this->Response->throw_exception(400, "No data provided");
		}

		$this->validate_create_update_common();
	}

	/**
	 * Validation of POST/PATCH parameters - common checks
	 *
	 * @access private
	 * @return void
	 */
	private function validate_create_update_common () {
		//validate and normalize MAC address
		if(!is_blank($this->_params->mac)) {
			if($this->validate_mac ($this->_params->mac)===false)
				$this->Response->throw_exception(400, "Invalid MAC address");
			// normalize
			$this->_params->mac = $this->reformat_mac_address ($this->_params->mac, 1);
		}

		// validate device
		if(isset($this->_params->switch)) {
			if (!empty($this->_params->switch) && !is_numeric($this->_params->switch)) {
				$this->Response->throw_exception(400, "Device does not exist");
			}
			if($this->_params->switch > 0 && $this->Tools->fetch_object("devices", "id", $this->_params->switch)===false)
				$this->Response->throw_exception(400, "Device does not exist");
		}

		// validate state
		if(isset($this->_params->state)) {
			if (!empty($this->_params->state) && !is_numeric($this->_params->state))
				$this->Response->throw_exception(400, "Invalid state");
			if($this->Tools->fetch_object("ipTags", "id", $this->_params->state)===false)
				$this->Response->throw_exception(400, "Tag does not exist");
		} else {
			$this->_params->state = 2;
		}
	}

	/**
	 * Get changelog for subnet
	 * @method subnet_changelog
	 * @return [type]
	 */
	private function address_changelog () {
		// Check for id
		$this->validate_address_id ();
		// get changelog
		$Log = new Logging ($this->Database);
		$clogs = $Log->fetch_changlog_entries("ip_addr", $this->_params->id, true);
		// reformat
		$clogs_formatted = [];
		// loop
		if (is_array($clogs)) {
			if (sizeof($clogs)>0) {
				foreach ($clogs as $l) {
					// diff to array
					$l->cdiff = explode("\r\n", str_replace(["[","]"], "", trim($l->cdiff)));
					// save
					$clogs_formatted[] = [
						"user"   => $l->real_name,
						"action" => $l->caction,
						"result" => $l->cresult,
						"date"   => $l->cdate,
						"diff"   => $l->cdiff,
					];
				}
			}
		}
		// result
		if(sizeof($clogs_formatted)>0) 	{ return $clogs_formatted; }
		else 							{ $this->Response->throw_exception(404, "No changelogs found"); }
	}
}
