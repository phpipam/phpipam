<?php

use OpenApi\Attributes as OA;

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
						"subnets"   => "1",
						"addresses" => "1",
						"vlan"      => "0",
						"vrf"       => "0"
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
	#[OA\Options(
	    path: "/{app_id}/search/",
	    tags: ["search"],
	    summary: "Discover supported search routes/methods (HATEOAS)",
	    parameters: [new OA\Parameter(ref: "#/components/parameters/app_id")],
	    responses: [new OA\Response(response: 200, description: "OK")]
	)]
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
	#[OA\Get(
	    path: "/{app_id}/search/{search_string}/",
	    tags: ["search"],
	    summary: "Search subnets, addresses, VLANs and VRFs for a matching string or IP address",
	    description: "Runs the search string against every object type that is enabled through the subnets/addresses/vlan/vrf query flags (subnets and addresses are searched by default, vlan and vrf are not). IP-like search strings are reformatted so that a partial address also matches. Each enabled object type gets its own key in the response data; a type with no hits reports its own \"code\":404 entry inline rather than failing the whole request. The effective flags are echoed back under \"search_filter\".",
	    parameters: [
	        new OA\Parameter(ref: "#/components/parameters/app_id"),
	        new OA\Parameter(name: "search_string", in: "path", required: true, description: "Search string or (partial) IP address to search for", schema: new OA\Schema(type: "string")),
	        new OA\Parameter(name: "subnets", in: "query", required: false, description: "Include subnets in the search (1=on, default; 0=off)", schema: new OA\Schema(type: "integer", enum: [0, 1], default: 1)),
	        new OA\Parameter(name: "addresses", in: "query", required: false, description: "Include IP addresses in the search (1=on, default; 0=off)", schema: new OA\Schema(type: "integer", enum: [0, 1], default: 1)),
	        new OA\Parameter(name: "vlan", in: "query", required: false, description: "Include VLANs in the search (1=on; 0=off, default)", schema: new OA\Schema(type: "integer", enum: [0, 1], default: 0)),
	        new OA\Parameter(name: "vrf", in: "query", required: false, description: "Include VRFs in the search (1=on; 0=off, default)", schema: new OA\Schema(type: "integer", enum: [0, 1], default: 0)),
	    ],
	    responses: [
	        new OA\Response(
	            response: 200,
	            description: "OK",
	            content: new OA\JsonContent(
	                properties: [
	                    new OA\Property(property: "code", type: "integer", example: 200),
	                    new OA\Property(
	                        property: "data",
	                        type: "object",
	                        properties: [
	                            new OA\Property(property: "subnets", type: "array", items: new OA\Items(ref: "#/components/schemas/Subnet")),
	                            new OA\Property(property: "addresses", type: "array", items: new OA\Items(ref: "#/components/schemas/Address")),
	                            new OA\Property(property: "vlan", type: "array", items: new OA\Items(ref: "#/components/schemas/Vlan")),
	                            new OA\Property(property: "vrf", type: "array", items: new OA\Items(ref: "#/components/schemas/Vrf")),
	                            new OA\Property(property: "search_filter", type: "object", description: "Effective subnets/addresses/vlan/vrf flags applied to this search", example: ["subnets" => "1", "addresses" => "1", "vlan" => "0", "vrf" => "0"]),
	                        ]
	                    ),
	                ]
	            )
	        ),
	        new OA\Response(response: 400, description: "Search string not provided", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
	    ]
	)]
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
}
