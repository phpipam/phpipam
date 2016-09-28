<?php

/**
 *  phpIPAM API class to work with prefixes
 *
 *  Prefix call example:
 *          GET /api/{app_id}/prefix/{customer_type}/
 *          GET /api/{app_id}/prefix/{customer_type}/{address_type}/
 *          GET /api/{app_id}/prefix/{customer_type}/{address_type}/{mask}/
 *
 *          GET /api/{app_id}/prefix/{customer_type}/address/
 *          GET /api/{app_id}/prefix/{customer_type}/address/{address_type}/
 *          GET /api/{app_id}/prefix/{customer_type}/{address_type}/address/
 *
 *          GET /api/{app_id}/prefix/external_id/{external_identifier_field}/
 *
 *  Parameters are:
 *          customer_type : customer type custom field for subnet - variable can be set
 *          address_type  : IP type (IPv4/IPv6)
 *          mask          : Subnet bitmask (/24, /64) etc
 *
 *
 *  Prefixes will execute the following logic
 *          - Search for subnets that have  {custom_field_name} = {customer_type} custom field
 *          - Order them by {custom_field_orderby}, {custom_field_order_direction}
 *          - Go through subnets, on first match return result.
 *          - On no match return http/404
 *
 *  @version: 0.5
 *  @author: Miha Petkovsek <miha.petkovsek@gmail.com>
 *
 */
class Prefix_controller extends Common_api_functions {

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
    public $custom_fields;

    /**
     * Custom field selector
     *
     *  This will be used to search for subnets that have {$custom_field_name = customer_type}
     *
     *  Example:
     *      custom_field_name = customer_type
     *      custom_field_value = residential
     *
     *
     * (default value: "customer_type")
     *
     * @var string
     * @access private
     */
    private $custom_field_name = "customer_type";

    /**
     * This selector will be used to order found subnets
     *
     * (default value: "subnet")
     *
     * @var string
     * @access private
     */
    private $custom_field_orderby = "subnet";

    /**
     * How to order found subnets
     *
     * (default value: "asc")
     *
     * @var string
     * @access private
     */
    private $custom_field_order_direction = "asc";

    /**
     * Custom field selector
     *
     *  This will be used to search for subnets that have {$custom_field_name_addr = customer_type}
     *
     *  Example:
     *      custom_field_name = customer_type
     *      custom_field_value = residential
     *
     *
     * (default value: "customer_type")
     *
     * @var string
     * @access private
     */
    private $custom_field_name_addr = "customer_address_type";

    /**
     * External identifier to link subnets and addresses with
     *
     * (default value: "csid")
     *
     * @var string
     * @access private
     */
    private $external_identifier_field = "csid";

    /**
     * This selector will be used to order found subnets
     *
     * (default value: "subnet")
     *
     * @var string
     * @access private
     */
    private $custom_field_orderby_addr = "subnet";

    /**
     * How to order found subnets
     *
     * (default value: "asc")
     *
     * @var string
     * @access private
     */
    private $custom_field_order_direction_addr = "asc";

    /**
     * Valida address types
     *
     * (default value: array("IPv4", "IPv6", "v4", "v6", "4", "6"))
     *
     * @var string
     * @access private
     */
    private $valid_address_types = array("IPv4", "IPv6", "v4", "v6", "4", "6");

    /**
     * List of ignored subnet fields
     *
     *  If this field will be provided in response it will be stripped out
     *
     * (default value: array())
     *
     * @var array
     * @access private
     */
    private $ignored_prefix_fields = array(
                                        "linked_subnet",
                                        "firewallAddressObject",
                                        "pingSubnet",
                                        "discoverSubnet",
                                        "DNSrecursive",
                                        "DNSrecords",
                                        "nameserverId",
                                        "scanAgent",
                                        "isFull",
                                        "state",
                                        "threshold",
                                        "showName",
                                        "vrfId",
                                        "allowRequests",
                                        "device",
                                        "permissions",
                                        "vlanId",
                                        "location",
                                        "isFolder",
                                        "editDate"
                                    );

    /**
     * List of ignored addresses fields
     *
     *  If this field will be provided in response it will be stripped out
     *
     * (default value: array())
     *
     * @var array
     * @access private
     */
    private $ignored_addresses_fields = array(
                                        "state",
                                        "deviceId",
                                        "switch",
                                        "port",
                                        "note",
                                        "lastSeen",
                                        "excludePing",
                                        "PTRignore",
                                        "PTR",
                                        "firewallAddressObject",
                                        "editDate"
                                    );

    /**
     * Query type, overrides if addresses are requested
     *
     * (default value: "subnets")
     *
     * @var string
     * @access private
     */
    private $query_type = "subnets";

    /**
     * Show links in responses
     *
     * (default value: false)
     *
     * @var bool
     * @access private
     */
    private $use_links = false;

    /**
     * Address type from request (IPv6/IPv6)
     *
     * (default value: "IPv4")
     *
     * @var string
     * @access private
     */
    private $address_type = "both";

    /**
     * Master subnet selected for first available prefix
     *
     * (default value: false)
     *
     * @var bool
     * @access private
     */
    private $master_subnet = false;

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
     * Subnets controller
     *
     * @var mixed
     * @access protected
     */
    protected $Subnets_controller;

    /**
     * Addresses controller
     *
     * @var mixed
     * @access protected
     */
    protected $Addresses_controller;

    /**
     * Master Subnets object
     *
     * @var mixed
     * @access protected
     */
    protected $Subnets;

    /**
     * Master Addresses object
     *
     * @var mixed
     * @access protected
     */
    protected $Addresses;

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
        $this->Database  = $Database;
        $this->Response  = $Response;
        $this->Tools     = $Tools;
        $this->_params   = $params;
		// set query type
		$this->set_query_type ();
        // set valid keys
        $this->set_valid_keys ("subnets");
        // init required objects
        $this->init_object ("Subnets", $Database);
        // add addresses object
        if ($this->query_type=="address") {
            $this->init_object ("Addresses", $Database);
            // set valid keys
            $this->set_valid_keys ("ipaddresses");
        }
    }

    /**
     * Init Subnets controller object - for POST
     *
     * @access private
     */
    private function init_subnets_controller () {
        // file
        require( dirname(__FILE__) . "/Subnets.php");
		// validate parameters on Subnets_controller
		$this->Subnets_controller = new Subnets_controller ($this->Database, $this->Tools, $this->_params, $this->Response);
    }

    /**
     * Init Addresses controller object - for POST
     *
     * @access private
     */
    private function init_addresses_controller () {
        // file
        require( dirname(__FILE__) . "/Addresses.php");
		// validate parameters on Subnets_controller
		$this->Addresses_controller = new Addresses_controller ($this->Database, $this->Tools, $this->_params, $this->Response);
    }
    /**
     * If addresses is provided as id3 execute addresses search
     *
     *  GET /prefix/{customer_address_type}/address/
     *  GET /prefix/{customer_address_type}/address/4/
     *
     * @access private
     */
    private function set_query_type () {
        // get subnets in address selection query
        if ( $this->_params->id2 == "address" || $this->_params->id2 == "addresses" || $this->_params->id2 == "ip") {
            // set query type
            $this->query_type = "address";
            // type provided ?
            if (isset($this->_params->id3)) {
                $this->_params->id2 = $this->_params->id3;
                // remove address
                unset($this->_params->id3);
            }
            else {
                // remove address
                unset($this->_params->id2);
            }
            // overwrite select parameters
            $this->override_custom_fields ();
        }
        // get single address
        elseif ($this->_params->id3 == "address" || $this->_params->id3 == "addresses" || $this->_params->id3 == "ip") {
            // set query type
            $this->query_type = "address";
            // overwrite select parameters
            $this->override_custom_fields ();
        }
    }

    /**
     * override_custom_fields for address
     *
     * @access private
     */
    private function override_custom_fields () {
        $this->custom_field_name = $this->custom_field_name_addr;
        $this->custom_field_orderby = $this->custom_field_orderby_addr;
        $this->custom_field_order_direction = $this->custom_field_order_direction_addr;
    }





    /**
     * Returns json encoded options and version
     *
     * @access public
     * @return array
     */
    public function OPTIONS () {
        // validate
        $this->validate_options_request ();

        // methods
        $result = array();
        $result['methods'] = array(
                                array("href"=>"/api/".$this->_params->app_id."/sections/",      "methods"=>array(array("rel"=>"options","method"=>"OPTIONS"))),
                                array("href"=>"/api/".$this->_params->app_id."/sections/{id}/", "methods"=>array(array("rel"=>"read",   "method"=>"GET"),
                                                                                                                 array("rel"=>"create", "method"=>"POST")))
                            );
        # result
        return array("code"=>200, "data"=>$result);
    }





    /**
     * GET
     *
     *  /api/{app_id}/prefix/{customer_type}/
     *  /api/{app_id}/prefix/{customer_type}/{address_type}/
     *  /api/{app_id}/prefix/{customer_type}/{address_type}/{mask}/
     *  /api/{app_id}/prefix/external_id/{external_identifier_field}/
     *
     * @access public
     * @return array
     */
    public function GET () {
        // external identifier
        if($this->_params->id == "external_id") {
            // search subnets and addresses
            $subnets   = $this->find_external_id_subnets_addresses ("subnets");
            $addresses = $this->find_external_id_subnets_addresses ("ipaddresses");
            // filter result
            if($subnets!==false) {
                foreach ($subnets as $k=>$s) {
                    $subnets[$k] = $this->filter_prefix_result ($s);
                }
                // prepare result
                $subnets = $this->prepare_result ($subnets, "subnets", $this->use_links, true);
            }
            // filter result
            if($addresses!==false) {
                foreach ($addresses as $k=>$s) {
                    $addresses[$k] = $this->filter_addresses_result ($s);
                }
                // prepare result
                $addresses = $this->prepare_result ($addresses, "addresses", $this->use_links, true);
            }
            // result
            if($subnets===false && $addresses===false)  { $this->Response->throw_exception(404, "No objects found"); }
            elseif($addresses===false)                  { return array("code"=>200, "data"=>array("subnets"=>$subnets)); }
            elseif($subnets===false)                    { return array("code"=>200, "data"=>array("addresses"=>$addresses)); }
            else                                        { return array("code"=>200, "data"=>array("subnets"=>$subnets, "addresses"=>$addresses)); }
        }
        // get all subnets involved in querying
        elseif(!isset($this->_params->id3)) {
            // validate requested custom field
            $this->validate_request_parameters_custom_field ();
            // set address type
            if(isset($this->_params->id2)) {
                // set address type
                $this->set_address_type ();
            }

            // search for subnets
            $subnets = $this->search_custom_field_name_subnets ();

            // check result
            if($subnets===false)        { $this->Response->throw_exception(404, "No master subnets found"); }
            else {
                // filter
                foreach ($subnets as $k=>$s) {
                    $subnets[$k] = $this->filter_prefix_result ($s);
                    // append usage
                    if(@$this->_params->usage=="true") {
                        $subnets[$k]->usage = $this->calculate_subnet_usage ($s->id);
                    }
                }
                return array("code"=>200, "data"=>$this->prepare_result ($subnets, "subnets", $this->use_links, true));
            }
        }
        // get prefix
        else {
            // set address type
            $this->set_address_type ();
            // validate all parameters
            $this->validate_all_request_parameters ();
            // search for subnets based on custom field
            $subnets = $this->search_custom_field_name_subnets ();
            // find first subnet or address
            if($this->query_type == "address") {
                $available = $this->find_first_available_address ($subnets);
                $data_type = "ip";
            }
            else {
                $available = $this->find_first_available_subnet ($subnets);
                $data_type = "data";
            }

            // response
            if($available===false)      { $this->Response->throw_exception(404, "No $this->query_type found"); }
            else                        { return array("code"=>200, $data_type=>$available); }
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
     * Creates new prefix or address, based on input parameters
     *
     *  /api/{app_id}/prefix/{customer_type}/{address_type}/{mask}/?description=customer1
     *  /api/{app_id}/prefix/{customer_type}/{address_type}/address/?description=customer1
     *
     * @access public
     * @return array
     */
    public function POST () {
        // validate parameters
        $this->validate_all_request_parameters ();
        // set address type
        $this->set_address_type ();
        // search for subnets based on custom field
        $subnets = $this->search_custom_field_name_subnets ();
        // subnets or addresses
        return $this->query_type == "address" ? $this->POST_ADDRESS ($subnets) : $this->POST_SUBNET ($subnets);
    }

    /**
     * Creates new subnet
     *
     *  /api/{app_id}/prefix/{customer_type}/{address_type}/{mask}/?description=customer1
     *
     * @access private
     * @param mixed $subnets
     * @return array
     */
    private function POST_SUBNET ($subnets) {
        // find first subnet
        $available = $this->find_first_available_subnet ($subnets);

        // found any
        if($available===false)          { $this->Response->throw_exception(404, "No subnets found"); }
        else {
            // parse avilable
    		$subnet_tmp = explode("/", $available);
            // set params
    		$this->_params->subnet          = $subnet_tmp[0];
    		$this->_params->mask            = $subnet_tmp[1];
    		$this->_params->sectionId       = $this->master_subnet->sectionId;
    		$this->_params->masterSubnetId  = $this->master_subnet->id;
    		$this->_params->permissions     = $this->master_subnet->permissions;
    		unset($this->_params->id2, $this->_params->id3);
            // description
            if(!isset($this->_params->description))    { $this->_params->description = "Prefix controller autocreated"; }
        }
        // check for valid keys
        $values = $this->validate_keys ();

        // validate
        $this->init_subnets_controller ();
        $this->Subnets_controller->validate_create_parameters ();

        // transform subnet to decimal format
        $values['subnet'] = $this->Tools->transform_address ($values['subnet'], "decimal");
 		// execute
		if(!$this->Subnets->modify_subnet ("add", $values)) {
			$this->Response->throw_exception(500, "Failed to create subnet");
		}
		else {
			//set result
			return array("code"=>201, "message"=>"Subnet created", "id"=>$this->Subnets->lastInsertId, "data"=>$this->Tools->transform_address ($values['subnet'], "dotted")."/".$values['mask'], "location"=>"/api/".$this->_params->app_id."/subnets/".$this->Subnets->lastInsertId."/");
		}
    }

    /**
     * Creates new address
     *
     *  /api/{app_id}/prefix/{customer_type}/{address_type}/address/?description=customer1.
     *
     * @access private
     * @param mixed $subnets
     * @return array
     */
    private function POST_ADDRESS ($subnets) {
        // find first subnet
        $available = $this->find_first_available_address ($subnets);

		// remap keys
		$this->remap_keys ();

        // found any
        if($available===false)          { $this->Response->throw_exception(404, "No addresses found"); }
        else {
            // set params
    		$this->_params->ip_addr         = $this->Tools->transform_address ($available, "dotted");
    		$this->_params->subnetId        = $this->master_subnet->id;
    		unset($this->_params->id2, $this->_params->id3);
            // description, state
            if(!isset($this->_params->description))    { $this->_params->description = "Prefix controller autocreated"; }
            if(!isset($this->_params->state))          { $this->_params->state = 2; }
        }
        // check for valid keys
        $values = $this->validate_keys ();

        // validate
        $this->init_addresses_controller ();
        $this->Addresses_controller->validate_create_parameters ();

		// set action
		$values['action'] = "add";

		# execute
		if(!$this->Addresses->modify_address ($values)) {
			$this->Response->throw_exception(500, "Failed to create address");
		}
		else {
    		//set result
            return array("code"=>201, "message"=>"Address created", "id"=>$this->Addresses->lastId, "subnetId"=>$this->master_subnet->id, "ip"=>$this->Addresses->transform_address ($this->_params->ip_addr, "dotted"), "location"=>"/api/".$this->_params->app_id."/addresses/".$this->Addresses->lastId."/");
		}
    }




    /**
     * PATCH method
     *
     *  Not needed
     *
     * @access public
     * @return void
     */
    public function PATCH () {
        $this->Response->throw_exception(501, "Method not imeplemented");
    }

    /**
     * DELETE method
     *
     *  Not needed
     *
     * @access public
     * @return void
     */
    public function DELETE () {
        $this->Response->throw_exception(501, "Method not imeplemented");
    }







    /**
     * This function will validate request parameters provided via API.
     *
     *  /api/prefix/{id}/{id2}/{id3}/
     *
     * @access private
     * @return void
     */
    private function validate_all_request_parameters () {
        // set address type
        $this->set_address_type ();
        // validate requested custom field
        $this->validate_request_parameters_custom_field ();
        // validate address type
        if(!in_array($this->_params->id2, $this->valid_address_types))             { $this->Response->throw_exception(400, "Invalid address type"); }
        // validate mask or address
        if($this->query_type!=="address") {
            // validate mask
            if (!is_numeric($this->_params->id3))                                  { $this->Response->throw_exception(400, "Invalid subnet mask"); }
            elseif ($this->_params->id3 < 8)                                       { $this->Response->throw_exception(400, "Invalid subnet mask"); }
            elseif ($this->_params->id3 > 128)                                     { $this->Response->throw_exception(400, "Invalid subnet mask"); }
            elseif ($this->address_type=="IPv4" && $this->_params->id3>32)         { $this->Response->throw_exception(400, "Invalid subnet mask"); }
        }
    }

    /**
     * validate requested custom field
     *
     * @access private
     * @return void
     */
    private function validate_request_parameters_custom_field () {
        if($this->query_type=="address") {
            $this->set_valid_keys ("subnets");
            if (!array_key_exists($this->custom_field_name, $this->custom_fields)) { $this->Response->throw_exception(400, "Invalid custom field ".$this->custom_field_name); }
            // reset keys
            $this->set_valid_keys ("ipaddresses");
        }
        else {
            if (!array_key_exists($this->custom_field_name, $this->custom_fields)) { $this->Response->throw_exception(400, "Invalid custom field ".$this->custom_field_name); }
        }
    }

    /**
     * Address type saving
     *
     * @access private
     * @return void
     */
    private function set_address_type () {
        // ipv4
        if ( strpos($this->_params->id2, "6")!==false )      { $this->address_type = "IPv6"; }
        // ipv6
        elseif ( strpos($this->_params->id2, "4")!==false )  { $this->address_type = "IPv4"; }
        // both
        else                                                 { $this->Response->throw_exception(404, "Invalid address type");  }
    }

    /**
     * Strips outunwanted values
     *
     * @access private
     * @param mixed $result
     * @return object
     */
    private function filter_prefix_result ($result) {
        // loop through fields
        foreach ($result as $k=>$r) {
            if (in_array($k, $this->ignored_prefix_fields)) {
                unset($result->$k);
            }
        }
        // return
        return $result;
    }

    /**
     * Strips outunwanted values from addresses
     *
     * @access private
     * @param mixed $result
     * @return object
     */
    private function filter_addresses_result ($result) {
        // loop through fields
        foreach ($result as $k=>$r) {
            if (in_array($k, $this->ignored_addresses_fields)) {
                unset($result->$k);
            }
        }
        // return
        return $result;
    }
    /**
     * Searches for avaialble master subnets
     *
     * @access private
     * @return array|false
     */
    private function search_custom_field_name_subnets () {
        // set limit base on type
        if($this->address_type=="IPv4")     { $limit = " and `subnet` < 4294967296"; }
        elseif($this->address_type=="IPv6") { $limit = " and `subnet` > 4294967296"; }
        else                                { $limit = ""; }
        // set query and params
        $query = "select * from `subnets` where `".$this->custom_field_name."` = ? $limit order by `".$this->custom_field_orderby."` ".$this->custom_field_order_direction.";";
        $params = array($this->_params->id);
        // search
        try { $subnets = $this->Database->getObjectsQuery($query, $params); }
        catch (Exception $e) { $this->Response->throw_exception(500, "Error: ".$e->getMessage()); }
        // remove if they have slaves for addresses query
        if ($this->query_type=="address") {
            $subnets = $this->remove_if_has_slaves ($subnets);
        }
        // return
        return sizeof($subnets)>0 ? $subnets : false;
    }

    /**
     * Check if subnet has slaves and remove it from result array if yes
     *
     * @access private
     * @param array $subnets
     * @return array
     */
    private function remove_if_has_slaves ($subnets) {
        if(sizeof($subnets)>0) {
            foreach ($subnets as $k=>$s) {
                if($this->Subnets->has_slaves ($s->id)) {
                    unset($subnets[$k]);
                }
            }
            return $subnets;
        }
        else {
            return $subnets;
        }
    }

	/**
	 * Calculates subnet usage
	 *
	 * @access private
	 * @param int $id
	 * @return array
	 */
	private function calculate_subnet_usage ($id) {
		# check that section exists
		$subnet = $this->Subnets->fetch_subnet ("id", $id);
		if($subnet===false)
														{ $this->Response->throw_exception(400, "Subnet does not exist"); }

		# set slaves
		$slaves = $this->Subnets->has_slaves ($id) ? true : false;

		# init controller
		$this->init_object ("Addresses", $this->Database);

		# fetch all addresses and calculate usage
		if($slaves) {
			$addresses = $this->Addresses->fetch_subnet_addresses_recursive ($id, false);
		} else {
			$addresses = $this->Addresses->fetch_subnet_addresses ($id);
		}
		// calculate
		$subnet_usage  = $this->Subnets->calculate_subnet_usage (gmp_strval(sizeof($addresses)), $subnet->mask, $subnet->subnet, $subnet->isFull );		//Calculate free/used etc

		# return
		return $subnet_usage;
	}

    /**
     * Searches for first available subnet from array of master subnets
     *
     * @access private
     * @param mixed $subnets
     * @return bool|array
     */
    private function find_first_available_subnet ($subnets) {
        $available = false;
        // check result
        if($subnets===false)        { return false; }
        // search first available prefix on found addresses
        foreach ($subnets as $s) {
            $available = $this->search_first_available_subnet ($s->id, $this->_params->id3);
            // end if found
            if ($available!==false) {
                // save
                $this->master_subnet = $s;
                break;
            }
        }
        // did we found any
        return $available===false ? false : $available;
    }

    /**
     * Searches for first available subnet
     *
     * @access public
     * @param mixed $master_subnet_id
     * @param mixed $bitmask
     * @return bool|mixed
     */
    public function search_first_available_subnet ($master_subnet_id, $bitmask) {
        // fetch
        $first = $this->Subnets->search_available_single_subnet ($master_subnet_id, $bitmask);
        // return result
        return $first===false ? false : $first[0];
    }

    /**
     * Searches for first available address from array of master subnets
     *
     * @access private
     * @param mixed $subnets
     * @return bool|mixed
     */
    private function find_first_available_address ($subnets) {
        $available = false;
        // check result
        if($subnets===false)        { return false; }
        // search first available prefix on found addresses
        foreach ($subnets as $s) {
            $available = $this->search_first_available_address ($s->id);
            // end if found
            if ($available!==false) {
                // save
                $this->master_subnet = $s;
                break;
            }
        }
        // did we found any
        return $available===false ? false : $available;
    }


    /**
     * Searches for first available address
     *
     * @access public
     * @param mixed $master_subnet_id
     * @return bool|mixed
     */
    public function search_first_available_address ($master_subnet_id) {
        // fetch
        $first = $this->Addresses->get_first_available_address ($master_subnet_id, $this->Subnets);
        // return result
        return $first===false ? false : $this->Tools->transform_address ($first, "dotted");
    }

    /**
     * Searches for external identifier
     *
     * @access private
     * @param string $type (default: "subnets")
     * @return bool|mixed
     */
    private function find_external_id_subnets_addresses ($type = "subnets") {
        // search
        try { $objects = $this->Database->findObjects($type, $this->external_identifier_field, $this->_params->id2, 'id'); }
        catch (Exception $e) { $this->Response->throw_exception(500, "Error: ".$e->getMessage()); }
        // result
        return sizeof($objects)>0 ? $objects : false;
    }
}

?>
