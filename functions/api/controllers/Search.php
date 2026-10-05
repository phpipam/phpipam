<?php

/**
 *	phpIPAM API search class
 *
 *
 */
class Search_controller extends Common_api_functions {

	/**
	 * What items to search
	 * @var array
	 */
	private $search_items = [
						"subnets"         => "1",
						"addresses"       => "1",
						"vlan"            => "0",
						"vrf"             => "0",
						"devices"         => "0",
						"circuits"        => "0",
						"circuitsLogical" => "0",
						"locations"       => "0"
	];

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
		$this->Response = $Response;
		$this->Tools 	= $Tools;
		$this->_params 	= $params;
		// redefine search options
		$this->define_search_items ();
	}

	/**
	 * Define which items to search if provided in query.
	 *
	 *
	 * 	/api/{app_id}/search/{string}/?subnets=0
	 *
	 * @method define_search_items
	 */
	private function define_search_items () {
		foreach ($this->_params as $item => $value) {
			if (array_key_exists((string) $item, $this->search_items)) {
				if($value==0 || $value==1) {
					$this->search_items[$item] = $value;
				}
			}
		}
	}




	/**
	 * Returns json encoded options and version
	 *
	 * @access public
	 * @return void
	 */
	#[\Override]
    public function OPTIONS () {
		// validate
		$this->validate_options_request ();

		// methods
		$result = [];
		$result['methods'] = [
								["href"=>"/api/".$this->_params->app_id."/search/{id}/", 	"methods"=>[["rel"=>"read", 	"method"=>"GET"]]],
							];
		# result
		return ["code"=>200, "data"=>$result];
	}





	/**
	 * GET sections functions
	 *
	 *	ID can be:
	 *		- /{id}/                // search string
	 *
	 *  Result can be filtered [set 0], all defaults to 1:
	 *
	 *  	subnets=1
	 *   	addresses=1
	 *    	vlans=0
	 *      vrfs=0
	 *
	 * @access public
	 * @return void
	 */
	#[\Override]
    public function GET () {
		// start search
		if(isset($this->_params->id)) {
			// set result array
			$result = [];
			// init objects
			$this->init_object ("Tools", $this->Database);

			// search each item if selected
			if ($this->search_items['subnets']==1) 		{ $result['subnets']   = $this->search_subnets (); }
			if ($this->search_items['addresses']==1) 	{ $result['addresses'] = $this->search_addresses (); }
			if ($this->search_items['vlan']==1) 		{ $result['vlan']      = $this->search_vlans (); }
			if ($this->search_items['vrf']==1) 			{ $result['vrf']       = $this->search_vrfs (); }
			if ($this->search_items['devices']==1) 		{ $result['devices']   = $this->search_devices (); }
			if ($this->search_items['circuits']==1) 		{ $result['circuits']  = $this->search_circuits (); }
			if ($this->search_items['circuitsLogical']==1) { $result['circuitsLogical'] = $this->search_circuitsLogical (); }
			if ($this->search_items['locations']==1) 	{ $result['locations'] = $this->search_locations (); }

			// add filter
			$result['search_filter'] = $this->search_items;

			// return result
			return ["code"=>200, "data"=>$result];
		}
		// search string missing
		else {
			$this->Response->throw_exception(400, 'Please provide search string');
		}
	}

	/**
	 * Reformat IP addresses for range search
	 * @method reformat_for_search
	 * @return [type]
	 */
	private function reformat_for_search () {
		// identify address type - v4 or v6
    	$type = $this->Tools->identify_address($this->_params->id);

		// reformat IP addresses for search
    	if ($type == "IPv4") 		{ $searchTerm_edited = $this->Tools->reformat_IPv4_for_search ($this->_params->id); }	//reformat the IPv4 address!
    	elseif($type == "IPv6") 	{ $searchTerm_edited = $this->Tools->reformat_IPv6_for_search ($this->_params->id); }	//reformat the IPv4 address!

    	// return
    	return $searchTerm_edited;
	}

	/**
	 * Search subnets
	 * @method search_subnets
	 * @return array
	 */
	private function search_subnets () {
		// edit search rterm
    	$searchTerm_edited = $this->reformat_for_search ();
		// search subnets
		$result = $this->Tools->search_subnets($this->_params->id, $searchTerm_edited['high'], $searchTerm_edited['low'], NULL, $this->Tools->fetch_custom_fields ("subnets"));

		// result
		if(sizeof($result)==0) {
			return ["code"=>404, "data"=>"No subnets found"];
		}
		else {
			return ["code"=>200, "data"=>$this->prepare_result ($result, "subnets", true, false)];
		}
	}

	/**
	 * Search IP addresses
	 * @method search_addresses
	 * @return array
	 */
	private function search_addresses () {
		// edit search rterm
    	$searchTerm_edited = $this->reformat_for_search ();
		// search
		$result = $this->Tools->search_addresses($this->_params->id, $searchTerm_edited['high'], $searchTerm_edited['low'], $this->Tools->fetch_custom_fields ("ipaddresses"));

		// result
		if(sizeof($result)==0) {
			return ["code"=>404, "data"=>"No addresses found"];
		}
		else {
			return ["code"=>200, "data"=>$this->prepare_result ($result, "addresses", true, false)];
		}
	}

	/**
	 * Search VLANs
	 * @method search_vrfs
	 * @return array
	 */
	private function search_vlans () {
		// search
		$result = $this->Tools->search_vlans($this->_params->id, $this->Tools->fetch_custom_fields ("vlans"));

		// result
		if(sizeof($result)==0) {
			return ["code"=>404, "data"=>"No vlan found"];
		}
		else {
			return ["code"=>200, "data"=>$this->prepare_result ($result, "vlans", true, false)];
		}
	}

	/**
	 * Search VRFs
	 * @method search_vrfs
	 * @return array
	 */
	private function search_vrfs () {
		// search
		$result = $this->Tools->search_vrfs($this->_params->id, $this->Tools->fetch_custom_fields ("vrf"));

		// result
		if(sizeof($result)==0) {
			return ["code"=>404, "data"=>"No vrf found"];
		}
		else {
			return ["code"=>200, "data"=>$this->prepare_result ($result, "vrfs", true, false)];
		}
	}

	/**
	 * Search devices
	 * @method search_devices
	 * @return array
	 */
	private function search_devices () {
		// search
		$result = $this->Tools->search_devices($this->_params->id, $this->Tools->fetch_custom_fields ("devices"));

		// result
		if(sizeof($result)==0) {
			return ["code"=>404, "data"=>"No devices found"];
		}
		else {
			return ["code"=>200, "data"=>$this->prepare_result ($result, "devices", true, false)];
		}
	}

	/**
	 * Search circuits
	 * @method search_circuits
	 * @return array
	 */
	private function search_circuits () {
		// search
		$result = $this->Tools->search_circuits($this->_params->id, $this->Tools->fetch_custom_fields ("circuits"));

		// result
		if(sizeof($result)==0) {
			return ["code"=>404, "data"=>"No circuits found"];
		}
		else {
			return ["code"=>200, "data"=>$this->prepare_result ($result, "circuits", true, false)];
		}
	}

	/**
	 * Search logical circuits
	 * @method search_circuitsLogical
	 * @return array
	 */
	private function search_circuitsLogical () {
		// search
		$result = $this->Tools->search_circuitsLogical($this->_params->id, $this->Tools->fetch_custom_fields ("circuitsLogical"));

		// result
		if(sizeof($result)==0) {
			return ["code"=>404, "data"=>"No logical circuits found"];
		}
		else {
			return ["code"=>200, "data"=>$this->prepare_result ($result, "circuitsLogical", true, false)];
		}
	}

	/**
	 * Search locations
	 * @method search_locations
	 * @return array
	 */
	private function search_locations () {
		// search
		$result = $this->Tools->search_locations($this->_params->id, $this->Tools->fetch_custom_fields ("locations"));

		// result
		if(sizeof($result)==0) {
			return ["code"=>404, "data"=>"No locations found"];
		}
		else {
			return ["code"=>200, "data"=>$this->prepare_result ($result, "locations", true, false)];
		}
	}
}
