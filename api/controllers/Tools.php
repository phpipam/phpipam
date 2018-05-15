<?php

/**
 *	phpIPAM API class to work with tools
 *
 *
 */

class Tools_controller extends Common_api_functions {


	/**
	 * _params provided
	 *
	 * @var mixed
	 * @access public
	 */
	public $_params;

	/**
	 * subcontrollers
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $subcontrollers;

	/**
	 * sort_key for database sorting
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $sort_key;

	/**
	 * identifiers
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $identifiers;

	/**
	 * Database object
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $Database;

	/**
	 * Response
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $Response;

	/**
	 * Master Tools object
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $Tools;

	/**
	 * Main Admin class
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $Admin;

	/**
	 * Main Subnets class
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $Subnets;

	/**
	 * __construct function
	 *
	 * @access public
	 * @param class $Database
	 * @param class $Tools
	 * @param mixed $params		// post/get values
	 * @param class $Response
	 */
	public function __construct($Database, $Tools, $params, $Response) {
		$this->Database = $Database;
		$this->Response = $Response;
		$this->Tools 	= $Tools;
		$this->_params 	= $params;
		// init required objects
		$this->init_object ("Admin", $Database);
		$this->init_object ("Subnets", $Database);
		// define controllers
		$this->define_tools_controllers ();
		$this->define_available_identifiers ();

		// fist validate subcontroller
		$this->validate_subcontroller ();
		// rewrite subcontroller
		$this->rewrite_subcontroller ();

        // set keys if options are not provided
		if($_SERVER['REQUEST_METHOD']!="OPTIONS" && isset($this->_params->controller)) {
            // set valid keys
    		$this->set_valid_keys ($this->_params->id);
            // set sort key
            $this->define_sort_key ();
        }
	}

	/**
	 * Defines available tools (sub)controllers.
	 *
	 *	tools has subcontrollers, defined with id2 parameter
	 *
	 * @access private
	 * @return void
	 */
	private function define_tools_controllers () {
		$this->subcontrollers = array(
		                              	"ipTags"	  => "tags",
										"devices"     => "devices",
										"deviceTypes" => "device_types",
										"vlans"       => "vlans",
										"vrf"         => "vrfs",
										"nameservers" => "nameservers",
										"scanAgents"  => "scanagents",
										"locations"   => "locations",
										"racks"       => "racks",
										"nat"         => "nat"
									  );
	}

	/**
	 * Defines available identifiers for subcontrollers
	 *
	 * @access private
	 * @return void
	 */
	private function define_available_identifiers () {
		$this->identifiers = array(
								"ipTags"      => array("id2", "id3"),
								"devices"     => array("id2", "id3"),
								"deviceTypes" => array("id2", "id3"),
								"vlans"       => array("id2", "id3"),
								"vrf"         => array("id2", "id3"),
								"nameservers" => array("id2"),
								"scanAgents"  => array("id2"),
								"locations"   => array("id2", "id3"),
								"racks"       => array("id2", "id3"),
								"nat"         => array("id2", "id3")
								);
	}

	/**
	 * define_sort_key function
	 *
	 * @access private
	 * @return void
	 */
	private function define_sort_key () {
		// deviceTypes
		if ($this->_params->id == "deviceTypes")	{ $this->sort_key = "tid"; }
		elseif ($this->_params->id == "vlans")		{ $this->sort_key = "vlanId"; }
		elseif ($this->_params->id == "vrf")		{ $this->sort_key = "vrfId"; }
		else										{ $this->sort_key = "id"; }
	}







	/**
	 * returns general Controllers and supported methods
	 *
	 * @access public
	 * @return void
	 */
	public function OPTIONS () {
		// validate
		$this->validate_options_request ();

		// get api
		$app = $this->Tools->fetch_object ("api", "app_id", $this->_params->app_id);

		// controllers
		$controllers = array(
						array("rel"=>"sections",	"href"=>"/api/".$_GET['app_id']."/sections/"),
						array("rel"=>"subnets",		"href"=>"/api/".$_GET['app_id']."/subnets/"),
						array("rel"=>"folders",		"href"=>"/api/".$_GET['app_id']."/folders/"),
						array("rel"=>"addresses",	"href"=>"/api/".$_GET['app_id']."/addresses/"),
						array("rel"=>"vlans",		"href"=>"/api/".$_GET['app_id']."/vlan/"),
						array("rel"=>"vrfs",		"href"=>"/api/".$_GET['app_id']."/vrf/"),
						array("rel"=>"nameservers",	"href"=>"/api/".$_GET['app_id']."/tools/nameservers/"),
						array("rel"=>"scanAgents",	"href"=>"/api/".$_GET['app_id']."/tools/scanagents/"),
						array("rel"=>"locations",	"href"=>"/api/".$_GET['app_id']."/tools/locations/"),
						array("rel"=>"racks",	    "href"=>"/api/".$_GET['app_id']."/tools/racks/"),
						array("rel"=>"nat",	        "href"=>"/api/".$_GET['app_id']."/tools/nat/"),
						array("rel"=>"tools",		"href"=>"/api/".$_GET['app_id']."/tools/")
					);
		# Response
		return array("code"=>200, "data"=>array("permissions"=>$this->Subnets->parse_permissions($app->app_permissions), "controllers"=>$controllers));
	}





	/**
	 * fetch tools object
	 *
	 *	structure:
	 *		/tools/{subcontroller}/{identifier}/{parameter}/
	 *
	 *		/tools/id/id2/id3/
	 *
	 *		- {subcontroller}	- defines which tools object to work on
	 *		- {identifier}		- defines id for that object (optional)
	 *		- {parameter}		- additional parameter (optional)
	 *
	 *  Special options:
	 *      - /tools/device_types/{id}/
	 *      - /tools/device_types/{id}/devices/
	 *
	 *      - /tools/vlans/{id}/subnets/
	 *
	 *      - /tools/vrf/{id}/subnets/
	 *
	 *      - /tools/locations/{id}/subnets/
	 *      - /tools/locations/{id}/devices/
	 *      - /tools/locations/{id}/racks/
	 *      - /tools/locations/{id}/ipaddresses/
	 *
	 *      - /tools/racks/{id}/devices/
	 *
	 *      - /tools/nat/{id}/objects/
	 *      - /tools/nat/{id}/objects_full/
	 *
	 * @access public
	 * @return void
	 */
	public function GET () {
		# validate identifiers
		$this->validate_subcontroller_identifier ();

		# all ?
		if (!isset($this->_params->id2)) {
			$result = $this->Tools->fetch_all_objects ($this->_params->id,  $this->sort_key);
			// result
			if($result===false)							{ $this->Response->throw_exception(200, 'No objects found'); }
			else										{ return array("code"=>200, "data"=>$this->prepare_result ($result, "tools/".$this->_params->id, true, false)); }
		}
		# by parameter
		elseif (isset($this->_params->id3)) {
			// devices (for deviceTypes)
			if ($this->_params->id == "deviceTypes" && $this->_params->id3=="devices") {
				// fetch
				$result = $this->Tools->fetch_multiple_objects ("devices", "type", $this->_params->id2, "id", true);
			}
			// vlans
			elseif ($this->_params->id == "vlans" && $this->_params->id3=="subnets") {
				// fetch
				$result = $this->Tools->fetch_multiple_objects ("subnets", "vlanId", $this->_params->id2, "id", true);
                // add gateway
    			if($result!=false) {
    				foreach ($result as $k=>$r) {
        				//gateway
                		$gateway = $this->read_subnet_gateway ($r->id);
                		if ( $gateway!== false) {
                    		$result[$k]->gatewayId = $gateway->id;
                		}
                    	//nameservers
                		$ns = $this->read_subnet_nameserver ($r);
                        if ($ns!==false) {
                            $result[$k]->nameservers = $ns;
                        }
    				}
    			}
			}
			// vrfs
			elseif ($this->_params->id == "vrf" && $this->_params->id3=="subnets") {
				// fetch
				$result = $this->Tools->fetch_multiple_objects ("subnets", "vrfId", $this->_params->id2, "id", true);
                // add gateway
    			if($result!=false) {
    				foreach ($result as $k=>$r) {
                		$gateway = $this->read_subnet_gateway ($r->id);
                		if ( $gateway!== false) {
                    		$result[$k]->gatewayId = $gateway->id;
                		}
    				}
    			}
			}
			// locations
			elseif ($this->_params->id == "locations" && ($this->_params->id3=="subnets" || $this->_params->id3=="racks" || $this->_params->id3=="devices" || $this->_params->id3=="ipaddresses")) {
				// fetch
				$result = $this->Tools->fetch_multiple_objects ($this->_params->id3, "location", $this->_params->id2, "id", true);
			}
			// racks
			elseif ($this->_params->id == "racks" && $this->_params->id3=="devices") {
				// fetch
				$result = $this->Tools->fetch_multiple_objects ($this->_params->id3, "rack", $this->_params->id2, "id", true);
			}
			// nat
			elseif ($this->_params->id == "nat" && ($this->_params->id3=="objects" || $this->_params->id3=="objects_full")) {
    			// fetch nat first
    			$result = $this->Tools->fetch_object ($this->_params->id, $this->sort_key, $this->_params->id2);
                // add objects
    			if($result!=false) {
    				// parse result
    				$result->src = $this->parse_nat_objects ($result->src);
    				$result->dst = $this->parse_nat_objects ($result->dst);
    				// full ?
    				if ($this->_params->id3=="objects_full") {
        				if(sizeof($result->src)>0) {
            				foreach ($result->src as $type=>$arr) {
                				foreach ($arr as $k=>$id) {
                    				unset($result->src[$type][$k]);
                    				$result->src[$type][] = $this->Tools->fetch_object ($type, "id", $id);
                                }
            				}
        				}
        				if(sizeof($result->dst)>0) {
            				foreach ($result->dst as $type=>$arr) {
                				foreach ($arr as $k=>$id) {
                    				unset($result->dst[$type][$k]);
                    				$result->dst[$type][] = $this->Tools->fetch_object ($type, "id", $id);
                                }
            				}
        				}
    				}
    			}
			}
			else {
    			$field = "";
				// id3 can only be addresses
				if ($this->_params->id3 != "addresses")	{ $this->Response->throw_exception(400, 'Invalid parameter'); }
				// define identifier
				if ($this->_params->id == "ipTags") 	{ $field = "state"; }
				elseif ($this->_params->id == "devices"){ $field = "switch"; }
				else									{ $this->Response->throw_exception(400, 'Invalid parameter'); }
				// fetch
				$result = $this->Tools->fetch_multiple_objects ("ipaddresses", $field, $this->_params->id2, $this->sort_key, true);
			}
			// result
			if($result===false)							{ $this->Response->throw_exception(200, 'No objects found'); }
			else										{ return array("code"=>200, "data"=>$this->prepare_result ($result, "tools/".$this->_params->id, true, true)); }

		}
		# by id
		else {
			// numeric
			if(!is_numeric($this->_params->id2)) 		{ $this->Response->throw_exception(400, 'Identifier must be numeric'); }

			$result = $this->Tools->fetch_object ($this->_params->id, $this->sort_key, $this->_params->id2);
			// result
			if($result===false)							{ $this->Response->throw_exception(200, 'No objects found'); }
			else										{ return array("code"=>200, "data"=>$this->prepare_result ($result, "tools/".$this->_params->id, true, false)); }
		}
	}





	/**
	 * Creates new tools object
	 *
	 *	required parameters:
	 *		id {subcontroller}
	 *
	 *		/tools/{subcontroller}/
	 *
	 * @access public
	 * @return void
	 */
	public function POST () {
		# vlans, vrfs
		if ($this->_params->id=="vlans" || $this->_params->id=="vrf")
													{ $this->Response->throw_exception(400, 'Please use '.$this->_params->id.' controller'); }

		# remap keys
		$this->remap_keys ();

		# Get coordinates if locations
		if($this->_params->id=="locations") {
			$values = $this->format_location ();
		}

		# check for valid keys
		$values = $this->validate_keys ();

		# validations
		$this->validate_post_patch ();

		# only 1 parameter ?
		if (sizeof($values)==1)						{ $this->Response->throw_exception(400, 'No parameters'); }

		# execute update
		if(!$this->Admin->object_modify ($this->_params->id, "add", "", $values))
													{ $this->Response->throw_exception(500, $this->_params->id." object creation failed"); }
		else {
			//set result
			return array("code"=>201, "data"=>$this->_params->id." object created", "id"=>$this->Admin->lastId, "location"=>"/api/".$this->_params->app_id."/tools/".$this->_params->id."/".$this->Admin->lastId."/");
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
	 * Updates tools object
	 *
	 * @access public
	 * @return void
	 */
	public function PATCH () {
		# vlans, vrfs
		if ($this->_params->id=="vlans" || $this->_params->id=="vrf")
													{ $this->Response->throw_exception(400, 'Please use '.$this->_params->id.' controller'); }
		# remap keys
		$this->remap_keys ();

		# verify object
		$this->validate_tools_object ();

		# validations
		$this->validate_post_patch ();

		# rewrite keys - id2 must become id and unset
		$table_name = $this->_params->id;
		$this->_params->id = $this->_params->id2;
		unset($this->_params->id2);

		# validate and prepare keys
		$values = $this->validate_keys ();

		# only 1 parameter ?
		if (sizeof($values)==1)						{ $this->Response->throw_exception(400, 'No parameters'); }

		# execute update
		if(!$this->Admin->object_modify ($table_name, "edit",  $this->sort_key, $values))
													{ $this->Response->throw_exception(500, $table_name." object edit failed"); }
		else {
			//set result
			return array("code"=>200, "message"=>$table_name." object updated");
		}
	}





	/**
	 * Deletes existing vlan
	 *
	 * @access public
	 * @return void
	 */
	public function DELETE () {
		# vlans, vrfs
		if ($this->_params->id=="vlans" || $this->_params->id=="vrf")
													{ $this->Response->throw_exception(400, 'Please use '.$this->_params->id.' controller'); }

		# verify object
		$this->validate_tools_object ();

		# set variables for delete
		$values = array();
		$values[$this->sort_key] = $this->_params->id2;

		# execute delete
		if(!$this->Admin->object_modify ($this->_params->id, "delete",  $this->sort_key, $values))
													{ $this->Response->throw_exception(500, $this->_params->id." object delete failed"); }
		else {
			// set update field
			if ($this->_params->id == "devices")	{ $update_field = "switch"; }
			elseif ($this->_params->id == "ipTags")	{ $update_field = "state"; }

			// delete all references
			if (isset($update_field))
			$this->Admin->remove_object_references ("ipaddresses", $update_field, $this->_params->id2);

			// set result
			return array("code"=>200, "message"=>$this->_params->id." object deleted");
		}
	}










	/* @validations ---------- */

	/**
	 * Validates subcontroller
	 *
	 * @access private
	 * @return void
	 */
	private function validate_subcontroller () {
		// not options
		if($_SERVER['REQUEST_METHOD']!=="OPTIONS") {
    		if (!in_array($this->_params->id, @$this->subcontrollers))			{ $this->Response->throw_exception(400, "Invalid subcontroller"); }
		}
	}

	/**
	 * Validates identifier for subcontroller
	 *
	 * @access private
	 * @return void
	 */
	private function validate_subcontroller_identifier () {
		// id3
		if (isset($this->_params->id3)) {
			if(!in_array("id3", $this->identifiers[$this->_params->id]))	{ $this->Response->throw_exception(400, "Invalid subcontroller identifier"); }
		}
		// id2
		if (isset($this->_params->id2)) {
			if(!in_array("id2", $this->identifiers[$this->_params->id]))	{ $this->Response->throw_exception(400, "Invalid subcontroller identifier"); }
		}
	}

	/**
	 * Rewrites id (tags -> ipTags) to match database fields
	 *
	 * @access private
	 * @return void
	 */
	private function rewrite_subcontroller () {
		$this->_params->id = array_search($this->_params->id, $this->subcontrollers);
	}

	/**
	 * Validates that tools object exists.
	 *
	 * @access private
	 * @return void
	 */
	private function validate_tools_object () {
		if ($this->Tools->fetch_object ($this->_params->id, $this->sort_key, $this->_params->id2)===false)
																			{ $this->Response->throw_exception(400, "Invalid identifier"); }
	}

	/**
	 * Validations for post and patch
	 *
	 * @access private
	 * @return void
	 */
	private function validate_post_patch () {
		$this->validate_device_type ();
		$this->validate_ip ();
	}

	/**
	 * Validates device type
	 *
	 * @access private
	 * @return void
	 */
	private function validate_device_type () {
		if ($this->_params->id == "devices" && isset($this->_params->type)) {
			// numeric
			if (!is_numeric($this->_params->type))							{ $this->Response->throw_exception(400, "Invalid devicetype identifier"); }
			// check
			if ($this->Tools->fetch_object ("deviceTypes", "tid", $this->_params->type)===false)
																			{ $this->Response->throw_exception(400, "Device type does not exist"); }
		}
	}

	/**
	 * Validates IP address
	 *
	 * @access private
	 * @return void
	 */
	private function validate_ip () {
		if (isset($this->_params->ip_addr)) {
			// check
			if(strlen($err = $this->Subnets->verify_cidr_address($this->_params->ip_addr."/32"))>1)
																			{ $this->Response->throw_exception(400, $err); }

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
	 * @param result $obj
	 * @return void
	 */
	private function read_subnet_nameserver ($result) {
    	return $this->Tools->fetch_object ("nameservers", "id", $result->nameserverId);
	}

	/**
	 * Parses NAT objects into array.
	 *
	 * @access private
	 * @param json $obj
	 * @return array
	 */
	private function parse_nat_objects ($obj) {
    	if($this->Tools->validate_json_string($obj)!==false) {
        	return(json_decode($obj, true));
    	}
    	else {
        	return array ();
    	}
	}

	/**
	 * Get latlng from Google
	 *
	 * @method format_location
	 * @return [type]          [description]
	 */
	private function format_location () {
		if((strlen(@$this->_params->lat)==0 || strlen(@$this->_params->long)==0) && strlen(@$this->_params->address)>0) {
            $latlng = $this->Tools->get_latlng_from_address ($this->_params->address);
            if($latlng['lat']!=NULL && $latlng['lng']!=NULL) {
                $this->_params->lat  = $latlng['lat'];
                $this->_params->long = $latlng['lng'];
            }
		}
	}
}
